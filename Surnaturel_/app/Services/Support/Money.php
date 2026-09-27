<?php

namespace App\Services\Support;

use App\Models\Setting;

class Money
{
    public static function currency(): string
    {
        return (string) Setting::get('company.currency', 'CDF');
    }

    public static function format(float|int|string|null $amount, ?string $currency = null): string
    {
        $decimals = (int) Setting::get('company.currency_decimals', 2);

        return number_format((float) $amount, $decimals, ',', ' ').' '.($currency ?? self::currency());
    }

    /**
     * Montant en toutes lettres (factures, bons de caisse) : « mille trois cent trente-six dollars et quatre-vingt-dix cents ».
     */
    public static function inWords(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency ??= self::currency();
        $amount = round((float) $amount, 2);
        $units = (int) floor($amount);
        $cents = (int) round(($amount - $units) * 100);
        $speller = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
        [$major, $minor] = match ($currency) {
            'USD' => ['dollar', 'cent'],
            'EUR' => ['euro', 'centime'],
            'CDF' => ['franc congolais', 'centime'],
            default => [$currency, 'centime'],
        };
        $plural = fn (string $word, int $n): string => $n > 1 ? implode(' ', array_map(fn (string $w): string => preg_match('/[sx]$/u', $w) ? $w : $w.'s', explode(' ', $word))) : $word;

        $text = $speller->format($units).' '.$plural($major, $units);
        if ($cents > 0) {
            $text .= ' et '.$speller->format($cents).' '.$plural($minor, $cents);
        }

        return ucfirst($text);
    }
}
