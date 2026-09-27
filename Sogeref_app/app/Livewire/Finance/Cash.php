<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\CashSession;
use App\Models\Document;
use App\Models\FinancialAccount;
use App\Models\SalesOrder;
use App\Models\Voucher;
use App\Services\CashService;
use App\Services\OrderWorkflow;
use App\Services\VoucherService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Caisse : session de l'agent, encaissement des factures (qui fait avancer
 * le suivi de commande), exécution des bons de décaissement, clôture.
 */
#[Title('Caisse')]
class Cash extends Component
{
    use InteractsWithUi;

    #[Url(as: 'onglet')]
    public string $tab = 'encaisser';

    public string $search = '';

    // Ouverture / clôture
    public ?int $accountId = null;

    public string $openingAmount = '0';

    public bool $showClose = false;

    public string $countedAmount = '';

    public string $closingNotes = '';

    // Encaissement
    public bool $showCollect = false;

    public ?int $invoiceId = null;

    public string $amount = '';

    public string $method = 'cash';

    public ?int $collectAccountId = null;

    public string $reference = '';

    public function mount(): void
    {
        $this->accountId = FinancialAccount::where('company_id', company_id())->where('type', 'cash')->where('is_active', true)->value('id');
        if ($invoice = request()->integer('facture')) {
            $this->startCollect($invoice);
        }
    }

    private function session(): ?CashSession
    {
        return app(CashService::class)->current();
    }

    // ── Session ─────────────────────────────────────────────────────────────
    public function open(CashService $cash): void
    {
        $this->authorizeModule('cash', 'manage');
        $this->validate(['accountId' => ['required', 'integer'], 'openingAmount' => ['required', 'numeric', 'min:0']], [], ['accountId' => 'caisse', 'openingAmount' => 'fonds de caisse']);
        $account = FinancialAccount::where('company_id', company_id())->findOrFail($this->accountId);
        $this->attempt(fn () => $cash->open($account, (float) $this->openingAmount), 'Session de caisse ouverte.');
    }

    public function prepareClose(CashService $cash): void
    {
        $this->countedAmount = number_format($cash->expected($this->session()), 2, '.', '');
        $this->showClose = true;
    }

    public function close(CashService $cash): void
    {
        $this->authorizeModule('cash', 'manage');
        $this->validate(['countedAmount' => ['required', 'numeric', 'min:0']], [], ['countedAmount' => 'montant compté']);
        $session = $this->session();
        $closed = $this->attempt(fn () => $cash->close($session, (float) $this->countedAmount, $this->closingNotes ?: null));
        if ($closed) {
            $gap = round((float) $closed->closing_amount - (float) $closed->expected_amount, 2);
            $this->toast($gap == 0.0 ? 'Caisse clôturée sans écart.' : 'Caisse clôturée — écart de '.money($gap).'.', $gap == 0.0 ? 'success' : 'warning');
            $this->showClose = false;
        }
    }

    // ── Encaissement ────────────────────────────────────────────────────────
    public function startCollect(int $invoiceId): void
    {
        $invoice = Document::where('company_id', company_id())->where('type', 'invoice')->findOrFail($invoiceId);
        $this->invoiceId = $invoice->id;
        $order = SalesOrder::where('invoice_id', $invoice->id)->first();
        $deposit = $order && $order->stage === 'facture'
            ? round((float) $invoice->total * app(OrderWorkflow::class)->depositRate() / 100 - (float) $invoice->amount_paid, 2) : 0;
        $this->amount = number_format($deposit > 0 ? $deposit : $invoice->balance_due, 2, '.', '');
        $this->method = 'cash';
        $this->collectAccountId = $this->session()?->financial_account_id;
        $this->reference = '';
        $this->showCollect = true;
    }

    public function updatedMethod(): void
    {
        $this->collectAccountId = $this->method === 'cash'
            ? $this->session()?->financial_account_id
            : FinancialAccount::where('company_id', company_id())->where('type', $this->method === 'mobile_money' ? 'mobile_money' : 'bank')->value('id');
    }

    public function collect(CashService $cash, OrderWorkflow $workflow): void
    {
        $this->authorizeModule('cash', 'manage');
        $this->validate(['amount' => ['required', 'numeric', 'gt:0'], 'collectAccountId' => ['required', 'integer']], [], ['amount' => 'montant', 'collectAccountId' => 'compte']);
        $session = $this->session();
        if (! $session) {
            $this->toast('Ouvrez d’abord votre session de caisse.', 'error');

            return;
        }
        $invoice = Document::where('company_id', company_id())->findOrFail($this->invoiceId);
        $account = FinancialAccount::where('company_id', company_id())->findOrFail($this->collectAccountId);

        $done = $this->attempt(function () use ($cash, $workflow, $invoice, $account, $session) {
            $cash->receive($invoice, (float) $this->amount, $this->method, $account, $this->reference ?: null, "Encaissement caisse {$session->reference}");
            if ($order = SalesOrder::where('invoice_id', $invoice->id)->first()) {
                $workflow->syncPayment($order);
            }
        }, 'Encaissement de '.money($this->amount).' enregistré — '.$invoice->number);
        if ($done) {
            $this->showCollect = false;
        }
    }

    // ── Décaissements approuvés ─────────────────────────────────────────────
    public function disburse(int $voucherId): void
    {
        $vouchers = app(VoucherService::class);
        abort_unless($this->allowed('vouchers.execute'), 403);
        $session = $this->session();
        if (! $session) {
            $this->toast('Ouvrez d’abord votre session de caisse.', 'error');

            return;
        }
        $voucher = Voucher::where('company_id', company_id())->where('type', 'decaissement')->findOrFail($voucherId);
        $this->attempt(fn () => $vouchers->execute($voucher, $session->financial_account_id), "Bon {$voucher->number} décaissé.");
    }

    public function render(CashService $cash)
    {
        $session = $this->session();
        $cid = company_id();

        return view('livewire.finance.cash', [
            'session' => $session,
            'expected' => $session ? $cash->expected($session) : 0,
            'movements' => $session ? $cash->movements($session)->limit(50)->get() : collect(),
            'cashAccounts' => FinancialAccount::where('company_id', $cid)->where('type', 'cash')->where('is_active', true)->get(),
            'accounts' => FinancialAccount::where('company_id', $cid)->where('is_active', true)->get(),
            'invoices' => Document::with('contact')->where('company_id', $cid)->where('type', 'invoice')->whereNotIn('status', ['paid', 'cancelled'])
                ->whereColumn('amount_paid', '<', 'total')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")->orWhereHas('contact', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%"))))
                ->orderBy('due_date')->limit(40)->get(),
            'orders' => SalesOrder::where('company_id', $cid)->whereNotNull('invoice_id')->get()->keyBy('invoice_id'),
            'disbursements' => Voucher::with('site', 'employee')->where('company_id', $cid)->where('type', 'decaissement')->where('status', 'approved')->latest('voucher_date')->get(),
            'history' => CashSession::with('user', 'account')->where('company_id', $cid)->where('status', 'closed')->latest('closed_at')->limit(10)->get(),
            'invoice' => $this->invoiceId ? Document::with('contact')->find($this->invoiceId) : null,
        ]);
    }
}
