<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Support\DisplayCurrency;
use Database\Seeders\SystemSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FirstRunSetup extends Component
{
    public int $welcomeStep = 0;

    public function nextWelcome(): void
    {
        $this->welcomeStep = min(5, max(0, $this->welcomeStep) + 1);
    }

    public function previousWelcome(): void
    {
        $this->welcomeStep = max(0, min(5, $this->welcomeStep) - 1);
    }

    public string $adminName = '';

    public string $adminEmail = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $baseCurrency = DisplayCurrency::CDF;

    public string $secondaryCurrency = DisplayCurrency::USD;

    public string $exchangeRate = '0.00035714';

    public function updatedBaseCurrency(string $currency): void
    {
        if (! in_array($currency, DisplayCurrency::SUPPORTED, true)) {
            return;
        }

        if ($this->secondaryCurrency === $currency) {
            $this->secondaryCurrency = DisplayCurrency::defaultSecondaryFor($currency);
        }
        $this->refreshDefaultRate();
    }

    public function updatedSecondaryCurrency(string $currency): void
    {
        if ($currency === $this->baseCurrency || ! in_array($currency, DisplayCurrency::SUPPORTED, true)) {
            $this->secondaryCurrency = DisplayCurrency::defaultSecondaryFor($this->baseCurrency);
        }
        $this->refreshDefaultRate();
    }

    public function mount(): void
    {
        if (User::query()->exists()) {
            $this->redirect(Auth::check() ? route('dashboard') : route('login'), navigate: true);
        }
    }

    public function complete()
    {
        if (User::query()->exists()) {
            return $this->redirect(Auth::check() ? route('dashboard') : route('login'), navigate: true);
        }

        if ($this->welcomeStep !== 5) {
            $this->addError('welcomeStep', 'Découvrez les cinq écrans de bienvenue avant de créer votre administrateur.');
            return;
        }

        $this->validate([
            'adminName' => ['required', 'string', 'max:120'],
            'adminEmail' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'baseCurrency' => ['required', Rule::in(DisplayCurrency::SUPPORTED)],
            'secondaryCurrency' => ['required', Rule::in(DisplayCurrency::SUPPORTED), 'different:baseCurrency'],
            'exchangeRate' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
        ], [
            'secondaryCurrency.different' => 'La devise secondaire doit être différente de la devise de base.',
            'exchangeRate.gt' => 'Le taux de change doit être supérieur à zéro.',
        ]);

        $admin = DB::transaction(function () {
            app(SystemSeeder::class)->run();

            $company = Company::query()->with('pointsOfSale')->first();
            $point = $company?->pointsOfSale->first();

            $company?->update([
                'base_currency' => $this->baseCurrency,
                'secondary_currency' => $this->secondaryCurrency,
                'exchange_rate' => (float) $this->exchangeRate,
            ]);
            $point?->update(['default_currency' => $this->baseCurrency]);

            // Référentiels minimums nécessaires au premier article. Aucun
            // produit, client, rendez-vous ou mouvement démo n'est créé.
            ProductCategory::firstOrCreate(
                ['code' => 'GENERAL'],
                ['name' => 'Général', 'is_active' => true],
            );
            UnitOfMeasure::firstOrCreate(
                ['symbol' => 'u'],
                ['name' => 'Unité', 'type' => 'unit', 'is_active' => true],
            );

            $admin = User::create([
                'company_id' => $company?->id,
                'current_establishment_id' => $point?->establishment_id,
                'current_point_of_sale_id' => $point?->id,
                'name' => $this->adminName,
                'email' => strtolower($this->adminEmail),
                'password' => Hash::make($this->password),
                'is_master_admin' => true,
                'is_company_admin' => true,
                'is_active' => true,
            ]);
            $admin->email_verified_at = now();
            $admin->save();
            $admin->assignRole('Super administrateur');
            if ($point) {
                $admin->pointsOfSale()->syncWithoutDetaching([
                    $point->id => ['access_level' => 'administrator'],
                ]);
            }

            Setting::set('database_mode', 'production');
            Setting::set('base_currency', $this->baseCurrency);
            Setting::set('company_currency', $this->baseCurrency);
            Setting::set('product_price_currency', $this->baseCurrency);
            Setting::set('secondary_currency', $this->secondaryCurrency);
            Setting::set('exchange_rate', (string) (float) $this->exchangeRate);
            Setting::set('currency_operation', 'base_to_secondary');
            Setting::set('setup_completed', '1');

            return $admin;
        });

        Auth::login($admin, true);
        session()->regenerate();

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.first-run-setup', [
            'currencies' => DisplayCurrency::currencies(),
        ])->layout('layouts.guest');
    }

    private function refreshDefaultRate(): void
    {
        $this->exchangeRate = rtrim(rtrim(number_format(
            DisplayCurrency::defaultRate($this->baseCurrency, $this->secondaryCurrency),
            8,
            '.',
            '',
        ), '0'), '.');
    }
}
