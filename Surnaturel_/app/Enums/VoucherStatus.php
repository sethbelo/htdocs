<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VoucherStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Executed = 'executed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingApproval => 'En validation',
            self::Approved => 'Approuvé',
            self::Rejected => 'Refusé',
            self::Executed => 'Exécuté',
            self::Cancelled => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::PendingApproval => 'amber',
            self::Approved => 'emerald',
            self::Rejected => 'red',
            self::Executed => 'indigo',
            self::Cancelled => 'zinc',
        };
    }
}
