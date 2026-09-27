<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Account;
use App\Models\Setting;
use App\Services\Accounting\AccountMap;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Paramètres — Entreprise')]
class Company extends Component
{
    use InteractsWithToasts, WithFileUploads;

    /** @var array<string, mixed> */
    public array $company = [];

    /** @var array<string, string|null> */
    public array $socials = [];

    /** @var array<string, mixed> */
    public array $operations = [];

    /** @var array<string, string> */
    public array $accounting = [];

    public $logo = null;

    public $favicon = null;

    /**
     * @var list<string>
     */
    private const COMPANY_KEYS = ['name', 'legal_name', 'rccm', 'id_nat', 'nif', 'address', 'city', 'country', 'phone', 'email', 'website', 'currency', 'currency_decimals', 'default_tax_rate', 'ticket_footer'];

    public function mount(): void
    {
        foreach (self::COMPANY_KEYS as $key) {
            $this->company[$key] = Setting::get("company.{$key}");
        }

        $this->socials = array_merge(['facebook' => null, 'instagram' => null, 'linkedin' => null, 'whatsapp' => null, 'tiktok' => null], (array) Setting::get('company.socials', []));
        $this->operations = [
            'default_deposit_rate' => Setting::get('orders.default_deposit_rate', 100),
            'allow_negative_stock' => (bool) Setting::get('inventory.allow_negative_stock', false),
            'work_start' => Setting::get('hr.work_start', '08:00'),
            'daily_hours' => Setting::get('hr.daily_hours', 8),
            'late_tolerance_minutes' => Setting::get('hr.late_tolerance_minutes', 10),
            'working_days_per_month' => Setting::get('hr.working_days_per_month', 26),
            'overtime_rate' => Setting::get('hr.overtime_rate', 1.3),
        ];
        $this->accounting = collect(AccountMap::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => (string) Setting::get("accounting.{$key}", $default)])->all();
    }

    public function save(): void
    {
        $this->authorize('settings.update');

        $this->validate([
            'company.name' => ['required', 'string', 'max:255'],
            'company.legal_name' => ['nullable', 'string', 'max:255'],
            'company.rccm' => ['nullable', 'string', 'max:100'],
            'company.id_nat' => ['nullable', 'string', 'max:100'],
            'company.nif' => ['nullable', 'string', 'max:100'],
            'company.email' => ['nullable', 'email'],
            'company.website' => ['nullable', 'url'],
            'company.currency' => ['required', 'string', 'size:3'],
            'company.currency_decimals' => ['required', 'integer', 'between:0,3'],
            'company.default_tax_rate' => ['required', 'numeric', 'between:0,100'],
            'socials.*' => ['nullable', 'string', 'max:255'],
            'operations.default_deposit_rate' => ['required', 'numeric', 'between:0,100'],
            'operations.work_start' => ['required', 'date_format:H:i'],
            'operations.daily_hours' => ['required', 'numeric', 'between:1,24'],
            'operations.late_tolerance_minutes' => ['required', 'integer', 'min:0'],
            'operations.working_days_per_month' => ['required', 'integer', 'between:1,31'],
            'operations.overtime_rate' => ['required', 'numeric', 'min:1'],
            'accounting.*' => ['required', 'exists:accounts,code'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],
        ], [], ['company.name' => 'raison sociale', 'accounting.*' => 'compte comptable']);

        foreach ($this->company as $key => $value) {
            Setting::put("company.{$key}", $value === '' ? null : (in_array($key, ['currency_decimals'], true) ? (int) $value : ($key === 'default_tax_rate' ? (float) $value : $value)));
        }

        Setting::put('company.socials', $this->socials);
        Setting::put('orders.default_deposit_rate', (float) $this->operations['default_deposit_rate']);
        Setting::put('inventory.allow_negative_stock', (bool) $this->operations['allow_negative_stock']);

        foreach (['work_start', 'daily_hours', 'late_tolerance_minutes', 'working_days_per_month', 'overtime_rate'] as $key) {
            Setting::put("hr.{$key}", $key === 'work_start' ? $this->operations[$key] : (float) $this->operations[$key]);
        }

        foreach ($this->accounting as $key => $code) {
            Setting::put("accounting.{$key}", $code);
        }

        if ($this->logo) {
            Setting::put('company.logo_path', $this->logo->store('branding', 'public'));
        }

        if ($this->favicon) {
            Setting::put('company.favicon_path', $this->favicon->store('branding', 'public'));
        }

        $this->reset('logo', 'favicon');
        $this->toast('Paramètres enregistrés.');
    }

    public function render()
    {
        return view('livewire.settings.company', [
            'accountLabels' => [
                'customers' => 'Clients', 'suppliers' => 'Fournisseurs', 'sales_goods' => 'Ventes de marchandises (POS)', 'sales_services' => 'Travaux d’impression',
                'vat_collected' => 'TVA collectée', 'vat_deductible' => 'TVA déductible', 'purchases_goods' => 'Achats marchandises', 'purchases_materials' => 'Achats matières',
                'other_purchases' => 'Autres achats / décaissements', 'staff_expenses' => 'Notes de frais', 'salaries' => 'Salaires', 'salaries_payable' => 'Salaires à payer',
                'staff_advances' => 'Avances au personnel', 'cash' => 'Caisse (défaut)', 'bank' => 'Banque (défaut)', 'mobile_money' => 'Mobile Money (défaut)',
                'internal_transfers' => 'Virements internes', 'cash_differences' => 'Écarts de caisse', 'discounts_granted' => 'Remises accordées',
            ],
            'accounts' => Account::query()->orderBy('code')->get(['code', 'name']),
        ]);
    }
}
