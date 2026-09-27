<?php

namespace App\Livewire\Admin\Accounting;

use App\Models\Account;
use App\Models\Expense;
use App\Models\PaymentMethod;
use Livewire\Component;
use Livewire\WithPagination;

class Expenses extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public string $filterCategory = '';

    public string $search = '';

    // Create/Edit
    public bool $showModal = false;

    public ?string $editId = null;

    public string $category = '';

    public string $description = '';

    public float $amount = 0;

    public string $expense_date = '';

    public string $account_id = '';

    public string $payment_method_id = '';

    public string $notes = '';

    protected function rules(): array
    {
        return [
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editId', 'category', 'description', 'amount', 'expense_date', 'account_id', 'payment_method_id', 'notes']);
        $this->expense_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $eid = auth()->user()->establishment_id;

        $data = [
            'establishment_id' => $eid,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'account_id' => $this->account_id ?: null,
            'payment_method_id' => $this->payment_method_id ?: null,
            'notes' => $this->notes ?: null,
            'created_by' => auth()->id(),
        ];

        if ($this->editId) {
            Expense::find($this->editId)?->update($data);
        } else {
            Expense::create($data);
        }

        $this->showModal = false;
    }

    public function approve(string $id): void
    {
        $expense = Expense::find($id);
        if (! $expense || $expense->status !== 'pending') {
            return;
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function reject(string $id): void
    {
        $expense = Expense::find($id);
        if (! $expense || $expense->status !== 'pending') {
            return;
        }

        $expense->update(['status' => 'rejected']);
    }

    public function render()
    {
        $eid = auth()->user()->establishment_id;

        $query = Expense::where('establishment_id', $eid)->with(['account', 'creator', 'approver']);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }
        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('description', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%")
            );
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(15);

        // KPIs
        $totalMonth = Expense::where('establishment_id', $eid)->approved()
            ->whereMonth('expense_date', now()->month)->sum('amount');
        $pendingCount = Expense::where('establishment_id', $eid)->pending()->count();
        $totalApproved = Expense::where('establishment_id', $eid)->approved()->count();

        $accounts = Account::where('establishment_id', $eid)->where('class', 6)->detail()->orderBy('code')->get();
        $paymentMethods = PaymentMethod::where('establishment_id', $eid)->get();
        $categories = Expense::categories();

        return view('livewire.admin.accounting.expenses', compact(
            'expenses', 'totalMonth', 'pendingCount', 'totalApproved',
            'accounts', 'paymentMethods', 'categories'
        ))->layout('layouts.admin', ['title' => 'Dépenses']);
    }
}
