<?php

namespace App\Livewire\Finance;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\BankTransaction;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Trésorerie : comptes bancaires, caisses et mobile money ; mouvements,
 * virements internes, frais et rapprochement.
 */
#[Title('Trésorerie')]
class Banking extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url(as: 'onglet')]
    public string $tab = 'mouvements';

    #[Url(as: 'compte')]
    public ?int $accountId = null;

    // Dépense générale
    public bool $showExpense = false;

    public array $expense = [];

    public string $search = '';

    public bool $showAccount = false;

    public array $account = [];

    public bool $showMove = false;

    public array $move = [];

    public function mount(): void
    {
        $this->accountId ??= FinancialAccount::where('company_id', company_id())->where('type', 'bank')->value('id');
    }

    public function updatingAccountId(): void
    {
        $this->resetPage();
    }

    public function editAccount(?int $id = null): void
    {
        $this->authorizeModule('banking', 'manage');
        $a = $id ? FinancialAccount::where('company_id', company_id())->findOrFail($id) : null;
        $this->account = ['id' => $a?->id, 'type' => $a?->type ?? 'bank', 'name' => $a?->name ?? '', 'provider' => $a?->provider ?? '', 'account_number' => $a?->account_number ?? '', 'currency_code' => $a?->currency_code ?? 'USD', 'opening_balance' => (float) ($a?->opening_balance ?? 0), 'is_active' => $a?->is_active ?? true];
        $this->showAccount = true;
    }

    public function saveAccount(): void
    {
        $this->authorizeModule('banking', 'manage');
        $data = $this->validate(['account.type' => ['required', 'in:bank,cash,mobile_money'], 'account.name' => ['required', 'string', 'max:120'], 'account.provider' => ['nullable', 'string', 'max:120'], 'account.account_number' => ['nullable', 'string', 'max:60'], 'account.currency_code' => ['required', 'in:USD,CDF,EUR'], 'account.opening_balance' => ['numeric'], 'account.is_active' => ['boolean']], [], ['account.name' => 'nom'])['account'];
        unset($data['id']);
        FinancialAccount::updateOrCreate(['id' => $this->account['id']], $data + ['company_id' => company_id()]);
        $this->showAccount = false;
        $this->toast('Compte de trésorerie enregistré.');
    }

    public function newMove(string $kind = 'transfer'): void
    {
        $this->authorizeModule('banking', 'manage');
        $this->move = ['kind' => $kind, 'from' => $this->accountId, 'to' => null, 'date' => now()->toDateString(), 'amount' => '', 'label' => $kind === 'fee' ? 'Frais bancaires' : ($kind === 'transfer' ? 'Virement interne' : 'Apport'), 'reference' => ''];
        $this->showMove = true;
    }

    public function saveMove(SynchronizeAccounting $accounting): void
    {
        $this->authorizeModule('banking', 'manage');
        $this->validate(['move.kind' => ['required', 'in:transfer,fee,in'], 'move.from' => ['required', 'integer'], 'move.to' => ['required_if:move.kind,transfer', 'nullable', 'integer', 'different:move.from'], 'move.date' => ['required', 'date'], 'move.amount' => ['required', 'numeric', 'gt:0'], 'move.label' => ['required', 'string', 'max:160']], [], ['move.to' => 'compte destinataire', 'move.amount' => 'montant']);
        $m = $this->move;
        $treasury = fn (FinancialAccount $a) => ['cash' => '571', 'mobile_money' => '581'][$a->type] ?? '521';
        $journal = fn (FinancialAccount $a) => $a->type === 'cash' ? 'CA' : 'BQ';

        DB::transaction(function () use ($m, $accounting, $treasury, $journal) {
            $from = FinancialAccount::where('company_id', company_id())->findOrFail($m['from']);
            $amount = round((float) $m['amount'], 2);
            if ($m['kind'] === 'transfer') {
                $to = FinancialAccount::where('company_id', company_id())->findOrFail($m['to']);
                $out = BankTransaction::create(['financial_account_id' => $from->id, 'transaction_date' => $m['date'], 'reference' => $m['reference'] ?: null, 'description' => $m['label'].' → '.$to->name, 'amount' => -$amount, 'status' => 'reconciled']);
                BankTransaction::create(['financial_account_id' => $to->id, 'transaction_date' => $m['date'], 'reference' => $m['reference'] ?: null, 'description' => $m['label'].' ← '.$from->name, 'amount' => $amount, 'status' => 'reconciled']);
                $accounting->postLines($out, $journal($from), $m['date'], $m['label'], [[$treasury($to), $m['label'], $amount, 0], [$treasury($from), $m['label'], 0, $amount]], company_id());
            } else {
                $signed = $m['kind'] === 'fee' ? -$amount : $amount;
                $tx = BankTransaction::create(['financial_account_id' => $from->id, 'transaction_date' => $m['date'], 'reference' => $m['reference'] ?: null, 'description' => $m['label'], 'amount' => $signed, 'status' => 'reconciled']);
                $accounting->postLines($tx, $journal($from), $m['date'], $m['label'], $m['kind'] === 'fee'
                    ? [['631', $m['label'], $amount, 0], [$treasury($from), $m['label'], 0, $amount]]
                    : [[$treasury($from), $m['label'], $amount, 0], ['101', $m['label'], 0, $amount]], company_id());
            }
        });
        $this->showMove = false;
        $this->toast('Mouvement enregistré et comptabilisé.');
    }

    public function newExpense(): void
    {
        $this->authorizeModule('banking', 'manage');
        $this->expense = ['description' => '', 'account_code' => '605', 'supplier_id' => null, 'amount' => '', 'tax_amount' => '0', 'expense_date' => now()->toDateString(), 'financial_account_id' => $this->accountId, 'notes' => ''];
        $this->showExpense = true;
    }

    public function saveExpense(SynchronizeAccounting $accounting): void
    {
        $this->authorizeModule('banking', 'manage');
        $data = $this->validate([
            'expense.description' => ['required', 'string', 'max:200'], 'expense.account_code' => ['required', 'in:'.implode(',', array_keys(config('erp.expense_accounts')))],
            'expense.supplier_id' => ['nullable', 'integer'], 'expense.amount' => ['required', 'numeric', 'gt:0'], 'expense.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'expense.expense_date' => ['required', 'date'], 'expense.financial_account_id' => ['required', 'integer'], 'expense.notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['expense.description' => 'libellé', 'expense.amount' => 'montant', 'expense.financial_account_id' => 'compte de paiement'])['expense'];
        $account = FinancialAccount::where('company_id', company_id())->findOrFail($data['financial_account_id']);
        DB::transaction(function () use ($data, $account, $accounting) {
            $expense = Expense::create([
                'company_id' => company_id(), 'created_by' => auth()->id(), 'financial_account_id' => $account->id, 'supplier_id' => $data['supplier_id'] ?: null,
                'reference' => 'DEP-'.now()->format('ymd').'-'.random_int(100, 999), 'account_code' => $data['account_code'], 'description' => $data['description'],
                'amount' => $data['amount'], 'tax_amount' => $data['tax_amount'] ?: 0, 'currency_code' => $account->currency_code, 'exchange_rate' => 1,
                'expense_date' => $data['expense_date'], 'status' => 'approved', 'notes' => $data['notes'] ?: null,
                'payment_method' => ['cash' => 'cash', 'mobile_money' => 'mobile_money'][$account->type] ?? 'bank_transfer',
            ]);
            $accounting->expense($expense);
        });
        $this->showExpense = false;
        $this->toast('Dépense enregistrée et comptabilisée.');
    }

    public function toggleReconciled(int $id): void
    {
        $this->authorizeModule('banking', 'manage');
        $tx = BankTransaction::whereHas('account', fn ($q) => $q->where('company_id', company_id()))->findOrFail($id);
        $tx->update(['status' => $tx->status === 'reconciled' ? 'pending' : 'reconciled']);
    }

    public function render()
    {
        $cid = company_id();
        $accounts = FinancialAccount::where('company_id', $cid)->orderByRaw("CASE type WHEN 'bank' THEN 0 WHEN 'mobile_money' THEN 1 ELSE 2 END")->get();
        $months = collect(range(5, 0))->map(fn ($n) => now()->subMonthsNoOverflow($n)->startOfMonth());
        $ids = $accounts->pluck('id');

        return view('livewire.finance.banking', [
            'expenses' => $this->tab === 'depenses' ? Expense::with('supplier', 'financialAccount')->where('company_id', $cid)->latest('expense_date')->latest('id')->paginate(20) : null,
            'suppliers' => Contact::where('company_id', $cid)->where('type', 'supplier')->orderBy('company_name')->get(),
            'accounts' => $accounts,
            'current' => $accounts->firstWhere('id', $this->accountId),
            'transactions' => BankTransaction::where('financial_account_id', $this->accountId)
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', "%{$this->search}%")->orWhere('reference', 'like', "%{$this->search}%")))
                ->latest('transaction_date')->latest('id')->paginate(20),
            'chart' => [
                'type' => 'line', 'money' => true, 'legend' => true,
                'labels' => $months->map(fn ($m) => ucfirst($m->translatedFormat('M')))->values(),
                'datasets' => [
                    ['label' => 'Entrées', 'data' => $months->map(fn ($m) => round((float) BankTransaction::whereIn('financial_account_id', $ids)->where('amount', '>', 0)->whereBetween('transaction_date', [$m, $m->copy()->endOfMonth()])->sum('amount')))->values(), 'color' => '#3acece', 'fill' => true],
                    ['label' => 'Sorties', 'data' => $months->map(fn ($m) => round(abs((float) BankTransaction::whereIn('financial_account_id', $ids)->where('amount', '<', 0)->whereBetween('transaction_date', [$m, $m->copy()->endOfMonth()])->sum('amount'))))->values(), 'color' => '#f7313a'],
                ],
            ],
        ]);
    }
}
