<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\EstablishmentType;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithFileUploads;

    public string $activeTab = 'company';

    public string $name = '';

    public string $slogan = '';

    public string $type = 'restaurant';

    public bool $kitchen_enabled = false;

    public bool $reservations_enabled = false;

    public bool $appointments_enabled = false;

    public bool $pos_provisional_receipt_enabled = false;

    public string $pos_provisional_receipt_type = 'addition';

    public int $appointment_reminder_hours = 24;

    public string $appointment_whatsapp_template = 'Bonjour {client}, rappel de votre rendez-vous {service} le {date} à {heure} chez {entreprise}.';

    public string $rccm = '';

    public string $id_nat = '';

    public string $import_id = '';

    public string $nif = '';

    public string $vat_number = '';

    public string $address = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public string $document_footer = '';

    public $logo;

    public string $default_currency_id = '';

    public array $enabled_currencies = ['CDF', 'USD'];

    public float $usd_cdf_rate = 2850;

    public string $pdf_template = 'modern';

    public string $paper_size = '80';

    public string $ticket_font = 'DejaVu Sans';

    public int $ticket_font_size = 11;

    public string $ticket_color = '#18181b';

    public bool $show_logo_on_ticket = true;

    public bool $auto_print_ticket = true;

    public string $ticket_template = 'modern';

    public string $ticket_code_type = 'qrcode';

    public int $ticket_logo_width = 26;

    public string $ticket_header_text = 'REÇU DE PAIEMENT';

    public string $ticket_footer_text = 'Merci de votre visite !';

    public bool $ticket_show_fiscal_info = true;

    public bool $ticket_show_tax_details = true;

    public bool $ticket_show_payment_details = true;

    public string $theme_mode = 'system';

    public string $theme_color = '#2563eb';

    public bool $loyalty_enabled = true;

    public int $loyalty_unit_amount = 1000;

    public int $loyalty_points_per_unit = 1;

    public int $loyalty_point_value = 10;

    public int $loyalty_expiry_months = 12;

    public bool $showUserForm = false;

    public ?int $editingUserId = null;

    public string $user_name = '';

    public string $user_email = '';

    public string $user_phone = '';

    public string $user_role = 'vendeur';

    public string $user_status = 'active';

    public string $user_password = '';

    public function mount(): void
    {
        $establishment = auth()->user()->establishment;
        abort_unless($establishment, 404);

        foreach ($establishment->only([
            'name', 'slogan', 'rccm', 'id_nat', 'import_id', 'nif', 'vat_number',
            'address', 'city', 'phone', 'email', 'website', 'document_footer',
        ]) as $property => $value) {
            $this->{$property} = (string) ($value ?? '');
        }
        $this->type = $establishment->type?->value ?? 'restaurant';
        $this->kitchen_enabled = $establishment->kitchenModuleEnabled();
        $this->reservations_enabled = $establishment->reservationsModuleEnabled();
        $this->appointments_enabled = $establishment->appointmentsModuleEnabled();
        $this->pos_provisional_receipt_enabled = $establishment->provisionalReceiptEnabled();
        $this->default_currency_id = $establishment->default_currency_id ?? '';

        $settings = $establishment->settings()->pluck('value', 'key');
        $this->enabled_currencies = json_decode($settings->get('enabled_currencies', '["CDF","USD"]'), true) ?: ['CDF', 'USD'];
        $this->pdf_template = $settings->get('pdf_template', 'modern');
        $this->paper_size = $settings->get('paper_size', '80');
        $this->ticket_font = $settings->get('ticket_font', 'DejaVu Sans');
        $this->ticket_font_size = (int) $settings->get('ticket_font_size', 11);
        $this->ticket_color = $settings->get('ticket_color', '#18181b');
        $this->show_logo_on_ticket = filter_var($settings->get('show_logo_on_ticket', true), FILTER_VALIDATE_BOOLEAN);
        $this->auto_print_ticket = filter_var($settings->get('auto_print_ticket', true), FILTER_VALIDATE_BOOLEAN);
        $this->ticket_template = $settings->get('ticket_template', 'modern');
        $this->ticket_code_type = $settings->get('ticket_code_type', 'qrcode');
        $this->ticket_logo_width = (int) $settings->get('ticket_logo_width', 26);
        $this->ticket_header_text = $settings->get('ticket_header_text', 'REÇU DE PAIEMENT');
        $this->ticket_footer_text = $settings->get('ticket_footer_text', $establishment->document_footer ?: 'Merci de votre visite !');
        $this->ticket_show_fiscal_info = filter_var($settings->get('ticket_show_fiscal_info', true), FILTER_VALIDATE_BOOLEAN);
        $this->ticket_show_tax_details = filter_var($settings->get('ticket_show_tax_details', true), FILTER_VALIDATE_BOOLEAN);
        $this->ticket_show_payment_details = filter_var($settings->get('ticket_show_payment_details', true), FILTER_VALIDATE_BOOLEAN);
        $this->theme_mode = $settings->get('theme_mode', 'system');
        $this->theme_color = $settings->get('theme_color', '#2563eb');
        $this->loyalty_enabled = filter_var($settings->get('loyalty_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $this->loyalty_unit_amount = (int) $settings->get('loyalty_unit_amount', 1000);
        $this->loyalty_points_per_unit = (int) $settings->get('loyalty_points_per_unit', 1);
        $this->loyalty_point_value = (int) $settings->get('loyalty_point_value', 10);
        $this->loyalty_expiry_months = (int) $settings->get('loyalty_expiry_months', 12);
        $this->appointment_reminder_hours = (int) $settings->get('appointment_reminder_hours', 24);
        $this->appointment_whatsapp_template = $settings->get('appointment_whatsapp_template', $this->appointment_whatsapp_template);
        $this->pos_provisional_receipt_type = $settings->get('pos_provisional_receipt_type', 'addition');

        $usd = Currency::where('code', 'USD')->first();
        $cdf = Currency::where('code', 'CDF')->first();
        if ($usd && $cdf) {
            $this->usd_cdf_rate = (float) (ExchangeRate::query()
                ->where('establishment_id', $establishment->id)
                ->where('from_currency_id', $usd->id)
                ->where('to_currency_id', $cdf->id)
                ->latest('effective_date')->value('sell_rate') ?? 2850);
        }
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['company', 'modules', 'currencies', 'documents', 'ticket', 'appearance', 'loyalty', 'users'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function saveCompany(): void
    {
        $this->ensureSettingsEditable();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EstablishmentType::class)],
            'rccm' => ['nullable', 'string', 'max:100'],
            'id_nat' => ['nullable', 'string', 'max:100'],
            'import_id' => ['nullable', 'string', 'max:100'],
            'nif' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'document_footer' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'kitchen_enabled' => ['boolean'],
        ]);

        $supportsKitchen = in_array($this->type, [
            EstablishmentType::Restaurant->value,
            EstablishmentType::Bar->value,
        ], true);
        $this->kitchen_enabled = $supportsKitchen && $this->kitchen_enabled;

        unset($data['logo'], $data['kitchen_enabled']);
        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('establishments/logos', 'public');
        }
        auth()->user()->establishment->update($data);
        $this->putSetting('modules', 'kitchen_enabled', $this->kitchen_enabled, 'boolean');
        $this->dispatch('settings-saved');
        session()->flash('success', $this->kitchen_enabled
            ? 'Informations enregistrées et module Cuisine activé.'
            : 'Informations de l’entreprise enregistrées.');
        $this->redirectRoute('admin.settings.index', navigate: true);
    }

    public function saveCurrencies(): void
    {
        $this->ensureSettingsEditable();
        $this->validate([
            'default_currency_id' => ['required', 'exists:currencies,id'],
            'enabled_currencies' => ['required', 'array', 'min:1'],
            'enabled_currencies.*' => ['in:CDF,USD,EUR'],
            'usd_cdf_rate' => ['required', 'numeric', 'min:0.000001', 'max:999999999'],
        ]);

        $establishment = auth()->user()->establishment;
        $usd = Currency::where('code', 'USD')->firstOrFail();
        $cdf = Currency::where('code', 'CDF')->firstOrFail();

        DB::transaction(function () use ($establishment, $usd, $cdf) {
            $establishment->update(['default_currency_id' => $this->default_currency_id]);
            $this->putSetting('currency', 'enabled_currencies', $this->enabled_currencies, 'json');

            ExchangeRate::updateOrCreate([
                'establishment_id' => $establishment->id,
                'from_currency_id' => $usd->id,
                'to_currency_id' => $cdf->id,
                'effective_date' => today(),
            ], [
                'buy_rate' => $this->usd_cdf_rate,
                'sell_rate' => $this->usd_cdf_rate,
                'created_by' => auth()->id(),
            ]);

            $inverse = 1 / $this->usd_cdf_rate;
            ExchangeRate::updateOrCreate([
                'establishment_id' => $establishment->id,
                'from_currency_id' => $cdf->id,
                'to_currency_id' => $usd->id,
                'effective_date' => today(),
            ], [
                'buy_rate' => $inverse,
                'sell_rate' => $inverse,
                'created_by' => auth()->id(),
            ]);
        });

        session()->flash('success', 'Devises et taux USD/CDF mis à jour.');
    }

    public function saveModules(): void
    {
        $this->ensureSettingsEditable();
        $this->validate([
            'kitchen_enabled' => ['boolean'],
            'reservations_enabled' => ['boolean'],
            'appointments_enabled' => ['boolean'],
            'pos_provisional_receipt_enabled' => ['boolean'],
            'pos_provisional_receipt_type' => ['required', 'in:addition,proforma'],
            'appointment_reminder_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'appointment_whatsapp_template' => ['required', 'string', 'max:1000'],
        ]);

        $establishment = auth()->user()->establishment->fresh();
        $this->kitchen_enabled = $establishment->supportsKitchenModule() && $this->kitchen_enabled;
        $this->pos_provisional_receipt_enabled = $establishment->supportsKitchenModule() && $this->pos_provisional_receipt_enabled;

        $this->putMany('modules', [
            'kitchen_enabled' => [$this->kitchen_enabled, 'boolean'],
            'reservations_enabled' => [$this->reservations_enabled, 'boolean'],
            'appointments_enabled' => [$this->appointments_enabled, 'boolean'],
            'pos_provisional_receipt_enabled' => [$this->pos_provisional_receipt_enabled, 'boolean'],
            'pos_provisional_receipt_type' => [$this->pos_provisional_receipt_type, 'string'],
            'appointment_reminder_hours' => [$this->appointment_reminder_hours, 'integer'],
            'appointment_whatsapp_template' => [$this->appointment_whatsapp_template, 'string'],
        ]);

        session()->flash('success', 'Configuration des modules enregistrée.');
        $this->redirectRoute('admin.settings.index', navigate: true);
    }

    public function saveDocuments(): void
    {
        $this->ensureSettingsEditable();
        $this->validate(['pdf_template' => ['required', 'in:compact,modern,classic']]);
        $this->putSetting('documents', 'pdf_template', $this->pdf_template);
        session()->flash('success', 'Modèle PDF enregistré.');
    }

    public function saveTicket(): void
    {
        $this->ensureSettingsEditable();
        $this->validate([
            'paper_size' => ['required', 'in:58,80,a4'],
            'ticket_font' => ['required', 'in:DejaVu Sans,Courier New,Helvetica'],
            'ticket_font_size' => ['required', 'integer', 'min:8', 'max:18'],
            'ticket_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'show_logo_on_ticket' => ['boolean'],
            'auto_print_ticket' => ['boolean'],
            'ticket_template' => ['required', 'in:minimal,classic,modern,elegant,bold,compact'],
            'ticket_code_type' => ['required', 'in:none,barcode,qrcode'],
            'ticket_logo_width' => ['required', 'integer', 'min:16', 'max:40'],
            'ticket_header_text' => ['required', 'string', 'max:80'],
            'ticket_footer_text' => ['nullable', 'string', 'max:500'],
            'ticket_show_fiscal_info' => ['boolean'],
            'ticket_show_tax_details' => ['boolean'],
            'ticket_show_payment_details' => ['boolean'],
        ]);
        $this->putMany('ticket', [
            'paper_size' => [$this->paper_size, 'string'],
            'ticket_font' => [$this->ticket_font, 'string'],
            'ticket_font_size' => [$this->ticket_font_size, 'integer'],
            'ticket_color' => [$this->ticket_color, 'string'],
            'show_logo_on_ticket' => [$this->show_logo_on_ticket, 'boolean'],
            'auto_print_ticket' => [$this->auto_print_ticket, 'boolean'],
            'ticket_template' => [$this->ticket_template, 'string'],
            'ticket_code_type' => [$this->ticket_code_type, 'string'],
            'ticket_logo_width' => [$this->ticket_logo_width, 'integer'],
            'ticket_header_text' => [$this->ticket_header_text, 'string'],
            'ticket_footer_text' => [$this->ticket_footer_text, 'string'],
            'ticket_show_fiscal_info' => [$this->ticket_show_fiscal_info, 'boolean'],
            'ticket_show_tax_details' => [$this->ticket_show_tax_details, 'boolean'],
            'ticket_show_payment_details' => [$this->ticket_show_payment_details, 'boolean'],
        ]);
        session()->flash('success', 'Configuration du ticket thermique enregistrée.');
    }

    public function saveAppearance(): void
    {
        $this->ensureSettingsEditable();
        $this->validate([
            'theme_mode' => ['required', 'in:light,dark,system'],
            'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $this->putMany('appearance', [
            'theme_mode' => [$this->theme_mode, 'string'],
            'theme_color' => [$this->theme_color, 'string'],
        ]);
        $this->dispatch('yeboox-theme-changed', mode: $this->theme_mode);
        session()->flash('success', 'Apparence enregistrée et appliquée.');
    }

    public function saveLoyalty(): void
    {
        $this->ensureSettingsEditable();
        $this->validate([
            'loyalty_enabled' => ['boolean'],
            'loyalty_unit_amount' => ['required', 'integer', 'min:1'],
            'loyalty_points_per_unit' => ['required', 'integer', 'min:1', 'max:100000'],
            'loyalty_point_value' => ['required', 'integer', 'min:0', 'max:1000000'],
            'loyalty_expiry_months' => ['required', 'integer', 'min:0', 'max:120'],
        ]);
        $this->putMany('loyalty', [
            'loyalty_enabled' => [$this->loyalty_enabled, 'boolean'],
            'loyalty_unit_amount' => [$this->loyalty_unit_amount, 'integer'],
            'loyalty_points_per_unit' => [$this->loyalty_points_per_unit, 'integer'],
            'loyalty_point_value' => [$this->loyalty_point_value, 'integer'],
            'loyalty_expiry_months' => [$this->loyalty_expiry_months, 'integer'],
        ]);
        session()->flash('success', 'Programme de fidélité mis à jour.');
    }

    public function openUserForm(?int $userId = null): void
    {
        $this->resetUserForm();
        if ($userId) {
            $user = User::forEstablishment()->findOrFail($userId);
            $this->editingUserId = $user->id;
            $this->user_name = $user->name;
            $this->user_email = $user->email;
            $this->user_phone = $user->phone ?? '';
            $this->user_role = $user->roles->first()?->name ?? 'vendeur';
            $this->user_status = $user->status->value;
        }
        $this->showUserForm = true;
    }

    public function saveUser(): void
    {
        abort_unless(auth()->user()->can($this->editingUserId ? 'users.edit' : 'users.create'), 403);
        $rules = [
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'user_phone' => ['nullable', 'string', 'max:40'],
            'user_role' => ['required', Rule::exists('roles', 'name')],
            'user_status' => ['required', 'in:active,suspended'],
            'user_password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
        ];
        $this->validate($rules);

        if ($this->editingUserId === auth()->id() && $this->user_status === 'suspended') {
            throw ValidationException::withMessages([
                'user_status' => 'Vous ne pouvez pas suspendre votre propre compte.',
            ]);
        }

        $values = [
            'name' => $this->user_name,
            'email' => $this->user_email,
            'phone' => $this->user_phone ?: null,
            'status' => $this->user_status,
            'establishment_id' => auth()->user()->establishment_id,
        ];
        if ($this->user_password !== '') {
            $values['password'] = Hash::make($this->user_password);
        }
        if (! $this->editingUserId) {
            $values['email_verified_at'] = now();
        }

        $user = $this->editingUserId
            ? User::forEstablishment()->findOrFail($this->editingUserId)
            : new User;
        $user->fill($values)->save();
        $user->syncRoles([$this->user_role]);

        $this->showUserForm = false;
        session()->flash('success', $this->editingUserId ? 'Utilisateur modifié.' : 'Utilisateur créé.');
        $this->resetUserForm();
    }

    public function resetUserForm(): void
    {
        $this->reset(['editingUserId', 'user_name', 'user_email', 'user_phone', 'user_password']);
        $this->user_role = 'vendeur';
        $this->user_status = 'active';
        $this->resetValidation();
    }

    private function putMany(string $group, array $settings): void
    {
        foreach ($settings as $key => [$value, $type]) {
            $this->putSetting($group, $key, $value, $type);
        }
    }

    private function ensureSettingsEditable(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
    }

    private function putSetting(string $group, string $key, mixed $value, string $type = 'string'): void
    {
        Setting::updateOrCreate([
            'establishment_id' => auth()->user()->establishment_id,
            'branch_id' => null,
            'group' => $group,
            'key' => $key,
        ], [
            'value' => $type === 'json' ? json_encode($value) : match ($type) {
                'boolean' => $value ? '1' : '0',
                default => (string) $value,
            },
            'type' => $type,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.settings.index', [
            'establishment' => auth()->user()->establishment->fresh(),
            'currencies' => Currency::active()->orderBy('code')->get(),
            'types' => EstablishmentType::cases(),
            'users' => User::forEstablishment()->with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Paramètres', 'breadcrumb' => 'Paramètres']);
    }
}
