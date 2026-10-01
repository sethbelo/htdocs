<?php

namespace App\Enums;

enum ContactType: string
{
    case Client = 'client';
    case Supplier = 'supplier';
    case Prospect = 'prospect';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Supplier => 'Fournisseur',
            self::Prospect => 'Prospect',
            self::Partner => 'Partenaire',
        };
    }

    /** [valeur => libellé] pour les listes déroulantes. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
