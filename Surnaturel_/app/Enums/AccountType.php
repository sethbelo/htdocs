<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AccountType: string
{
    use HasOptions;

    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Actif',
            self::Liability => 'Passif',
            self::Equity => 'Capitaux propres',
            self::Income => 'Produit',
            self::Expense => 'Charge',
        };
    }
}
