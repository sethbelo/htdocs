<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use DomainException;
use Illuminate\Database\Eloquent\Model;

/**
 * Programmes de fidélité B2B / B2C : cumul de points, paliers et remises.
 */
class LoyaltyService
{
    public function __construct(private readonly SequenceService $sequences) {}

    public function programFor(Contact $contact): ?LoyaltyProgram
    {
        return LoyaltyProgram::where('company_id', $contact->company_id)->where('is_active', true)
            ->whereIn('segment', ['all', $contact->segment ?? 'b2c'])
            ->orderByRaw("segment = 'all'")->first();
    }

    public function cardFor(Contact $contact, bool $create = true): ?LoyaltyCard
    {
        if ($card = $contact->loyaltyCard()->first()) {
            return $card;
        }
        if (! $create || ! ($program = $this->programFor($contact))) {
            return null;
        }

        return LoyaltyCard::create([
            'company_id' => $contact->company_id,
            'loyalty_program_id' => $program->id,
            'contact_id' => $contact->id,
            'card_number' => $this->sequences->next('loyalty_card', $contact->company_id, yearly: false),
            'issued_at' => now()->toDateString(),
            'tier' => $program->tierFor(0)['name'] ?? null,
        ]);
    }

    public function earn(LoyaltyCard $card, float $amount, ?Model $source = null): int
    {
        $program = $card->program;
        $points = (int) floor($amount * (float) $program->points_per_unit);
        $spent = (float) $card->total_spent + $amount;
        $card->update(['points' => $card->points + $points, 'total_spent' => $spent, 'tier' => $program->tierFor($spent)['name'] ?? $card->tier]);
        if ($points > 0) {
            $card->transactions()->create(['type' => 'earn', 'points' => $points, 'source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey(), 'note' => 'Achat de '.money($amount)]);
        }

        return $points;
    }

    /** Convertit des points en remise (montant retourné en USD). */
    public function redeem(LoyaltyCard $card, int $points, ?Model $source = null): float
    {
        $program = $card->program;
        if ($points < $program->min_redeem_points) {
            throw new DomainException("Minimum {$program->min_redeem_points} points pour une conversion.");
        }
        if ($points > $card->points) {
            throw new DomainException('Solde de points insuffisant.');
        }
        $card->decrement('points', $points);
        $card->transactions()->create(['type' => 'redeem', 'points' => -$points, 'source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey(), 'note' => 'Conversion en remise']);

        return round($points * (float) $program->point_value, 2);
    }

    public function tierDiscount(LoyaltyCard $card): float
    {
        return (float) (collect($card->program->tiers ?? [])->firstWhere('name', $card->tier)['discount'] ?? 0);
    }
}
