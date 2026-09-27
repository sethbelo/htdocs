<?php

namespace App\Support;

/**
 * Montant en toutes lettres (français) pour les factures :
 * 1 250,50 USD → « mille deux cent cinquante dollars américains et cinquante cents ».
 */
final class AmountInWords
{
    private const UNITS = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];

    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante', 8 => 'quatre-vingt'];

    private const CURRENCIES = ['USD' => ['dollar américain', 'dollars américains', 'cent', 'cents'], 'CDF' => ['franc congolais', 'francs congolais', 'centime', 'centimes'], 'EUR' => ['euro', 'euros', 'centime', 'centimes']];

    public static function make(float $amount, string $currency = 'USD'): string
    {
        [$one, $many, $subOne, $subMany] = self::CURRENCIES[$currency] ?? [$currency, $currency, 'centième', 'centièmes'];
        $integer = (int) floor($amount + 1e-9);
        $cents = (int) round(($amount - $integer) * 100);

        // « deux millions DE dollars » : million / milliard suivis directement du nom.
        $de = $integer >= 1_000_000 && $integer % 1_000_000 === 0 ? ' de' : '';
        $text = self::number($integer).$de.' '.($integer > 1 ? $many : $one);
        if ($cents > 0) {
            $text .= ' et '.self::number($cents).' '.($cents > 1 ? $subMany : $subOne);
        }

        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }

    public static function number(int $n): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }
        if ($n < 100) {
            return self::tens($n);
        }
        if ($n < 1000) {
            $h = intdiv($n, 100);
            $r = $n % 100;
            $prefix = $h === 1 ? 'cent' : self::UNITS[$h].' cent'.($r === 0 ? 's' : '');

            return $r ? $prefix.' '.self::number($r) : $prefix;
        }
        foreach ([1_000_000_000 => ['milliard', 'milliards'], 1_000_000 => ['million', 'millions'], 1000 => ['mille', 'mille']] as $scale => [$single, $plural]) {
            if ($n >= $scale) {
                $q = intdiv($n, $scale);
                $r = $n % $scale;
                $head = $scale === 1000
                    ? ($q === 1 ? 'mille' : rtrim(self::number($q), 's').' mille')
                    : self::number($q).' '.($q > 1 ? $plural : $single);

                return $r ? $head.' '.self::number($r) : $head;
            }
        }

        return (string) $n;
    }

    private static function tens(int $n): string
    {
        $t = intdiv($n, 10);
        $u = $n % 10;
        if ($t === 7 || $t === 9) {
            $base = $t === 7 ? 'soixante' : 'quatre-vingt';

            return $base.($t === 7 && $u === 1 ? ' et ' : '-').self::UNITS[10 + $u];
        }
        $word = self::TENS[$t];
        if ($u === 0) {
            return $t === 8 ? 'quatre-vingts' : $word;
        }

        return $word.($u === 1 && $t !== 8 ? ' et un' : '-'.self::UNITS[$u]);
    }
}
