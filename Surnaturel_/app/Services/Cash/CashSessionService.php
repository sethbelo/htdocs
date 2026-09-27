<?php

namespace App\Services\Cash;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Setting;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Accounting\AccountingSyncService;
use App\Services\Support\NumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ouverture, mouvements et clôture des caisses (multi-caisses, multi-caissiers).
 */
class CashSessionService
{
    public function __construct(
        private NumberGenerator $numbers,
        private TreasuryService $treasury,
        private AccountingPoster $poster,
        private AccountingSyncService $sync,
    ) {}

    public function open(CashRegister $register, User $cashier, float $openingBalance, ?string $pin = null): CashSession
    {
        if (! $register->is_active) {
            throw new BusinessRuleException('Cette caisse est désactivée.');
        }

        if (! $cashier->canAccessSite($register->site_id)) {
            throw new BusinessRuleException('Vous n’êtes pas rattaché au siège de cette caisse.');
        }

        if ($register->requires_pin && ($pin === null || ! $cashier->checkPin($pin))) {
            throw new BusinessRuleException('PIN caisse invalide.');
        }

        return DB::transaction(function () use ($register, $cashier, $openingBalance): CashSession {
            $registerBusy = CashSession::query()->where('cash_register_id', $register->id)->where('status', CashSessionStatus::Open)->lockForUpdate()->exists();
            if ($registerBusy) {
                throw new BusinessRuleException("La caisse {$register->code} est déjà ouverte.");
            }

            if (CashSession::query()->where('user_id', $cashier->id)->where('status', CashSessionStatus::Open)->exists()) {
                throw new BusinessRuleException('Vous avez déjà une session de caisse ouverte.');
            }

            $session = CashSession::query()->create([
                'number' => $this->numbers->next(NumberGenerator::CashSession),
                'cash_register_id' => $register->id,
                'user_id' => $cashier->id,
                'status' => CashSessionStatus::Open,
                'opened_at' => now(),
                'opening_balance' => $openingBalance,
            ]);

            $session->movements()->create([
                'type' => CashMovementType::Opening,
                'direction' => 'in',
                'amount' => $openingBalance,
                'payment_method' => PaymentMethod::Cash,
                'description' => 'Fond de caisse',
                'user_id' => $cashier->id,
            ]);

            return $session;
        });
    }

    public function record(CashSession $session, CashMovementType $type, string $direction, float $amount, PaymentMethod $method, ?Model $source = null, ?string $description = null, ?User $authorizer = null): CashMovement
    {
        $this->ensureOpen($session);

        return $session->movements()->create([
            'type' => $type,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'payment_method' => $method,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'description' => $description,
            'user_id' => auth()->id() ?? $session->user_id,
            'authorized_by' => $authorizer?->id,
        ]);
    }

    /**
     * Décaissement : au-delà du seuil de la caisse, un responsable doit valider avec son PIN.
     */
    public function disburse(CashSession $session, float $amount, string $description, User $cashier, string $expenseAccount = '605', ?User $supervisor = null, ?string $supervisorPin = null): CashMovement
    {
        $this->ensureOpen($session);
        $register = $session->register;

        if (! $cashier->can('cash.disburse')) {
            throw new BusinessRuleException('Vous n’êtes pas autorisé à décaisser.');
        }

        if ($amount > (float) $register->max_disbursement_without_approval) {
            if (! $supervisor || ! $supervisor->can('cash.approve') || $supervisorPin === null || ! $supervisor->checkPin($supervisorPin)) {
                throw new BusinessRuleException('Ce décaissement nécessite la validation d’un responsable (PIN).');
            }
        }

        if ($amount > $session->theoreticalCashBalance()) {
            throw new BusinessRuleException('Montant supérieur aux espèces disponibles en caisse.');
        }

        return DB::transaction(function () use ($session, $amount, $description, $supervisor, $register, $expenseAccount): CashMovement {
            $movement = $this->record($session, CashMovementType::Disbursement, 'out', $amount, PaymentMethod::Cash, null, $description, $supervisor);

            if ($register->treasuryAccount) {
                $this->treasury->debit($register->treasuryAccount, $amount);
                $this->poster->postCashDisbursement($register->treasuryAccount, $amount, "Décaissement caisse {$register->code} : {$description}", $expenseAccount, $register->site_id);
            }

            return $movement;
        });
    }

    /**
     * Clôture : synchronisation comptable, comptage des espèces (coupures), contrôle de l'écart
     * (validation d'un responsable au-delà du seuil) et versement facultatif au coffre ou en banque.
     *
     * @param  array<string|int, int|string|null>|null  $denominations
     */
    public function close(
        CashSession $session,
        float $countedBalance,
        User $closer,
        ?string $notes = null,
        ?array $denominations = null,
        ?User $supervisor = null,
        ?string $supervisorPin = null,
        float $depositAmount = 0,
        ?TreasuryAccount $depositAccount = null,
    ): CashSession {
        $this->ensureOpen($session);

        if ($closer->id !== $session->user_id && ! $closer->can('cash.approve')) {
            throw new BusinessRuleException('Seul le caissier ou un responsable peut clôturer cette session.');
        }

        $expected = $session->theoreticalCashBalance();
        $difference = round($countedBalance - $expected, 2);
        $tolerance = (float) Setting::get('cash.max_difference_without_approval', 5);

        if (abs($difference) > $tolerance) {
            $validSupervisor = $supervisor
                && $supervisor->id !== $session->user_id
                && $supervisor->can('cash.approve')
                && $supervisorPin !== null
                && $supervisor->checkPin($supervisorPin);

            if (! $validSupervisor) {
                throw new BusinessRuleException('Écart de caisse de '.number_format($difference, 2, ',', ' ').' : la clôture doit être validée par un responsable (PIN).');
            }
        }

        if ($depositAmount > 0) {
            if (! $depositAccount || ! $session->register->treasuryAccount || $depositAccount->is($session->register->treasuryAccount)) {
                throw new BusinessRuleException('Choisissez un compte de destination valide pour le versement.');
            }

            if ($depositAmount > $countedBalance + 0.001) {
                throw new BusinessRuleException('Le versement ne peut pas dépasser les espèces comptées.');
            }
        }

        return DB::transaction(function () use ($session, $countedBalance, $closer, $notes, $denominations, $supervisor, $depositAmount, $depositAccount, $expected, $difference): CashSession {
            $this->sync->sync($session);

            $session->update([
                'status' => CashSessionStatus::Closed,
                'closed_at' => now(),
                'accounting_synced_at' => now(),
                'expected_balance' => $expected,
                'counted_balance' => $countedBalance,
                'denominations' => $denominations ? array_filter($denominations, fn ($count): bool => (int) $count > 0) : null,
                'difference' => $difference,
                'closed_by' => $closer->id,
                'supervisor_id' => abs($difference) > 0 ? $supervisor?->id : null,
                'deposit_amount' => $depositAmount,
                'deposit_treasury_account_id' => $depositAmount > 0 ? $depositAccount?->id : null,
                'notes' => $notes,
            ]);

            $treasury = $session->register->treasuryAccount;
            if ($difference != 0.0 && $treasury) {
                $difference > 0 ? $this->treasury->credit($treasury, $difference) : $this->treasury->debit($treasury, abs($difference));
                $this->poster->postCashDifference($treasury, $difference, "Écart de caisse {$session->number}", $session->register->site_id);
            }

            if ($depositAmount > 0 && $treasury) {
                $this->treasury->transfer($treasury, $depositAccount, $depositAmount, $closer, $session->number, "Versement de clôture {$session->register->code}");
            }

            return $session;
        });
    }

    public function currentFor(User $user): ?CashSession
    {
        return CashSession::query()->with('register.site', 'register.warehouse', 'register.treasuryAccount')
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Open)
            ->latest('opened_at')
            ->first();
    }

    private function ensureOpen(CashSession $session): void
    {
        if (! $session->isOpen()) {
            throw new BusinessRuleException('La session de caisse est clôturée.');
        }
    }
}
