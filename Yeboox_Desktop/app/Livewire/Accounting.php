<?php

namespace App\Livewire;

use App\Models\AccountingRecord;
use App\Models\ActivityLog;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Setting;
use App\Services\AccountingService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Accounting extends Component
{
    use WithPagination;

    public string $activeTab = 'overview';

    public string $search = '';

    public int $perPage = 15;

    public string $filterStartDate = '';

    public string $filterEndDate = '';

    public string $filterAccount = '';

    public bool $showEntryModal = false;

    public bool $showExpenseModal = false;

    public bool $showPaymentModal = false;

    public bool $showCreditModal = false;

    public string $recordDate = '';

    public string $debitCode = '601';

    public string $creditCode = '5711';

    public $amount = null;

    public string $description = '';

    public string $expenseDate = '';

    public string $expenseSupplier = '';

    public string $expenseDescription = '';

    public string $expenseAccount = '658';

    public string $expensePaymentMethod = 'cash';

    public string $expenseCreditAccount = '5711';

    public $expenseSubtotal = null;

    public $expenseTaxAmount = 0;

    public string $expenseReceipt = '';

    public string $expenseNotes = '';

    public $paymentInvoiceId = '';

    public string $paymentDate = '';

    public $paymentAmount = null;

    public string $paymentMethod = 'cash';

    public string $paymentTreasuryAccount = '5711';

    public string $paymentReference = '';

    public $creditInvoiceId = '';

    public string $creditDate = '';

    public $creditSubtotal = null;

    public $creditTaxAmount = 0;

    public string $creditReason = '';

    public function mount(): void
    {
        $today = Carbon::today()->toDateString();
        $this->recordDate = $this->expenseDate = $this->paymentDate = $this->creditDate = $today;
        $this->filterStartDate = Carbon::now()->startOfYear()->toDateString();
        $this->filterEndDate = $today;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAccount(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentInvoiceId($id): void
    {
        $this->paymentAmount = Invoice::find($id)?->balance_due;
    }

    public function updatedCreditInvoiceId($id): void
    {
        $this->creditSubtotal = Invoice::find($id)?->balance_due;
        $this->creditTaxAmount = 0;
    }

    public function updatedExpensePaymentMethod(string $method): void
    {
        $this->expenseCreditAccount = match ($method) {
            'bank' => '5211',
            'mobile' => '5218',
            'supplier' => '4011',
            default => '5711',
        };
    }

    public function updatedPaymentMethod(string $method): void
    {
        $this->paymentTreasuryAccount = match ($method) {
            'bank' => '5211',
            'mobile' => '5218',
            default => '5711',
        };
    }

    public function createEntry(AccountingService $service): void
    {
        $data = $this->validate([
            'recordDate' => 'required|date', 'debitCode' => 'required|string|max:20',
            'creditCode' => 'required|string|max:20|different:debitCode',
            'amount' => 'required|numeric|min:0.01', 'description' => 'required|string|max:255',
        ]);
        $reference = $service->postManual([
            'record_date' => $data['recordDate'], 'debit_code' => $data['debitCode'],
            'credit_code' => $data['creditCode'], 'amount' => $data['amount'], 'description' => $data['description'],
        ]);
        ActivityLog::log('Comptabilité', "Écriture {$reference} enregistrée.");
        $this->reset(['amount', 'description', 'showEntryModal']);
        $this->dispatch('notify', 'Écriture équilibrée enregistrée.');
    }

    public function createExpense(AccountingService $service): void
    {
        $data = $this->validate([
            'expenseDate' => 'required|date', 'expenseSupplier' => 'nullable|string|max:150',
            'expenseDescription' => 'required|string|max:255', 'expenseAccount' => 'required|string|max:20',
            'expensePaymentMethod' => 'required|in:cash,bank,mobile,supplier',
            'expenseCreditAccount' => 'required|string|max:20|different:expenseAccount',
            'expenseSubtotal' => 'required|numeric|min:0.01', 'expenseTaxAmount' => 'nullable|numeric|min:0',
            'expenseReceipt' => 'nullable|string|max:100', 'expenseNotes' => 'nullable|string|max:1000',
        ]);
        $expense = $service->createExpense([
            'expense_date' => $data['expenseDate'], 'supplier' => $data['expenseSupplier'] ?: null,
            'description' => $data['expenseDescription'], 'expense_account' => $data['expenseAccount'],
            'payment_method' => $data['expensePaymentMethod'], 'credit_account' => $data['expenseCreditAccount'],
            'subtotal' => $data['expenseSubtotal'], 'tax_amount' => $data['expenseTaxAmount'] ?: 0,
            'receipt_reference' => $data['expenseReceipt'] ?: null, 'notes' => $data['expenseNotes'] ?: null,
        ]);
        ActivityLog::log('Comptabilité', "Dépense {$expense->number} comptabilisée.");
        $this->reset(['expenseSupplier', 'expenseDescription', 'expenseSubtotal', 'expenseTaxAmount', 'expenseReceipt', 'expenseNotes', 'showExpenseModal']);
        $this->dispatch('notify', "Dépense {$expense->number} comptabilisée.");
    }

    public function recordPayment(AccountingService $service): void
    {
        $data = $this->validate([
            'paymentInvoiceId' => 'required|exists:invoices,id', 'paymentDate' => 'required|date',
            'paymentAmount' => 'required|numeric|min:0.01', 'paymentMethod' => 'required|in:cash,bank,mobile',
            'paymentTreasuryAccount' => 'required|string|max:20', 'paymentReference' => 'nullable|string|max:100',
        ]);
        $invoice = Invoice::findOrFail($data['paymentInvoiceId']);
        $payment = $service->recordPayment($invoice, [
            'payment_date' => $data['paymentDate'], 'amount' => $data['paymentAmount'],
            'method' => $data['paymentMethod'], 'treasury_account' => $data['paymentTreasuryAccount'],
            'reference' => $data['paymentReference'] ?: null, 'notes' => null,
        ]);
        ActivityLog::log('Comptabilité', "Paiement {$payment->number} enregistré.");
        $this->reset(['paymentInvoiceId', 'paymentAmount', 'paymentReference', 'showPaymentModal']);
        $this->dispatch('notify', "Paiement {$payment->number} enregistré.");
    }

    public function createCreditNote(AccountingService $service): void
    {
        $data = $this->validate([
            'creditInvoiceId' => 'required|exists:invoices,id', 'creditDate' => 'required|date',
            'creditSubtotal' => 'required|numeric|min:0.01', 'creditTaxAmount' => 'nullable|numeric|min:0',
            'creditReason' => 'required|string|max:1000',
        ]);
        $invoice = Invoice::findOrFail($data['creditInvoiceId']);
        $credit = $service->createCreditNote($invoice, [
            'credit_date' => $data['creditDate'], 'subtotal' => $data['creditSubtotal'],
            'tax_amount' => $data['creditTaxAmount'] ?: 0, 'reason' => $data['creditReason'],
        ]);
        ActivityLog::log('Comptabilité', "Avoir {$credit->number} comptabilisé.");
        $this->reset(['creditInvoiceId', 'creditSubtotal', 'creditTaxAmount', 'creditReason', 'showCreditModal']);
        $this->dispatch('notify', "Avoir {$credit->number} comptabilisé.");
    }

    public function render()
    {
        $base = AccountingRecord::query()
            ->when($this->filterStartDate, fn ($q) => $q->where('record_date', '>=', $this->filterStartDate))
            ->when($this->filterEndDate, fn ($q) => $q->where('record_date', '<=', $this->filterEndDate));
        // Calculer les indicateurs dans SQLite au lieu de charger tout le journal en mémoire.
        $periodSummary = (clone $base)
            ->selectRaw("COALESCE(SUM(CASE WHEN syscohada_code LIKE '7%' THEN credit ELSE 0 END), 0) as revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN syscohada_code LIKE '6%' THEN debit ELSE 0 END), 0) as expenses")
            ->selectRaw("COALESCE(SUM(CASE WHEN syscohada_code LIKE '5%' THEN debit - credit ELSE 0 END), 0) as cash")
            ->first();
        $invoiceQuery = Invoice::with('customer')->withSum('creditNotes', 'total_amount')
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub->where('number', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$this->search}%"))));
        $recordQuery = (clone $base)->with('user')
            ->when($this->filterAccount, fn ($q) => $q->where('syscohada_code', 'like', "{$this->filterAccount}%"))
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub->where('reference', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")));

        return view('livewire.accounting', [
            'records' => $recordQuery->latest('record_date')->latest('id')->paginate($this->perPage),
            'invoices' => $invoiceQuery->latest('invoice_date')->limit(100)->get(),
            'openInvoices' => Invoice::with('customer')->withSum('creditNotes', 'total_amount')->whereNotNull('posted_at')->where('status', '!=', 'paid')->latest()->limit(100)->get()->filter(fn ($invoice) => $invoice->balance_due > 0),
            'payments' => InvoicePayment::with(['invoice.customer', 'user'])->latest('payment_date')->limit(100)->get(),
            'creditNotes' => CreditNote::with(['invoice', 'customer'])->latest('credit_date')->limit(100)->get(),
            'expenses' => Expense::with('user')->latest('expense_date')->limit(100)->get(),
            'customers' => Customer::withCount('sales')->orderBy('name')->limit(100)->get(),
            'products' => Product::with('unitOfMeasure')->where('is_active', true)->orderBy('name')->limit(100)->get(),
            'revenue' => (float) $periodSummary->revenue,
            'expensesTotal' => (float) $periodSummary->expenses,
            'receivables' => Invoice::withSum('creditNotes', 'total_amount')->whereNotNull('posted_at')->get()->sum(fn ($invoice) => $invoice->balance_due),
            'cash' => (float) $periodSummary->cash,
            'currency' => Setting::get('company_currency', 'CDF'),
        ])->layout('layouts.app');
    }
}
