<?php

namespace App\Livewire\Forms;

use App\Support\Tenant;

/** @deprecated Utiliser App\Support\Tenant ou company_id(). Conservé pour compatibilité. */
final class CompanyContext
{
    public static function id(): int
    {
        return Tenant::id();
    }
}
