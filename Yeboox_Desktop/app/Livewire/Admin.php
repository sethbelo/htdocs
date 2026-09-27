<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\DocumentPreferences;
use App\Support\DisplayCurrency;
use App\Support\ModuleRegistry;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Admin extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $activeTab = 'company';

    // --- Company Info ---
    public $company_name = '';

    public $company_address = '';

    public $company_phone = '';

    public $company_email = '';

    public $exchange_rate = 2800;

    public $company_header = '';

    public $company_footer = '';

    public $company_rccm = '';

    public $company_nif = '';

    public $company_tva = '';

    public $company_currency = DisplayCurrency::CDF;

    public $secondary_currency = DisplayCurrency::USD;

    public $currency_operation = 'base_to_secondary';

    public string $product_price_currency = 'CDF';

    public bool $default_tax_included = true;

    // --- Payment Methods ---
    public $paymentMethodId = null;

    public $pmName = '';

    public $pmCode = '';

    public $pmIcon = '';

    public $pmIsActive = true;

    public $pmSyscohadaCode = '';

    public string $posBanks = '';

    public string $posMobileProviders = '';

    public string $posYepayProviders = '';

    public $isPaymentMethodModalOpen = false;

    // --- Theme & Logo ---
    public $primary_color = '#7e22ce';

    public $font_family = 'Poppins';

    public $app_theme = 'system';

    public $document_layout = 'light';

    public $document_font_size = 'base';

    public $logo;

    public $currentLogoUrl;

    public $establishmentLogo;

    public $currentEstablishmentLogoUrl;

    // --- Display & responsive layout ---
    public string $displayResolution = 'auto';

    public int $displayWidth = 1280;

    public int $displayHeight = 800;

    public int $displayScale = 100;

    public string $displayDensity = 'comfortable';

    // --- Env Editor ---
    public $envContent = '';

    // --- API Keys & Integrations ---
    public $stripe_key = '';

    public $twilio_key = '';

    // Email
    public $mail_driver = 'smtp';

    public $mail_host = '';

    public $mail_port = '587';

    public $mail_username = '';

    public $mail_password = '';

    public $mail_encryption = 'tls';

    public $mail_from_address = '';

    // Cloud Backup
    public $backup_disk = 'local';

    public $aws_access_key_id = '';

    public $aws_secret_access_key = '';

    public $aws_default_region = 'us-east-1';

    public $aws_bucket = '';

    // --- Documents & Printing ---
    public string $receipt_footer = 'Merci de votre visite ! À bientôt.';

    public string $receipt_template = 'classic';

    public string $documentSection = 'tickets';

    public string $receipt_code_type = 'none';

    public string $receipt_code_source = 'reference';

    public string $receipt_code_custom = '';

    public string $receipt_font_family = 'mono';

    public int $receipt_font_size = 11;

    public int $receipt_font_weight = 400;

    public string $receipt_paper_width = '80';

    public string $report_template = 'executive';

    public string $report_font_family = 'system';

    public int $report_font_size = 11;

    public int $report_font_weight = 400;

    // --- User Form ---
    public $userId = null;

    public $name = '';

    public $email = '';

    public $password = '';

    public $selectedRole = '';

    public $isUserModalOpen = false;

    // --- Restaurant Settings ---
    public array $restaurant_tables = [];

    public $kitchen_print_mode = 'browser';

    public $kitchen_printer_ip = '';

    public array $kitchen_categories = [];

    public array $monthlyModules = [];

    public string $loyaltyMode = 'points';

    public $loyaltyPointValueUsd = '0.02';

    public $loyaltyRequiredServices = 9;

    public ?int $loyaltyRewardProductId = null;

    // --- Search & Filter ---
    public $searchUsers = '';

    public $searchLogs = '';

    protected $queryString = [
        'activeTab' => ['except' => 'company'],
        'searchUsers' => ['except' => ''],
        'searchLogs' => ['except' => ''],
    ];

    public function mount()
    {
        if (! auth()->user()->can('manage settings')) {
            $this->activeTab = auth()->user()->can('manage users')
                ? 'users'
                : (auth()->user()->can('manage roles') ? 'roles' : 'logs');

            return;
        }

        // Load Company Info
        $this->company_name = Setting::get('company_name', 'Yeboox App');
        $this->company_address = Setting::get('company_address', '123 Avenue du Bien-être, Kinshasa');
        $this->company_phone = Setting::get('company_phone', '+243 81 000 0000');
        $this->company_email = Setting::get('company_email', 'contact@cmspa.com');
        $this->exchange_rate = Setting::get('exchange_rate', 2850);
        $this->company_header = Setting::get('company_header', '');
        $this->company_footer = Setting::get('company_footer', '');
        $this->company_rccm = Setting::get('company_rccm', '');
        $this->company_nif = Setting::get('company_nif', '');
        $this->company_tva = Setting::get('company_tva', '');
        $this->company_currency = DisplayCurrency::baseCode();
        $this->secondary_currency = DisplayCurrency::secondaryCode();
        $this->currency_operation = 'base_to_secondary';
        $this->product_price_currency = DisplayCurrency::baseCode();
        $this->default_tax_included = filter_var(
            Setting::get('default_tax_included', '1'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? true;
        $this->posBanks = implode(', ', json_decode((string) Setting::get('pos_banks', '[]'), true) ?: ['Rawbank', 'EquityBCDC', 'Ecobank', 'TMB', 'Access Bank', 'FBNBank', 'UBA', 'Afriland First Bank', 'Bank of Africa']);
        $this->posMobileProviders = implode(', ', json_decode((string) Setting::get('pos_mobile_providers', '[]'), true) ?: ['M-Pesa', 'Airtel Money', 'Orange Money', 'Afrimoney']);
        $this->posYepayProviders = implode(', ', json_decode((string) Setting::get('pos_yepay_providers', '[]'), true) ?: ['YePay Wallet']);

        // Load Theme
        $this->primary_color = Setting::get('primary_color', '#7e22ce');
        $this->font_family = Setting::get('font_family', 'Poppins');
        $this->app_theme = Setting::get('app_theme', 'system');
        $this->document_layout = Setting::get('document_layout', 'light');
        $this->document_font_size = Setting::get('document_font_size', 'base');
        $this->displayResolution = (string) Setting::get('display_resolution', 'auto');
        $this->displayWidth = (int) Setting::get('display_width', 1280);
        $this->displayHeight = (int) Setting::get('display_height', 800);
        $this->displayScale = (int) Setting::get('display_scale', 100);
        $this->displayDensity = (string) Setting::get('display_density', 'comfortable');
        $logoPath = Setting::get('company_logo');
        if ($logoPath) {
            $cleanPath = str_replace('public/', '', $logoPath);
            $this->currentLogoUrl = asset('storage/'.ltrim($cleanPath, '/'));
        } else {
            $this->currentLogoUrl = null;
        }
        $establishmentLogoPath = app(TenantContext::class)->establishment()?->logo_path;
        if ($establishmentLogoPath) {
            $cleanPath = str_replace('public/', '', $establishmentLogoPath);
            $this->currentEstablishmentLogoUrl = asset('storage/'.ltrim($cleanPath, '/'));
        } else {
            $this->currentEstablishmentLogoUrl = null;
        }

        // Load API Keys & Integrations
        $this->stripe_key = Setting::get('stripe_key', '');
        $this->twilio_key = Setting::get('twilio_key', '');

        $this->mail_driver = Setting::get('mail_driver', 'smtp');
        $this->mail_host = Setting::get('mail_host', '');
        $this->mail_port = Setting::get('mail_port', '587');
        $this->mail_username = Setting::get('mail_username', '');
        $this->mail_password = Setting::get('mail_password', '');
        $this->mail_encryption = Setting::get('mail_encryption', 'tls');
        $this->mail_from_address = Setting::get('mail_from_address', '');

        $this->backup_disk = Setting::get('backup_disk', 'local');
        $this->aws_access_key_id = Setting::get('aws_access_key_id', '');
        $this->aws_secret_access_key = Setting::get('aws_secret_access_key', '');
        $this->aws_default_region = Setting::get('aws_default_region', 'us-east-1');
        $this->aws_bucket = Setting::get('aws_bucket', '');

        $this->receipt_footer = Setting::get('receipt_footer', 'Merci de votre visite ! À bientôt.');
        $this->receipt_template = Setting::get('receipt_template', 'classic');
        $this->receipt_code_type = Setting::get('receipt_code_type', 'none');
        $this->receipt_code_source = Setting::get('receipt_code_source', 'reference');
        $this->receipt_code_custom = Setting::get('receipt_code_custom', '');
        $this->receipt_font_family = Setting::get('receipt_font_family', 'mono');
        $this->receipt_font_size = (int) Setting::get('receipt_font_size', 11);
        $this->receipt_font_weight = (int) Setting::get('receipt_font_weight', 400);
        $this->receipt_paper_width = (string) Setting::get('receipt_paper_width', '80');
        $this->report_template = Setting::get('report_template', 'executive');
        $this->report_font_family = Setting::get('report_font_family', 'system');
        $this->report_font_size = (int) Setting::get('report_font_size', 11);
        $this->report_font_weight = (int) Setting::get('report_font_weight', 400);

        // Load Restaurant Settings
        $tablesJson = Setting::get('restaurant_tables');
        $this->restaurant_tables = $tablesJson ? json_decode($tablesJson, true) : [['zone' => 'Salle Principale', 'tables' => '1, 2, 3, 4, 5']];
        $this->kitchen_print_mode = Setting::get('kitchen_print_mode', 'browser');
        $this->kitchen_printer_ip = Setting::get('kitchen_printer_ip', '192.168.1.100');
        $catsJson = Setting::get('kitchen_categories');
        $this->kitchen_categories = $catsJson ? json_decode($catsJson, true) : ['Plats', 'Boissons'];
        $this->monthlyModules = ModuleRegistry::configuration();
        $this->loyaltyMode = (string) Setting::get('loyalty_mode', 'points');
        $this->loyaltyPointValueUsd = (string) Setting::get('loyalty_point_value_usd', '0.02');
        $this->loyaltyRequiredServices = (int) Setting::get('loyalty_required_services', 9);
        $rewardProductId = (int) Setting::get('loyalty_reward_product_id', 0);
        $this->loyaltyRewardProductId = $rewardProductId > 0 ? $rewardProductId : null;

        // Load ENV
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $this->envContent = File::get($envPath);
        }
    }

    private const TABS = [
        'company',
        'documents',
        'theme',
        'display',
        'env',
        'api',
        'roles',
        'users',
        'logs',
        'payment_methods',
        'restaurants',
        'modules',
    ];

    public function setTab(string $tab): void
    {
        $tab = in_array($tab, self::TABS, true) ? $tab : 'company';
        $this->authorizeAdminTab($tab);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedActiveTab(string $tab): void
    {
        if (! in_array($tab, self::TABS, true)) {
            $this->activeTab = 'company';
        }

        $this->authorizeAdminTab($this->activeTab);

        $this->resetPage();
    }

    public function updatedProductPriceCurrency(string $currency): void
    {
        $this->product_price_currency = DisplayCurrency::baseCode();
    }

    public function updatedCurrencyOperation(string $operation): void
    {
        $this->currency_operation = 'base_to_secondary';
    }

    // --- Save Company ---
    public function saveCompany()
    {
        $this->authorizeSettings();
        $this->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:500',
            'company_phone' => 'required|string|max:50',
            'company_email' => 'required|email|max:255',
            'exchange_rate' => 'required|numeric|gt:0|max:1000000000',
            'company_currency' => ['required', Rule::in(DisplayCurrency::SUPPORTED)],
            'secondary_currency' => ['required', Rule::in(DisplayCurrency::SUPPORTED), 'different:company_currency'],
            'product_price_currency' => ['required', Rule::in([DisplayCurrency::baseCode()])],
            'default_tax_included' => ['required', 'boolean'],
            'company_header' => 'nullable|string|max:1000',
            'company_footer' => 'nullable|string|max:1000',
            'company_rccm' => 'nullable|string|max:100',
            'company_nif' => 'nullable|string|max:100',
            'company_tva' => 'nullable|string|max:100',
        ]);

        Setting::set('company_name', $this->company_name);
        Setting::set('company_address', $this->company_address);
        Setting::set('company_phone', $this->company_phone);
        Setting::set('company_email', $this->company_email);
        Setting::set('exchange_rate', $this->exchange_rate);
        Setting::set('company_header', $this->company_header);
        Setting::set('company_footer', $this->company_footer);
        Setting::set('company_rccm', $this->company_rccm);
        Setting::set('company_nif', $this->company_nif);
        Setting::set('company_tva', $this->company_tva);
        $this->company_currency = DisplayCurrency::baseCode();
        $this->product_price_currency = $this->company_currency;
        Setting::set('base_currency', $this->company_currency);
        Setting::set('company_currency', $this->company_currency);
        Setting::set('secondary_currency', $this->secondary_currency);
        Setting::set('currency_operation', 'base_to_secondary');
        Setting::set('product_price_currency', $this->company_currency);
        Setting::set('default_tax_included', $this->default_tax_included ? '1' : '0');

        $tenant = app(TenantContext::class);
        $tenant->company()?->update([
            'name' => $this->company_name,
            'legal_name' => $this->company_name,
            'email' => $this->company_email,
            'phone' => $this->company_phone,
            'address' => $this->company_address,
            'rccm' => $this->company_rccm ?: null,
            'nif' => $this->company_nif ?: null,
            'tva_number' => $this->company_tva ?: null,
            'base_currency' => $this->company_currency,
            'secondary_currency' => $this->secondary_currency,
            'exchange_rate' => $this->exchange_rate,
        ]);
        $tenant->pointOfSale()?->update(['default_currency' => $this->company_currency]);

        $this->dispatch(
            'company-identity-updated',
            initial: mb_strtoupper(mb_substr(trim((string) $this->company_name), 0, 1)) ?: 'Y'
        );

        session()->flash('success', 'Informations de la société enregistrées.');
    }

    // --- Save Theme ---
    public function saveTheme()
    {
        $this->authorizeSettings();
        $this->validate([
            'primary_color' => 'required|string|max:50',
            'font_family' => 'required|string|max:100',
            'app_theme' => 'required|string|in:light,dark,system',
            'document_layout' => 'required|string|in:light,wave,duel,lines,bubble,boxed,striped,polygon,elegant,nature',
            'document_font_size' => 'required|string|in:sm,base,lg',
        ]);

        Setting::set('primary_color', $this->primary_color);
        Setting::set('font_family', $this->font_family);
        Setting::set('app_theme', $this->app_theme);
        Setting::set('document_layout', $this->document_layout);
        Setting::set('document_font_size', $this->document_font_size);

        if ($this->logo) {
            $this->validate(['logo' => 'image|max:2048']);
            $path = $this->logo->store('logos', 'public');
            Setting::set('company_logo', $path);
            $this->currentLogoUrl = asset('storage/'.$path);
            $this->logo = null;
            $this->dispatch('logo-updated', url: $this->currentLogoUrl);
        }

        if ($this->establishmentLogo) {
            $this->validate(['establishmentLogo' => 'image|max:2048']);
            $path = $this->establishmentLogo->store('establishments', 'public');
            $establishment = app(TenantContext::class)->establishment();
            if ($establishment) {
                $establishment->update(['logo_path' => $path]);
                $this->currentEstablishmentLogoUrl = asset('storage/'.$path);
            }
            $this->establishmentLogo = null;
        }

        $this->dispatch('theme-updated', theme: $this->app_theme);
        $this->dispatch('color-updated', color: $this->primary_color);

        session()->flash('success', 'Thème et logo mis à jour.');
    }

    /**
     * Enregistre un profil d'affichage commun au navigateur et à Electron.
     * Electron limite ensuite la fenêtre à la surface réellement disponible.
     */
    public function saveDisplaySettings(): void
    {
        $this->authorizeSettings();
        $profiles = ['auto', '800x600', '1024x768', '1280x720', '1366x768', '1440x900', '1600x900', '1920x1080', '2560x1440', 'custom'];

        $this->validate([
            'displayResolution' => ['required', Rule::in($profiles)],
            'displayWidth' => 'required|integer|min:760|max:3840',
            'displayHeight' => 'required|integer|min:540|max:2160',
            'displayScale' => ['required', 'integer', Rule::in([80, 90, 100, 110, 125])],
            'displayDensity' => ['required', Rule::in(['comfortable', 'compact'])],
        ]);

        if ($this->displayResolution !== 'custom' && $this->displayResolution !== 'auto') {
            [$this->displayWidth, $this->displayHeight] = array_map('intval', explode('x', $this->displayResolution));
        }

        Setting::set('display_resolution', $this->displayResolution);
        Setting::set('display_width', $this->displayWidth);
        Setting::set('display_height', $this->displayHeight);
        Setting::set('display_scale', $this->displayScale);
        Setting::set('display_density', $this->displayDensity);

        $this->dispatch('display-settings-updated', settings: $this->displaySettingsPayload());
        session()->flash('success', 'Affichage appliqué à l’application et au point de vente.');
    }

    private function displaySettingsPayload(): array
    {
        return [
            'resolution' => $this->displayResolution,
            'width' => $this->displayWidth,
            'height' => $this->displayHeight,
            'scale' => $this->displayScale,
            'density' => $this->displayDensity,
        ];
    }

    // --- Save API Keys ---
    public function saveApiKeys()
    {
        $this->authorizeSettings();
        Setting::set('stripe_key', $this->stripe_key);
        Setting::set('twilio_key', $this->twilio_key);

        Setting::set('mail_driver', $this->mail_driver);
        Setting::set('mail_host', $this->mail_host);
        Setting::set('mail_port', $this->mail_port);
        Setting::set('mail_username', $this->mail_username);
        Setting::set('mail_password', $this->mail_password);
        Setting::set('mail_encryption', $this->mail_encryption);
        Setting::set('mail_from_address', $this->mail_from_address);

        Setting::set('backup_disk', $this->backup_disk);
        Setting::set('aws_access_key_id', $this->aws_access_key_id);
        Setting::set('aws_secret_access_key', $this->aws_secret_access_key);
        Setting::set('aws_default_region', $this->aws_default_region);
        Setting::set('aws_bucket', $this->aws_bucket);

        session()->flash('success', 'Intégrations enregistrées.');
    }

    // --- Save Env ---
    public function saveEnv()
    {
        $this->authorizeSettings();
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            File::put($envPath, $this->envContent);
            session()->flash('success', 'Fichier .env mis à jour avec succès.');
        } else {
            session()->flash('error', 'Le fichier .env est introuvable.');
        }
    }

    // --- Roles & Permissions ---
    public function togglePermission($roleId, $permissionName)
    {
        abort_unless(auth()->user()?->is_master_admin, 403, 'Seul l’administrateur master peut modifier la politique globale des rôles.');
        $permission = Permission::findByName((string) $permissionName, 'web');
        $role = Role::findById($roleId, 'web');
        if ($role->name === 'Super administrateur') {
            session()->flash('error', 'Vous ne pouvez pas modifier les permissions du Super administrateur.');

            return;
        }

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
        } else {
            $role->givePermissionTo($permission);
        }

        session()->flash('success', 'Permissions mises à jour.');
    }

    // --- Users Management ---
    public function openUserModal($id = null)
    {
        abort_unless(auth()->user()?->can('manage users'), 403, 'Vous ne pouvez pas gérer les utilisateurs.');
        $this->resetValidation();
        $this->resetUserForm();

        if ($id) {
            $user = $this->companyUsers()->findOrFail($id);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->selectedRole = $user->roles->first()->name ?? '';
        }

        $this->isUserModalOpen = true;
    }

    public function closeUserModal()
    {
        $this->isUserModalOpen = false;
        $this->resetUserForm();
    }

    public function resetUserForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->selectedRole = '';
    }

    public function saveUser()
    {
        abort_unless(auth()->user()?->can('manage users'), 403, 'Vous ne pouvez pas gérer les utilisateurs.');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->userId)],
            'selectedRole' => 'required|string|exists:roles,name',
        ];

        if (! $this->userId) {
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $existingUser = $this->userId ? $this->companyUsers()->with('roles')->findOrFail($this->userId) : null;
        $existingRole = $existingUser?->roles->first()?->name;
        if (! $existingUser || $existingRole !== $this->selectedRole) {
            abort_unless(auth()->user()?->can('manage roles'), 403, 'Vous ne pouvez pas attribuer un rôle.');
        }

        if ($existingUser?->id === auth()->id() && $existingRole !== $this->selectedRole) {
            session()->flash('error', 'Vous ne pouvez pas modifier votre propre rôle pendant votre session.');

            return;
        }

        if ($existingRole === 'Super administrateur'
            && $this->selectedRole !== 'Super administrateur'
            && $this->companyUsers()->role('Super administrateur')->count() <= 1) {
            session()->flash('error', 'Le dernier Super administrateur doit conserver son rôle.');

            return;
        }

        if ($existingUser) {
            $user = $existingUser;
            $user->update($data);
            ActivityLog::log('USER_UPDATED', "L'utilisateur {$user->name} a été modifié.");
        } else {
            $tenant = app(TenantContext::class);
            $user = User::create($data + [
                'company_id' => $tenant->companyId(),
                'current_establishment_id' => $tenant->establishmentId(),
                'current_point_of_sale_id' => $tenant->pointOfSaleId(),
                'is_company_admin' => $this->selectedRole === 'Super administrateur',
                'is_master_admin' => false,
                'is_active' => true,
            ]);
            if ($tenant->pointOfSaleId()) {
                $user->pointsOfSale()->syncWithoutDetaching([
                    $tenant->pointOfSaleId() => ['access_level' => $this->selectedRole === 'Super administrateur' ? 'administrator' : 'operator'],
                ]);
            }
            ActivityLog::log('USER_CREATED', "L'utilisateur {$user->name} a été créé.");
        }

        $user->syncRoles([$this->selectedRole]);

        $this->closeUserModal();
        session()->flash('success', 'Utilisateur enregistré avec succès.');
    }

    public function deleteUser($id)
    {
        abort_unless(auth()->user()?->can('manage users'), 403, 'Vous ne pouvez pas supprimer un utilisateur.');
        $user = $this->companyUsers()->findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return;
        }

        if ($user->hasRole('Super administrateur') && $this->companyUsers()->role('Super administrateur')->count() <= 1) {
            session()->flash('error', 'Le dernier Super administrateur ne peut pas être supprimé.');

            return;
        }

        $user->delete();
        ActivityLog::log('USER_DELETED', "L'utilisateur {$user->name} a été supprimé.");
        session()->flash('success', 'Utilisateur supprimé.');
    }

    // --- Payment Methods Management ---
    public function openPaymentMethodModal($id = null)
    {
        $this->authorizeSettings();
        $this->resetValidation();
        $this->resetPaymentMethodForm();

        if ($id) {
            $pm = PaymentMethod::findOrFail($id);
            $this->paymentMethodId = $pm->id;
            $this->pmName = $pm->name;
            $this->pmCode = $pm->code;
            $this->pmIcon = $pm->icon;
            $this->pmIsActive = $pm->is_active;
            $this->pmSyscohadaCode = $pm->syscohada_code;
        }

        $this->isPaymentMethodModalOpen = true;
    }

    public function closePaymentMethodModal()
    {
        $this->isPaymentMethodModalOpen = false;
        $this->resetPaymentMethodForm();
    }

    public function resetPaymentMethodForm()
    {
        $this->paymentMethodId = null;
        $this->pmName = '';
        $this->pmCode = '';
        $this->pmIcon = '';
        $this->pmIsActive = true;
        $this->pmSyscohadaCode = '';
    }

    public function savePaymentMethod()
    {
        $this->authorizeSettings();
        $this->validate([
            'pmName' => 'required|string|max:255',
            'pmCode' => ['required', 'string', 'max:50', Rule::unique('payment_methods', 'code')->ignore($this->paymentMethodId)],
            'pmIcon' => 'nullable|string|max:50',
            'pmIsActive' => 'boolean',
            'pmSyscohadaCode' => 'nullable|string|max:20',
        ]);

        $data = [
            'name' => $this->pmName,
            'code' => $this->pmCode,
            'icon' => $this->pmIcon,
            'is_active' => $this->pmIsActive,
            'syscohada_code' => $this->pmSyscohadaCode,
        ];

        if ($this->paymentMethodId) {
            $pm = PaymentMethod::findOrFail($this->paymentMethodId);
            $pm->update($data);
        } else {
            PaymentMethod::create($data);
        }

        $this->closePaymentMethodModal();
        session()->flash('success', 'Moyen de paiement enregistré avec succès.');
    }

    public function deletePaymentMethod($id)
    {
        $this->authorizeSettings();
        $pm = PaymentMethod::findOrFail($id);
        if ($pm->code === 'cash' || $pm->code === 'points') {
            session()->flash('error', 'Impossible de supprimer ce moyen de paiement système.');

            return;
        }
        $pm->delete();
        session()->flash('success', 'Moyen de paiement supprimé.');
    }

    public function savePosPaymentProviders(): void
    {
        $this->authorizeSettings();
        $this->validate([
            'posBanks' => ['required', 'string', 'max:2000'],
            'posMobileProviders' => ['required', 'string', 'max:1000'],
            'posYepayProviders' => ['required', 'string', 'max:500'],
        ]);

        foreach ([
            'pos_banks' => $this->posBanks,
            'pos_mobile_providers' => $this->posMobileProviders,
            'pos_yepay_providers' => $this->posYepayProviders,
        ] as $key => $value) {
            $items = collect(preg_split('/[,;\n]+/', $value))->map(fn ($item) => trim($item))->filter()->unique()->values()->all();
            Setting::set($key, json_encode($items, JSON_UNESCAPED_UNICODE));
        }

        session()->flash('success', 'Banques et opérateurs synchronisés avec le point de vente.');
    }

    public function saveDocumentSettings(): void
    {
        $this->authorizeSettings();
        $validated = $this->validate([
            'receipt_template' => ['required', Rule::in(array_keys(DocumentPreferences::receiptTemplates()))],
            'receipt_code_type' => ['required', Rule::in(['none', 'qr', 'barcode'])],
            'receipt_code_source' => ['required', Rule::in(['reference', 'company', 'custom'])],
            'receipt_code_custom' => ['nullable', 'string', 'max:120', Rule::requiredIf($this->receipt_code_source === 'custom' && $this->receipt_code_type !== 'none')],
            'receipt_font_family' => ['required', Rule::in(array_keys(DocumentPreferences::fonts()))],
            'receipt_font_size' => ['required', 'integer', 'between:8,18'],
            'receipt_font_weight' => ['required', Rule::in([300, 400, 500, 600, 700, 800])],
            'receipt_paper_width' => ['required', Rule::in(['58', '76', '80'])],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'report_template' => ['required', Rule::in(array_keys(DocumentPreferences::reportTemplates()))],
            'report_font_family' => ['required', Rule::in(array_keys(DocumentPreferences::fonts()))],
            'report_font_size' => ['required', 'integer', 'between:9,16'],
            'report_font_weight' => ['required', Rule::in([300, 400, 500, 600, 700, 800])],
            'company_rccm' => ['nullable', 'string', 'max:100'],
            'company_nif' => ['nullable', 'string', 'max:100'],
            'company_tva' => ['nullable', 'string', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value);
        }

        ActivityLog::log('Configuration', 'Mise à jour des modèles de ticket et de rapport A4.');
        session()->flash('success', 'Les modèles de documents sont appliqués aux tickets et aux rapports.');
    }

    public function addTableZone()
    {
        $this->restaurant_tables[] = ['zone' => '', 'tables' => ''];
    }

    public function removeTableZone($index)
    {
        unset($this->restaurant_tables[$index]);
        $this->restaurant_tables = array_values($this->restaurant_tables);
    }

    public function addKitchenCategory($category)
    {
        if (! in_array($category, $this->kitchen_categories) && ! empty(trim($category))) {
            $this->kitchen_categories[] = trim($category);
        }
    }

    public function removeKitchenCategory($index)
    {
        unset($this->kitchen_categories[$index]);
        $this->kitchen_categories = array_values($this->kitchen_categories);
    }

    public function saveRestaurantSettings()
    {
        $this->authorizeSettings();
        $this->validate([
            'kitchen_print_mode' => 'required|in:browser,ip',
            'kitchen_printer_ip' => 'nullable|string',
        ]);

        Setting::set('restaurant_tables', json_encode($this->restaurant_tables));
        Setting::set('kitchen_print_mode', $this->kitchen_print_mode);
        Setting::set('kitchen_printer_ip', $this->kitchen_printer_ip);
        Setting::set('kitchen_categories', json_encode($this->kitchen_categories));

        session()->flash('success', 'Paramètres du restaurant mis à jour avec succès.');
    }

    public function saveMonthlyModules(): void
    {
        $this->authorizeSettings();
        // Les champs texte acceptent la virgule décimale utilisée en français.
        $this->loyaltyPointValueUsd = str_replace(',', '.', trim((string) $this->loyaltyPointValueUsd));
        $this->loyaltyRequiredServices = preg_replace('/\D+/', '', (string) $this->loyaltyRequiredServices);
        $loyaltyEnabled = (bool) ($this->monthlyModules['loyalty']['enabled'] ?? false);

        // Un champ masqué par le mode courant ne doit ni bloquer ni écraser
        // une valeur précédente valide dans la base locale.
        if ($this->loyaltyMode !== 'points' && ! is_numeric($this->loyaltyPointValueUsd)) {
            $this->loyaltyPointValueUsd = (string) Setting::get('loyalty_point_value_usd', '0.02');
        }
        if ($this->loyaltyMode !== 'services' && $this->loyaltyRequiredServices === '') {
            $this->loyaltyRequiredServices = (string) max(1, (int) Setting::get('loyalty_required_services', 9));
        }

        $rules = [
            'monthlyModules.*.enabled' => ['boolean'],
            'monthlyModules.*.expires_at' => ['required', 'date'],
            'loyaltyMode' => ['required', Rule::in(['points', 'services'])],
        ];

        if ($this->loyaltyMode === 'points') {
            $rules['loyaltyPointValueUsd'] = ['required', 'numeric', 'min:0.0001', 'max:1000'];
            // Une ancienne prestation supprimée ne doit jamais bloquer le mode par points.
            $this->loyaltyRewardProductId = null;
        } else {
            $rules['loyaltyRequiredServices'] = ['required', 'integer', 'min:1', 'max:1000'];
            $rules['loyaltyRewardProductId'] = [
                Rule::requiredIf($loyaltyEnabled),
                'nullable',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query->whereIn('type', ['product', 'service'])->where('is_active', true)),
            ];
        }

        $this->validate($rules, [
            'monthlyModules.*.expires_at.required' => 'Indiquez une date de fin pour chaque module.',
            'monthlyModules.*.expires_at.date' => 'La date de fin d’un module n’est pas valide.',
            'loyaltyPointValueUsd.required' => 'Indiquez la valeur d’un point de fidélité.',
            'loyaltyPointValueUsd.numeric' => 'La valeur du point doit être un nombre, par exemple 0,02.',
            'loyaltyRewardProductId.required' => 'Sélectionnez le produit ou la prestation offert par la carte cadeau.',
            'loyaltyRewardProductId.exists' => 'Le cadeau sélectionné est inactif, incompatible ou n’existe plus.',
        ]);

        $clean = collect(ModuleRegistry::DEFINITIONS)->mapWithKeys(fn ($meta, $key) => [$key => [
            'enabled' => (bool) ($this->monthlyModules[$key]['enabled'] ?? false),
            'expires_at' => $this->monthlyModules[$key]['expires_at'] ?? now()->endOfMonth()->toDateString(),
        ]])->all();
        try {
            DB::transaction(function () use ($clean): void {
                Setting::set('monthly_modules', json_encode($clean, JSON_UNESCAPED_UNICODE));
                Setting::set('loyalty_mode', $this->loyaltyMode);
                Setting::set('loyalty_point_value_usd', (string) $this->loyaltyPointValueUsd);
                Setting::set('loyalty_required_services', (string) $this->loyaltyRequiredServices);
                Setting::set('loyalty_reward_product_id', $this->loyaltyRewardProductId ?: '');
                ActivityLog::log('MODULES_UPDATED', 'Activation mensuelle des modules mise à jour.');
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('loyaltyMode', 'L’enregistrement de la carte de fidélité a échoué. Vos réglages précédents sont conservés.');

            return;
        }

        session()->flash('success', 'Modules et carte de fidélité enregistrés avec succès.');
    }

    public function render()
    {
        $this->authorizeAdminTab($this->activeTab);

        // Chaque onglet charge uniquement son registre; les autres restent légers.
        $users = collect();
        $roles = collect();
        $permissions = collect();
        $logs = collect();
        $availableRoles = collect();
        $paymentMethodsList = collect();
        $loyaltyRewards = collect();

        if ($this->activeTab === 'users') {
            $users = $this->companyUsers()->with('roles')
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->searchUsers}%")
                        ->orWhere('email', 'like', "%{$this->searchUsers}%");
                })
                ->paginate(10, ['*'], 'usersPage');
            $availableRoles = Role::orderBy('name')->get();
        } elseif ($this->activeTab === 'roles') {
            $roles = Role::with('permissions')->get();
            $roleCounts = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $this->companyUsers()->pluck('id'))
                ->select('role_id', DB::raw('count(*) as count'))
                ->groupBy('role_id')
                ->pluck('count', 'role_id');
            foreach ($roles as $role) {
                $role->users_count = $roleCounts[$role->id] ?? 0;
            }
            $permissions = Permission::all();
        } elseif ($this->activeTab === 'logs') {
            $logs = ActivityLog::with('user')
                ->where(function ($q) {
                    $q->where('description', 'like', "%{$this->searchLogs}%")
                        ->orWhere('action', 'like', "%{$this->searchLogs}%");
                })
                ->latest()
                ->paginate(15, ['*'], 'logsPage');
        } elseif ($this->activeTab === 'payment_methods') {
            $paymentMethodsList = PaymentMethod::orderBy('id')->get();
        } elseif ($this->activeTab === 'modules') {
            $loyaltyRewards = Product::query()
                ->whereIn('type', ['product', 'service'])
                ->where('is_active', true)
                ->orderByRaw("case when type = 'service' then 0 else 1 end")
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'type', 'stock_qty']);
        }

        return view('livewire.admin', compact('users', 'roles', 'permissions', 'logs', 'availableRoles', 'paymentMethodsList', 'loyaltyRewards'))->layout('layouts.app');
    }

    private function authorizeSettings(): void
    {
        abort_unless(auth()->user()?->can('manage settings'), 403, 'Vous ne pouvez pas modifier la configuration.');
    }

    /** Limite toute gestion de comptes à l'entreprise résolue. */
    private function companyUsers()
    {
        return User::query()->where('company_id', app(TenantContext::class)->companyId());
    }

    private function authorizeAdminTab(string $tab): void
    {
        $permission = match ($tab) {
            'roles' => 'manage roles',
            'users' => 'manage users',
            'logs' => 'manage administration',
            default => 'manage settings',
        };

        abort_unless(auth()->user()?->can($permission), 403, 'Vous n’avez pas accès à cette section de configuration.');
    }
}
