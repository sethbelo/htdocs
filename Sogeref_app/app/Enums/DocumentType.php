<?php

namespace App\Enums;

enum DocumentType: string
{
    case Quote = 'quote';
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case PurchaseOrder = 'purchase_order';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Devis',
            self::Invoice => 'Facture',
            self::CreditNote => 'Avoir',
            self::PurchaseOrder => 'Bon de commande',
        };
    }
}
