<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LeaveStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case ManagerApproved = 'manager_approved';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente responsable',
            self::ManagerApproved => 'En attente RH',
            self::Approved => 'Validé',
            self::Rejected => 'Refusé',
            self::Cancelled => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::ManagerApproved => 'sky',
            self::Approved => 'emerald',
            self::Rejected => 'red',
            self::Cancelled => 'zinc',
        };
    }
}
