<?php

namespace App\Livewire\Finance;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Exports\TableExport;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Invoice;
use App\Models\TreasuryAccount;
use App\Services\Finance\ReceivablesService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Factures')]
class Invoices extends Component
{
    use InteractsWithToasts, ScopesToSite, WithPagination;

    #[Url(as: 'type', except: '')]
    public string $type = '';

    public ?int $actionId = null;

    /** @var array<string, mixed> */
    public array $action = ['kind' => 'collect', 'amount' => '', 'method' => 'bank_transfer', 'treasury_account_id' => '', 'reference' => '', 'reason' => ''];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'impayees')]
    public bool $unpaidOnly = false;

    #[Url(as: 'du')]
    public ?string $from = null;

    #[Url(as: 'au')]
    public ?string $to = null;

    private function query(): Builder
    {
        return Invoice::query()->visibleTo(auth()->user())->forSite($this->siteId)->with('client', 'order', 'sale')
            ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('number', 'like', "%{$this->search}%")
                ->orWhereHas('order', fn (Builder $o) => $o->where('number', 'like', "%{$this->search}%"))
                ->orWhereHas('client', fn (Builder $c) => $c->search($this->search))))
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->type, fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->unpaidOnly, fn (Builder $q) => $q->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid]))
            ->when($this->from, fn (Builder $q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->to, fn (Builder $q) => $q->whereDate('issued_at', '<=', $this->to));
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('invoices.export');

        $rows = $this->query()->latest('issued_at')->get()->map(fn (Invoice $i): array => [
            $i->number, $i->type->label(), $i->order?->number ?? $i->sale?->number, $i->issued_at->format('d/m/Y'), $i->client?->displayName(),
            (float) $i->total - (float) $i->tax_total, (float) $i->tax_total, (float) $i->total, (float) $i->amount_paid, $i->balanceDue(), $i->status->label(),
        ]);

        return Excel::download(new TableExport(['N°', 'Type', 'Réf. commande/vente', 'Date', 'Client', 'HT', 'TVA', 'TTC', 'Payé', 'Reste', 'Statut'], $rows, 'Factures'), 'factures-'.now()->format('Ymd').'.xlsx');
    }

    public function render()
    {
        $current = $this->actionId ? Invoice::query()->with('client', 'order')->find($this->actionId) : null;

        $base = $this->query();
        $billed = (clone $base)->where('type', '!=', InvoiceType::CreditNote);

        return view('livewire.finance.invoices', [
            'invoices' => (clone $base)->latest('issued_at')->paginate(25),
            'totals' => [
                'invoiced' => (float) (clone $billed)->sum('total'),
                'paid' => (float) (clone $billed)->sum('amount_paid'),
                'overdue' => (float) (clone $billed)->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid])->whereDate('due_at', '<', today())->selectRaw('COALESCE(SUM(total - amount_paid), 0) as d')->value('d'),
            ],
            'statuses' => InvoiceStatus::options(),
            'types' => InvoiceType::options(),
            'current' => $current,
            'treasuryAccounts' => TreasuryAccount::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('site_id')->orWhereIn('site_id', auth()->user()->accessibleSiteIds()))->orderBy('name')->get(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function openAction(int $id, string $kind): void
    {
        $invoice = $this->query()->findOrFail($id);
        $this->actionId = $invoice->id;
        $this->action = ['kind' => $kind, 'amount' => number_format($invoice->balanceDue(), 2, '.', ''), 'method' => 'bank_transfer', 'treasury_account_id' => '', 'reference' => '', 'reason' => ''];
        $this->modal('invoice-action')->show();
    }

    public function runAction(ReceivablesService $receivables): void
    {
        $invoice = $this->query()->findOrFail($this->actionId);
        $kind = $this->action['kind'];

        if ($kind === 'collect') {
            $this->authorize('payments.create');
            $data = $this->validate([
                'action.amount' => ['required', 'numeric', 'gt:0'],
                'action.method' => ['required', Rule::enum(PaymentMethod::class)],
                'action.treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
                'action.reference' => ['nullable', 'string', 'max:100'],
            ], [], ['action.treasury_account_id' => 'compte de trésorerie', 'action.amount' => 'montant'])['action'];
            $done = $this->attempt(fn () => $receivables->collect($invoice, TreasuryAccount::query()->findOrFail($data['treasury_account_id']), PaymentMethod::from($data['method']), (float) $data['amount'], auth()->user(), $data['reference'] ?: null), 'Encaissement enregistré et comptabilisé.');
        } else {
            $this->authorize('invoices.cancel');
            $data = $this->validate([
                'action.amount' => [$kind === 'credit' ? 'required' : 'nullable', 'numeric', 'gt:0'],
                'action.reason' => ['required', 'string', 'max:255'],
            ], [], ['action.reason' => 'motif', 'action.amount' => 'montant'])['action'];
            $done = $this->attempt(fn () => $kind === 'credit'
                ? $receivables->creditNote($invoice, (float) $data['amount'], $data['reason'], auth()->user())
                : $receivables->cancel($invoice, $data['reason'], auth()->user()), $kind === 'credit' ? 'Avoir émis et comptabilisé.' : 'Facture annulée par avoir.');
        }

        if ($done) {
            $this->modal('invoice-action')->close();
            $this->reset('actionId');
        }
    }
}
