<?php

namespace App\Services\Cash;

use App\Enums\PaymentMethod;
use App\Enums\TreasuryAccountType;
use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\TreasuryAccount;
use App\Models\TreasuryTransfer;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Support\NumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TreasuryService
{
    public function __construct(private NumberGenerator $numbers, private AccountingPoster $poster) {}

    /**
     * Compte de trésorerie qui reçoit un moyen de paiement : la caisse physique pour les espèces,
     * la banque du siège pour carte/virement/chèque, le wallet Mobile Money sinon.
     */
    public function accountFor(PaymentMethod $method, int $siteId, ?CashRegister $register = null): TreasuryAccount
    {
        if ($method === PaymentMethod::Cash && $register?->treasuryAccount) {
            return $register->treasuryAccount;
        }

        $type = match ($method) {
            PaymentMethod::Cash => TreasuryAccountType::Cash,
            PaymentMethod::MobileMoney => TreasuryAccountType::MobileMoney,
            default => TreasuryAccountType::Bank,
        };

        $account = TreasuryAccount::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->where('site_id', $siteId)->orWhereNull('site_id'))
            ->orderByRaw('site_id IS NULL')
            ->first();

        if (! $account) {
            throw new BusinessRuleException("Aucun compte de trésorerie « {$type->label()} » n’est configuré pour ce siège.");
        }

        return $account;
    }

    public function credit(TreasuryAccount $account, float $amount): void
    {
        TreasuryAccount::query()->whereKey($account->id)->increment('balance', round($amount, 2));
    }

    public function debit(TreasuryAccount $account, float $amount): void
    {
        TreasuryAccount::query()->whereKey($account->id)->decrement('balance', round($amount, 2));
    }

    public function transfer(TreasuryAccount $from, TreasuryAccount $to, float $amount, User $user, ?string $reference = null, ?string $description = null): TreasuryTransfer
    {
        if ($from->is($to) || $amount <= 0) {
            throw new BusinessRuleException('Transfert invalide.');
        }

        return DB::transaction(function () use ($from, $to, $amount, $user, $reference, $description): TreasuryTransfer {
            $transfer = TreasuryTransfer::query()->create([
                'number' => $this->numbers->next(NumberGenerator::TreasuryTransfer),
                'from_treasury_account_id' => $from->id,
                'to_treasury_account_id' => $to->id,
                'amount' => $amount,
                'transferred_at' => now(),
                'reference' => $reference,
                'description' => $description,
                'user_id' => $user->id,
            ]);

            $this->debit($from, $amount);
            $this->credit($to, $amount);
            $this->poster->postTreasuryTransfer($transfer->load('from', 'to'));

            return $transfer;
        });
    }
}
