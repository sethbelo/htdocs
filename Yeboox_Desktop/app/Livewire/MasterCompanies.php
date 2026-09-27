<?php

namespace App\Livewire;

use App\Models\Company;
use App\Services\SaasProvisioner;
use App\Support\DisplayCurrency;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Console plateforme réservée à l'administrateur master. */
#[Layout('layouts.app')]
class MasterCompanies extends Component
{
    use WithPagination;

    public bool $showCreate = false;

    public string $name = '';

    public string $legalName = '';

    public string $email = '';

    public string $phone = '';

    public string $baseCurrency = 'USD';

    public string $secondaryCurrency = 'CDF';

    public float $exchangeRate = 2800;

    public string $administratorName = '';

    public string $administratorEmail = '';

    public string $administratorPassword = '';

    public string $administratorPasswordConfirmation = '';

    public string $planCode = 'professional';

    public int $subscriptionDays = 30;

    public function createCompany(SaasProvisioner $provisioner): void
    {
        abort_unless(auth()->user()?->is_master_admin, 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:190'],
            'legalName' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'baseCurrency' => ['required', Rule::in(DisplayCurrency::SUPPORTED)],
            'secondaryCurrency' => ['required', Rule::in(DisplayCurrency::SUPPORTED), 'different:baseCurrency'],
            'exchangeRate' => ['required', 'numeric', 'min:0.000001'],
            'administratorName' => ['required', 'string', 'max:190'],
            'administratorEmail' => ['required', 'email', 'max:190', 'unique:users,email'],
            'administratorPassword' => ['required', 'same:administratorPasswordConfirmation', Password::min(12)->letters()->numbers()],
            'subscriptionDays' => ['required', 'integer', 'min:1', 'max:3660'],
            'planCode' => ['required', Rule::in(['starter', 'professional', 'enterprise'])],
        ], attributes: [
            'administratorPassword' => 'mot de passe administrateur',
            'administratorPasswordConfirmation' => 'confirmation du mot de passe',
        ]);

        $provisioner->createCompany([
            'name' => $data['name'],
            'legal_name' => $data['legalName'] ?: $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'base_currency' => $data['baseCurrency'],
            'secondary_currency' => $data['secondaryCurrency'],
            'exchange_rate' => $data['exchangeRate'],
            'administrator_name' => $data['administratorName'],
            'administrator_email' => $data['administratorEmail'],
            'administrator_password' => $data['administratorPassword'],
            'plan_code' => $data['planCode'],
            'subscription_days' => $data['subscriptionDays'],
        ]);

        $this->reset([
            'showCreate', 'name', 'legalName', 'email', 'phone',
            'administratorName', 'administratorEmail', 'administratorPassword',
            'administratorPasswordConfirmation', 'secondaryCurrency',
        ]);
        $this->secondaryCurrency = DisplayCurrency::defaultSecondaryFor($this->baseCurrency);
        session()->flash('success', 'Entreprise, établissement, point de vente et administrateur créés.');
        $this->resetPage();
    }

    public function toggleStatus(int $companyId): void
    {
        abort_unless(auth()->user()?->is_master_admin, 403);
        $company = Company::query()->findOrFail($companyId);
        $company->update(['status' => $company->status === 'active' ? 'suspended' : 'active']);
    }

    public function render()
    {
        return view('livewire.master-companies', [
            'companies' => Company::query()
                ->withCount(['establishments', 'pointsOfSale', 'users'])
                ->with('pointsOfSale')
                ->latest()
                ->paginate(12),
        ]);
    }
}
