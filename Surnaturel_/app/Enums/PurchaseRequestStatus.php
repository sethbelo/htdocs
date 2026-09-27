<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PurchaseRequestStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Ordered = 'ordered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingApproval => 'En validation',
            self::Approved => 'Validée',
            self::Rejected => 'Refusée',
            self::Ordered => 'Commandée',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::PendingApproval => 'amber',
            self::Approved => 'emerald',
            self::Rejected => 'red',
            self::Ordered => 'indigo',
            self::Cancelled => 'zinc',
        };
    }
}
