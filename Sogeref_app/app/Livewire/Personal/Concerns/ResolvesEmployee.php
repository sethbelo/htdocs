<?php

namespace App\Livewire\Personal\Concerns;

use App\Models\Employee;

/**
 * Fiche employé liée au compte connecté (employees.user_id) dans la société courante.
 * Un utilisateur sans fiche (ex. compte Master) reste pris en charge : null.
 */
trait ResolvesEmployee
{
    protected function employee(): ?Employee
    {
        return Employee::where('company_id', company_id())->where('user_id', auth()->id())->first();
    }
}
