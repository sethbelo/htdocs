<?php

namespace App\Services\Finance;

use App\Enums\DocumentCategory;
use App\Enums\ExpenseClaimStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\ExpenseClaim;
use App\Models\Payment;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Approvals\ApprovalService;
use App\Services\Cash\TreasuryService;
use App\Services\Documents\DocumentService;
use App\Services\Support\NumberGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Notes de frais : Employé → Responsable → Finance → Paiement (justificatifs en GED).
 */
class ExpenseService
{
    public function __construct(
        private NumberGenerator $numbers,
        private ApprovalService $approvals,
        private DocumentService $documents,
        private TreasuryService $treasury,
        private AccountingPoster $poster,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, amount: float|string, expense_date: string}  $data
     * @param  list<UploadedFile>  $receipts
     */
    public function submit(User $user, array $data, array $receipts = []): ExpenseClaim
    {
        return DB::transaction(function () use ($user, $data, $receipts): ExpenseClaim {
            $claim = ExpenseClaim::query()->create($data + [
                'number' => $this->numbers->next(NumberGenerator::ExpenseClaim),
                'user_id' => $user->id,
                'site_id' => $user->default_site_id,
                'department_id' => $user->employee?->department_id,
                'status' => ExpenseClaimStatus::Submitted,
            ]);

            foreach ($receipts as $receipt) {
                $this->documents->store($receipt, DocumentCategory::Receipt, $user, "Justificatif {$claim->number}", $claim, siteId: $claim->site_id);
            }

            $this->approvals->submit($claim, $user);

            return $claim;
        });
    }

    public function pay(ExpenseClaim $claim, TreasuryAccount $account, PaymentMethod $method, User $user, ?string $reference = null): Payment
    {
        if ($claim->status !== ExpenseClaimStatus::FinanceApproved) {
            throw new BusinessRuleException('La note de frais doit être validée par la finance avant paiement.');
        }

        if (! $user->can('expenses.disburse') && ! $user->can('treasury.disburse')) {
            throw new BusinessRuleException('Vous n’êtes pas autorisé à payer.');
        }

        return DB::transaction(function () use ($claim, $account, $method, $user, $reference): Payment {
            $payment = Payment::query()->create([
                'number' => $this->numbers->next(NumberGenerator::Payment),
                'batch' => (string) Str::uuid(),
                'direction' => 'out',
                'payable_type' => $claim->getMorphClass(),
                'payable_id' => $claim->id,
                'method' => $method,
                'amount' => $claim->amount,
                'reference' => $reference,
                'treasury_account_id' => $account->id,
                'site_id' => $claim->site_id,
                'received_by' => $user->id,
                'paid_at' => now(),
            ]);

            $this->treasury->debit($account, (float) $claim->amount);
            $this->poster->postExpenseClaimPayment($claim, $payment->setRelation('treasuryAccount', $account));
            $claim->update(['status' => ExpenseClaimStatus::Paid, 'payment_id' => $payment->id, 'paid_at' => now()]);

            return $payment;
        });
    }
}
