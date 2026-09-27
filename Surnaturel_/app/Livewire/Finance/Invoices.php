<?php

namespace App\Livewire\Finance;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Exports\TableExport;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Factures')]
class Invoices extends Component
{
    use ScopesToSite, WithPagination;

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
        $base = $this->query();

        return view('livewire.finance.invoices', [
            'invoices' => (clone $base)->latest('issued_at')->paginate(25),
            'totals' => [
                'invoiced' => (float) (clone $base)->sum('total'),
                'paid' => (float) (clone $base)->sum('amount_paid'),
                'overdue' => (float) (clone $base)->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid])->whereDate('due_at', '<', today())->selectRaw('COALESCE(SUM(total - amount_paid), 0) as d')->value('d'),
            ],
            'statuses' => InvoiceStatus::options(),
            'types' => InvoiceType::options(),
        ]);
    }
}
