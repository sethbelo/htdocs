<?php

namespace App\Livewire\Finance;

use App\Enums\PaymentMethod;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\WithListFilters;
use App\Models\ExpenseClaim;
use App\Models\TreasuryAccount;
use App\Services\Finance\ExpenseService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Notes de frais')]
class Expenses extends Component
{
    use InteractsWithToasts, WithFileUploads, WithListFilters, WithPagination;

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    public bool $mineOnly = false;

    /** @var array<string, string> */
    public array $form = ['title' => '', 'description' => '', 'amount' => '', 'expense_date' => ''];

    /** @var array<int, mixed> */
    public array $receipts = [];

    public ?int $payingId = null;

    /** @var array<string, string> */
    public array $payment = ['treasury_account_id' => '', 'method' => 'cash', 'reference' => ''];

    public function mount(): void
    {
        $this->mineOnly = ! auth()->user()->can('expenses.approve');
        $this->form['expense_date'] = today()->toDateString();
    }

    public function submit(ExpenseService $expenses): void
    {
        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:2000'],
            'form.amount' => ['required', 'numeric', 'gt:0'],
            'form.expense_date' => ['required', 'date', 'before_or_equal:today'],
            'receipts' => ['required', 'array', 'min:1'],
            'receipts.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ], [], ['receipts' => 'justificatifs', 'form.title' => 'objet'])['form'];

        if ($this->attempt(fn () => $expenses->submit(auth()->user(), $data, $this->receipts), 'Note de frais soumise à validation.')) {
            $this->reset('form', 'receipts');
            $this->form['expense_date'] = today()->toDateString();
            $this->modal('expense')->close();
        }
    }

    public function openPay(int $id): void
    {
        $this->payingId = $id;
        $this->modal('pay')->show();
    }

    public function pay(ExpenseService $expenses): void
    {
        $data = $this->validate([
            'payment.treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
            'payment.method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment.reference' => ['nullable', 'string', 'max:100'],
        ])['payment'];

        if ($this->attempt(fn () => $expenses->pay(ExpenseClaim::query()->findOrFail($this->payingId), TreasuryAccount::query()->findOrFail($data['treasury_account_id']), PaymentMethod::from($data['method']), auth()->user(), $data['reference'] ?: null), 'Note de frais payée.')) {
            $this->modal('pay')->close();
        }
    }

    public function render()
    {
        return view('livewire.finance.expenses', [
            'claims' => ExpenseClaim::query()->with('user', 'approvalRequest.currentStep', 'receipts.latestVersion')
                ->when($this->mineOnly, fn ($q) => $q->where('user_id', auth()->id()))
                ->when(! $this->mineOnly, fn ($q) => $q->visibleTo(auth()->user()))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->tap(fn ($q) => $this->applySearch($q, ['number', 'title', 'description', 'user.name']))
                ->tap(fn ($q) => $this->applyPeriod($q, 'expense_date'))
                ->latest()->paginate(20),
            'treasuryAccounts' => TreasuryAccount::query()->where('is_active', true)->get(),
            'methods' => PaymentMethod::options(),
        ]);
    }
}
