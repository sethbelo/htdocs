<?php

namespace App\Services;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Models\Expense;
use App\Models\ExpenseClaim;
use App\Models\FinancialAccount;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Notes de frais : soumission → approbation → remboursement (dépense
 * comptabilisée automatiquement sur le compte de trésorerie choisi).
 */
class ExpenseClaimService
{
    public function __construct(private readonly SynchronizeAccounting $accounting) {}

    public function submit(ExpenseClaim $claim): void
    {
        if (! in_array($claim->status, ['draft', 'rejected'], true) || $claim->lines()->doesntExist()) {
            throw new DomainException('Ajoutez au moins une dépense avant de soumettre.');
        }
        $claim->update(['status' => 'submitted', 'total' => $claim->lines()->sum('amount')]);
    }

    public function decide(ExpenseClaim $claim, bool $approve, ?string $comment = null): void
    {
        if ($claim->status !== 'submitted') {
            throw new DomainException('Seule une note soumise peut être approuvée ou refusée.');
        }
        $claim->update(['status' => $approve ? 'approved' : 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now(), 'comment' => $comment]);
    }

    public function reimburse(ExpenseClaim $claim, int $financialAccountId): Expense
    {
        if ($claim->status !== 'approved') {
            throw new DomainException('La note doit être approuvée avant remboursement.');
        }

        return DB::transaction(function () use ($claim, $financialAccountId) {
            $account = FinancialAccount::findOrFail($financialAccountId);
            $expense = Expense::create([
                'company_id' => $claim->company_id,
                'created_by' => auth()->id(),
                'financial_account_id' => $account->id,
                'reference' => $claim->number,
                'account_code' => '638',
                'description' => 'Note de frais '.$claim->number.' · '.($claim->employee?->full_name ?? $claim->user?->name),
                'amount' => $claim->total,
                'tax_amount' => 0,
                'currency_code' => $claim->currency_code,
                'exchange_rate' => 1,
                'expense_date' => now()->toDateString(),
                'status' => 'approved',
                'payment_method' => $account->type === 'mobile_money' ? 'mobile_money' : ($account->type === 'cash' ? 'cash' : 'bank_transfer'),
            ]);
            $this->accounting->expense($expense);
            $claim->update(['status' => 'paid', 'paid_at' => now(), 'expense_id' => $expense->id]);

            return $expense;
        });
    }
}
