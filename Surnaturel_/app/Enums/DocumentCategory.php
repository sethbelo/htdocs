<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentCategory: string
{
    use HasOptions;

    case Contract = 'contract';
    case Invoice = 'invoice';
    case Hr = 'hr';
    case Artwork = 'artwork';
    case Proof = 'proof';
    case Supplier = 'supplier';
    case Order = 'order';
    case Receipt = 'receipt';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Contrats',
            self::Invoice => 'Factures',
            self::Hr => 'Documents RH',
            self::Artwork => 'Fichiers graphiques',
            self::Proof => 'BAT',
            self::Supplier => 'Fournisseurs',
            self::Order => 'Commandes',
            self::Receipt => 'Justificatifs',
            self::Administrative => 'Documents administratifs',
        };
    }
}
