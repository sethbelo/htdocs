<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use DomainException;

class ExchangeRateService
{
    public function rate(int $companyId, string $from, string $to, CarbonInterface|string $date): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rate = ExchangeRate::query()
            ->where('company_id', $companyId)
            ->where('base_currency', $from)
            ->where('quote_currency', $to)
            ->whereDate('effective_date', '<=', $date)
            ->latest('effective_date')
            ->value('rate');

        if ($rate === null) {
            throw new DomainException("Taux de change indisponible pour {$from}/{$to}.");
        }

        return (float) $rate;
    }

    public function convert(float $amount, float $historicalRate): float
    {
        return round($amount * $historicalRate, 2);
    }
}
