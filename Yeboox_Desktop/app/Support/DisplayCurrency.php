<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Règles monétaires uniques de Yeboox.
 *
 * Tous les montants métier sont enregistrés dans la devise de base. La devise
 * secondaire sert exclusivement à l'affichage client (ticket et facture).
 */
final class DisplayCurrency
{
    public const USD = 'USD';

    public const CDF = 'CDF';

    public const XAF = 'XAF';

    public const XOF = 'XOF';

    public const EUR = 'EUR';

    public const SUPPORTED = [self::USD, self::CDF, self::XAF, self::XOF, self::EUR];

    /** @return array<string, array{label: string, symbol: string, decimals: int}> */
    public static function currencies(): array
    {
        return [
            self::USD => ['label' => 'Dollar américain', 'symbol' => '$', 'decimals' => 2],
            self::CDF => ['label' => 'Franc congolais', 'symbol' => 'FC', 'decimals' => 0],
            self::XAF => ['label' => 'Franc CFA (CEMAC)', 'symbol' => 'FCFA', 'decimals' => 0],
            self::XOF => ['label' => 'Franc CFA (UEMOA)', 'symbol' => 'FCFA', 'decimals' => 0],
            self::EUR => ['label' => 'Euro', 'symbol' => '€', 'decimals' => 2],
        ];
    }

    public static function baseCode(): string
    {
        return self::normalize(Setting::get('base_currency', Setting::get('product_price_currency', self::CDF)));
    }

    public static function secondaryCode(): string
    {
        $base = self::baseCode();
        $secondary = self::normalize(Setting::get('secondary_currency', $base === self::USD ? self::CDF : self::USD));

        return $secondary === $base ? self::defaultSecondaryFor($base) : $secondary;
    }

    /** La devise d'interface est toujours la devise comptable de base. */
    public static function code(?string $currency = null): string
    {
        return $currency === null ? self::baseCode() : self::normalize($currency, self::baseCode());
    }

    /** Nombre d'unités secondaires correspondant à une unité de base. */
    public static function rate(): float
    {
        return max(0.00000001, (float) Setting::get('exchange_rate', self::defaultRate(self::baseCode(), self::secondaryCode())));
    }

    /** Convertit un montant stocké en devise de base vers la devise demandée. */
    public static function convert(float|int|string|null $amount, ?string $currency = null): float
    {
        $value = (float) ($amount ?? 0);
        $target = self::code($currency);

        if ($target === self::baseCode()) {
            return $value;
        }

        return $target === self::secondaryCode() ? $value * self::rate() : $value;
    }

    public static function toBase(float|int|string|null $amount, ?string $sourceCurrency = null): float
    {
        $value = (float) ($amount ?? 0);
        $source = self::code($sourceCurrency);

        return $source === self::secondaryCode() ? $value / self::rate() : $value;
    }

    public static function decimals(?string $currency = null): int
    {
        return self::currencies()[self::code($currency)]['decimals'];
    }

    public static function symbol(?string $currency = null): string
    {
        return self::currencies()[self::code($currency)]['symbol'];
    }

    public static function label(?string $currency = null): string
    {
        return self::currencies()[self::code($currency)]['label'];
    }

    public static function format(float|int|string|null $amount, ?string $currency = null, bool $withCode = true): string
    {
        $code = self::code($currency);
        $formatted = number_format(self::convert($amount, $code), self::decimals($code), ',', ' ');

        return $withCode ? $formatted.' '.$code : $formatted;
    }

    /** Affichage client à deux monnaies, sans modifier la comptabilité. */
    public static function formatDual(float|int|string|null $amount, string $separator = ' · '): string
    {
        return self::format($amount, self::baseCode()).$separator.self::format($amount, self::secondaryCode());
    }

    public static function rateLabel(): string
    {
        $raw = number_format(self::rate(), 8, '.', '');
        $trimmed = rtrim(rtrim($raw, '0'), '.');
        $localized = str_replace('.', ',', $trimmed);

        return '1 '.self::baseCode().' = '.$localized.' '.self::secondaryCode();
    }

    public static function defaultSecondaryFor(string $base): string
    {
        return self::normalize($base) === self::USD ? self::CDF : self::USD;
    }

    public static function defaultRate(string $base, string $secondary): float
    {
        $pair = self::normalize($base).'/'.self::normalize($secondary);

        return match ($pair) {
            'USD/CDF' => 2800,
            'CDF/USD' => 1 / 2800,
            'EUR/XAF', 'EUR/XOF' => 655.957,
            'XAF/EUR', 'XOF/EUR' => 1 / 655.957,
            'EUR/USD' => 1.08,
            'USD/EUR' => 1 / 1.08,
            'USD/XAF', 'USD/XOF' => 610,
            'XAF/USD', 'XOF/USD' => 1 / 610,
            default => 1,
        };
    }

    private static function normalize(mixed $currency, string $fallback = self::CDF): string
    {
        $code = strtoupper(trim((string) $currency));
        $aliases = ['$' => self::USD, 'FC' => self::CDF, 'CFA' => self::XAF, '€' => self::EUR];
        $code = $aliases[$code] ?? $code;

        return in_array($code, self::SUPPORTED, true) ? $code : $fallback;
    }
}
