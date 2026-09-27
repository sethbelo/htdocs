<?php

namespace App\Support;

use App\Models\Setting;

/** Centralise les règles du programme de fidélité utilisées par le POS et les clients. */
final class LoyaltyProgram
{
    public static function enabled(): bool
    {
        return ModuleRegistry::enabled('loyalty');
    }

    public static function mode(): string
    {
        $mode = (string) Setting::get('loyalty_mode', 'points');

        return in_array($mode, ['points', 'services'], true) ? $mode : 'points';
    }

    public static function usesPoints(): bool
    {
        return self::enabled() && self::mode() === 'points';
    }

    public static function usesServices(): bool
    {
        return self::enabled() && self::mode() === 'services';
    }

    /** Valeur monétaire d'un point dans la devise comptable de base. */
    public static function pointValue(): float
    {
        return max(0.0001, (float) Setting::get('loyalty_point_value', Setting::get('loyalty_point_value_usd', 0.02)));
    }

    /** @deprecated Utiliser pointValue(); conservé pour les anciennes intégrations. */
    public static function pointValueUsd(): float
    {
        return self::pointValue();
    }

    public static function pointsValue(int $points): float
    {
        return round(max(0, $points) * self::pointValue(), 8);
    }

    public static function pointsRequired(float $amount): int
    {
        return (int) ceil(max(0, $amount) / self::pointValue());
    }

    /** Nombre de prestations payées donnant droit à une carte cadeau offerte. */
    public static function requiredServices(): int
    {
        return max(1, (int) Setting::get('loyalty_required_services', 9));
    }

    public static function rewardProductId(): ?int
    {
        $id = (int) Setting::get('loyalty_reward_product_id', 0);

        return $id > 0 ? $id : null;
    }
}
