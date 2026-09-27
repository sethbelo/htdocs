<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Les informations privées RH ne sont accessibles qu'aux rôles autorisés.
 */
class EmployeePolicy
{
    public function view(User $user, Employee $employee): bool
    {
        return ($user->can('employees.view') && $user->canAccessSite($employee->site_id)) || $employee->user_id === $user->id;
    }

    public function viewSensitive(User $user, Employee $employee): bool
    {
        return $user->can('employees.view_sensitive') || $employee->user_id === $user->id;
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.update') && $user->canAccessSite($employee->site_id);
    }
}
