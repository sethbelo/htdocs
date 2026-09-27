<?php

namespace App\Services\Crm;

use App\Enums\LoyaltyTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use App\Models\Setting;
use App\Services\Support\NumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function __construct(private NumberGenerator $numbers) {}

    public function accountFor(Client $client): LoyaltyAccount
    {
        $account = $client->loyaltyAccount()->first() ?? $client->loyaltyAccount()->create([
            'card_number' => $this->numbers->next(NumberGenerator::LoyaltyCard, padding: 8),
            'loyalty_tier_id' => LoyaltyTier::query()->orderBy('min_points')->value('id'),
            'expires_at' => ($years = Setting::get('loyalty.card_validity_years')) ? now()->addYears((int) $years) : null,
        ]);

        $client->setRelation('loyaltyAccount', $account);

        return $account;
    }

    /**
     * Points gagnés = montant / montant-par-point × multiplicateur du niveau.
     */
    public function pointsFor(LoyaltyAccount $account, float $amount): int
    {
        $amountPerPoint = max(1, (float) Setting::get('loyalty.amount_per_point', 1000));
        $multiplier = (float) ($account->tier?->points_multiplier ?? 1);

        return (int) floor($amount / $amountPerPoint * $multiplier);
    }

    public function earn(Client $client, float $amount, ?Model $source = null): int
    {
        if (! Setting::get('loyalty.enabled', true)) {
            return 0;
        }

        $account = $this->accountFor($client);
        $points = $this->pointsFor($account, $amount);

        if ($points > 0) {
            $this->record($account, LoyaltyTransactionType::Earn, $points, $source, 'Points gagnés');
            $account->increment('lifetime_points', $points);
            $this->refreshTier($account);
        }

        return $points;
    }

    /**
     * Utilisation de points : retourne la valeur monétaire de la remise.
     */
    public function redeem(Client $client, int $points, ?Model $source = null): float
    {
        $account = $this->accountFor($client);

        if ($points <= 0 || $points > $account->points_balance) {
            throw new BusinessRuleException('Solde de points insuffisant.');
        }

        $this->record($account, LoyaltyTransactionType::Redeem, -$points, $source, 'Points utilisés');

        return round($points * (float) Setting::get('loyalty.point_value', 10), 2);
    }

    private function record(LoyaltyAccount $account, LoyaltyTransactionType $type, int $points, ?Model $source, string $description): void
    {
        DB::transaction(function () use ($account, $type, $points, $source, $description): void {
            $account->refresh();
            $account->increment('points_balance', $points);

            $account->transactions()->create([
                'type' => $type,
                'points' => $points,
                'balance_after' => $account->points_balance,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'description' => $description,
                'user_id' => auth()->id(),
            ]);
        });
    }

    private function refreshTier(LoyaltyAccount $account): void
    {
        $tier = LoyaltyTier::query()->where('min_points', '<=', $account->lifetime_points)->orderByDesc('min_points')->first();

        if ($tier && $tier->id !== $account->loyalty_tier_id) {
            $account->update(['loyalty_tier_id' => $tier->id]);
        }
    }
}
