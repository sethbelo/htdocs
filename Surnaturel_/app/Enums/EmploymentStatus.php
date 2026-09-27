<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EmploymentStatus: string
{
    use HasOptions;

    case Permanent = 'permanent';
    case Daily = 'daily';
    case Contract = 'contract';
    case Contractor = 'contractor';
    case Intern = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Permanent',
            self::Daily => 'Journalier',
            self::Contract => 'Contractuel',
            self::Contractor => 'Prestataire',
            self::Intern => 'Stagiaire',
        };
    }
}
