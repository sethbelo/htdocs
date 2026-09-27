<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\DisbursementRequest;
use App\Models\EquityMovement;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Setting;
use App\Services\AccountingService;
use App\Support\ModuleRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Operations extends Component
{
    public string $module = 'expenses';

    public string $month = '';

    public bool $showForm = false;

    public string $date = '';

    public string $party = '';

    public string $description = '';

    public float|string $subtotal = '';

    public float|string $tax = 0;

    public float|string $amount = '';

    public string $account = '658';

    public string $treasuryAccount = '5711';

    public string $reference = '';

    public string $movementType = 'contribution';

    public string $notes = '';

    public function mount(string $module = 'expenses'): void
    {
        abort_unless(isset(ModuleRegistry::DEFINITIONS[$module]) && ModuleRegistry::enabled($module), 403, 'Ce module mensuel est inactif ou expiré.');
        $this->module = $module;
        $this->month = now()->format('Y-m');
        $this->date = now()->toDateString();
        $this->account = match ($module) {
            'purchases' => '601','equity' => '101',default => '658'
        };
        $this->treasuryAccount = $module === 'equity' ? '5211' : '5711';
    }

    public function openForm(): void
    {
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(AccountingService $accounting): void
    {
        match ($this->module) {
            'expenses' => $this->saveExpense($accounting),
            'purchases' => $this->savePurchase(),
            'disbursements' => $this->saveDisbursement(),
            'equity' => $this->saveEquity($accounting),
            default => null,
        };

        $this->showForm = false;
        $this->reset(['party', 'description', 'subtotal', 'amount', 'reference', 'notes']);
        $this->date = now()->toDateString();
        $this->tax = 0;
        $this->dispatch('notify', message: 'Opération enregistrée avec succès.');
    }

    private function saveExpense(AccountingService $accounting): void
    {
        $data = $this->validate(['date' => 'required|date', 'party' => 'nullable|string|max:255', 'description' => 'required|string|max:255', 'subtotal' => 'required|numeric|min:0.01', 'tax' => 'nullable|numeric|min:0', 'account' => 'required|string|max:20', 'treasuryAccount' => 'required|string|max:20', 'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:1000']);
        $accounting->createExpense(['expense_date' => $data['date'], 'supplier' => $data['party'], 'description' => $data['description'], 'expense_account' => $data['account'], 'payment_method' => 'cash', 'credit_account' => $data['treasuryAccount'], 'subtotal' => $data['subtotal'], 'tax_amount' => $data['tax'] ?: 0, 'receipt_reference' => $data['reference'], 'notes' => $data['notes']]);
    }

    private function savePurchase(): void
    {
        $data = $this->validate(['date' => 'required|date', 'party' => 'required|string|max:255', 'description' => 'required|string|max:255', 'subtotal' => 'required|numeric|min:0.01', 'tax' => 'nullable|numeric|min:0', 'account' => 'required|string|max:20', 'reference' => 'nullable|string|max:100']);
        Purchase::create(['number' => $this->number('ACH'), 'purchase_date' => $data['date'], 'supplier' => $data['party'], 'description' => $data['description'], 'expense_account' => $data['account'], 'subtotal' => $data['subtotal'], 'tax_amount' => $data['tax'] ?: 0, 'total_amount' => (float) $data['subtotal'] + (float) ($data['tax'] ?: 0), 'supplier_reference' => $data['reference'], 'user_id' => auth()->id()]);
    }

    private function saveDisbursement(): void
    {
        $data = $this->validate(['date' => 'required|date', 'party' => 'required|string|max:255', 'description' => 'required|string|max:255', 'amount' => 'required|numeric|min:0.01', 'account' => 'required|string|max:20', 'treasuryAccount' => 'required|string|max:20', 'notes' => 'nullable|string|max:1000']);
        DisbursementRequest::create(['number' => $this->number('DEC'), 'requested_date' => $data['date'], 'beneficiary' => $data['party'], 'purpose' => $data['description'], 'amount' => $data['amount'], 'expense_account' => $data['account'], 'treasury_account' => $data['treasuryAccount'], 'notes' => $data['notes'], 'requested_by' => auth()->id()]);
    }

    private function saveEquity(AccountingService $accounting): void
    {
        $data = $this->validate(['date' => 'required|date', 'party' => 'required|string|max:255', 'description' => 'required|string|max:255', 'amount' => 'required|numeric|min:0.01', 'movementType' => ['required', Rule::in(['contribution', 'withdrawal'])], 'account' => 'required|string|max:20', 'treasuryAccount' => 'required|string|max:20']);
        DB::transaction(function () use ($data, $accounting) {
            $movement = EquityMovement::create(['number' => $this->number('CAP'), 'movement_date' => $data['date'], 'partner' => $data['party'], 'type' => $data['movementType'], 'amount' => $data['amount'], 'equity_account' => $data['account'], 'treasury_account' => $data['treasuryAccount'], 'description' => $data['description'], 'user_id' => auth()->id()]);
            $contribution = $data['movementType'] === 'contribution';
            $accounting->postOperation($movement, $movement->number, $movement->movement_date, 'OD', 'equity', $movement->description, $contribution ? [['account' => $movement->treasury_account, 'debit' => (float) $movement->amount, 'credit' => 0], ['account' => $movement->equity_account, 'debit' => 0, 'credit' => (float) $movement->amount]] : [['account' => $movement->equity_account, 'debit' => (float) $movement->amount, 'credit' => 0], ['account' => $movement->treasury_account, 'debit' => 0, 'credit' => (float) $movement->amount]]);
        });
    }

    public function approvePurchase(int $id, AccountingService $accounting): void
    {
        abort_unless(auth()->user()?->can('approve operations'), 403, 'Vous ne pouvez pas valider cette opération.');
        DB::transaction(function () use ($id, $accounting) {
            $p = Purchase::lockForUpdate()->findOrFail($id);
            if ($p->status !== 'draft') {
                return;
            } $lines = [['account' => $p->expense_account, 'debit' => (float) $p->subtotal, 'credit' => 0], ['account' => '4011', 'debit' => 0, 'credit' => (float) $p->total_amount]];
            if ((float) $p->tax_amount > 0) {
                $lines[] = ['account' => '4452', 'debit' => (float) $p->tax_amount, 'credit' => 0];
            } $accounting->postOperation($p, $p->number, $p->purchase_date, 'AC', 'purchase', $p->description, $lines);
            $p->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        });
    }

    public function approveDisbursement(int $id): void
    {
        abort_unless(auth()->user()?->can('approve operations'), 403, 'Vous ne pouvez pas valider cette opération.');
        DisbursementRequest::whereKey($id)->where('status', 'pending')->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
    }

    public function payDisbursement(int $id, AccountingService $accounting): void
    {
        abort_unless(auth()->user()?->can('approve operations'), 403, 'Vous ne pouvez pas décaisser cette opération.');
        DB::transaction(function () use ($id, $accounting) {
            $d = DisbursementRequest::lockForUpdate()->findOrFail($id);
            if ($d->status !== 'approved') {
                return;
            } $accounting->postOperation($d, $d->number, now()->toDateString(), 'TR', 'disbursement', $d->purpose, [['account' => $d->expense_account, 'debit' => (float) $d->amount, 'credit' => 0], ['account' => $d->treasury_account, 'debit' => 0, 'credit' => (float) $d->amount]]);
            $d->update(['status' => 'paid', 'paid_at' => now()]);
        });
    }

    public function rejectDisbursement(int $id): void
    {
        abort_unless(auth()->user()?->can('approve operations'), 403, 'Vous ne pouvez pas rejeter cette opération.');
        DisbursementRequest::whereKey($id)->where('status', 'pending')->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
    }

    private function number(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $start = $this->month.'-01';
        $end = Carbon::parse($start)->endOfMonth();
        // La page n'affiche qu'un module : ne charger que son registre actif.
        $data = [
            'expenses' => collect(),
            'purchases' => collect(),
            'disbursements' => collect(),
            'equityMovements' => collect(),
            'appointments' => collect(),
        ];
        $data[match ($this->module) {
            'purchases' => 'purchases',
            'disbursements' => 'disbursements',
            'equity' => 'equityMovements',
            'planning' => 'appointments',
            default => 'expenses',
        }] = match ($this->module) {
            'purchases' => Purchase::with(['user', 'approver'])->whereBetween('purchase_date', [$start, $end])->latest('purchase_date')->get(),
            'disbursements' => DisbursementRequest::with(['requester', 'approver'])->whereBetween('requested_date', [$start, $end])->latest('requested_date')->get(),
            'equity' => EquityMovement::with('user')->whereBetween('movement_date', [$start, $end])->latest('movement_date')->get(),
            'planning' => Appointment::with(['customer', 'employee', 'service'])->whereBetween('date_time', [Carbon::parse($start)->startOfDay(), $end->copy()->endOfDay()])->orderBy('date_time')->get(),
            default => Expense::with('user')->whereBetween('expense_date', [$start, $end])->latest('expense_date')->get(),
        };

        return view('livewire.operations', [
            'meta' => ModuleRegistry::DEFINITIONS[$this->module], 'currency' => Setting::get('company_currency', '$'),
            ...$data,
        ])->layout('layouts.app');
    }
}
