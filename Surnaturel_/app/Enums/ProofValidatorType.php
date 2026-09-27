<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProofValidatorType: string
{
    use HasOptions;

    case Client = 'client';
    case SalesRep = 'sales_rep';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::SalesRep => 'Commercial',
            self::Manager => 'Responsable autorisé',
        };
    }
}
