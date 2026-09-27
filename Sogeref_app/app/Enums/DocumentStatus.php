<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon', self::Sent => 'Envoyé', self::Viewed => 'Consulté',
            self::Accepted => 'Accepté', self::Rejected => 'Refusé', self::PartiallyPaid => 'Partiellement payé',
            self::Paid => 'Payé', self::Overdue => 'En retard', self::Cancelled => 'Annulé',
        };
    }
}
