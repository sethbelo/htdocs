<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CareType: string
{
    use HasOptions;

    case Consultation = 'consultation';
    case Pharmacy = 'pharmacy';
    case Laboratory = 'laboratory';
    case Hospitalization = 'hospitalization';
    case Maternity = 'maternity';
    case Dental = 'dental';
    case Optical = 'optical';

    public function label(): string
    {
        return match ($this) {
            self::Consultation => 'Consultation',
            self::Pharmacy => 'Pharmacie',
            self::Laboratory => 'Laboratoire / examens',
            self::Hospitalization => 'Hospitalisation',
            self::Maternity => 'Maternité',
            self::Dental => 'Soins dentaires',
            self::Optical => 'Optique',
        };
    }
}
