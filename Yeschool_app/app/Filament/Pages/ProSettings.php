<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Setting;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-settings';
    protected static ?string $title = 'Configuration';

    public $activeTab = 'general';

    // General Settings
    public $school_name;
    public $school_description;
    public $school_id_number;
    public $school_phone;
    public $school_email;
    public $school_address;
    public $school_province;
    public $school_ville;
    public $school_commune;
    public $school_code_ecole;

    // Localisation Settings
    public $default_theme;
    public $date_format;
    public $time_format;
    public $currency;
    public $exchange_rate;

    // Documents
    public $doc_header_text;
    public $doc_footer_text;

    // Users & Roles List
    public $users = [];
    public $availableRoles = [];
    
    // Add User Form
    public $new_user_name;
    public $new_user_email;
    public $new_user_password;
    public $new_user_academic_level = 'none';
    public $new_user_finance_level = 'none';
    public $new_user_hr_level = 'none';
    public $new_user_admin_level = 'none';

    // Edit User Form
    public $editing_user_id = null;
    public $editing_user_name;
    public $editing_user_email;
    public $editing_user_password;
    public $editing_user_academic_level = 'none';
    public $editing_user_finance_level = 'none';
    public $editing_user_hr_level = 'none';
    public $editing_user_admin_level = 'none';

    // Security Settings
    public $password_complexity = 'medium';

    public $app_name;
    public $app_env;
    public $app_debug;
    public $app_url;

    // Local DB settings
    public $db_connection;
    public $db_host;
    public $db_port;
    public $db_database;
    public $db_username;
    public $db_password;

    public $mail_mailer;
    public $mail_host;
    public $mail_port;
    public $mail_username;
    public $mail_password;
    public $mail_encryption;
    public $mail_from_address;
    public $mail_from_name;

    public $aws_access_key_id;
    public $aws_secret_access_key;
    public $aws_default_region;
    public $aws_bucket;

    // Server & System Settings
    public $system_lang;
    public $avatar_storage;
    public $devis_validation;
    public $devis_prefix;
    public $devis_tax;

    // Currencies CRUD state
    public $currencies = [];
    
    // Add Currency Form
    public $new_currency_code;
    public $new_currency_name;
    public $new_currency_symbol;
    public $new_currency_exchange_rate = 1.0000;
    public $new_currency_is_default = false;
    public $new_currency_is_active = true;

    // Edit Currency Form
    public $editing_currency_id = null;
    public $editing_currency_code;
    public $editing_currency_name;
    public $editing_currency_symbol;
    public $editing_currency_exchange_rate;
    public $editing_currency_is_default;
    public $editing_currency_is_active;

    // Electron & Local Server Config
    public $electron_host;
    public $electron_port;
    public $electron_debug = false;
    public $offline_sync_interval;

    // API Config
    public $api_prefix;
    public $api_rate_limit;
    public $api_cors_origin;
    public $api_token_ttl;

    // Campus CRUD state
    public $campuses = [];
    public $new_campus_name;
    public $new_campus_address;
    public $editing_campus_id = null;
    public $editing_campus_name;
    public $editing_campus_address;

    public function mount()
    {
        abort_unless(auth()->user() && auth()->user()->hasAnyRole(['Super Admin']), 403, 'Accès refusé. Réservé aux administrateurs.');

        $this->loadSettings();
        $this->loadUsers();
        $this->loadRoles();
        $this->loadCurrencies();
        $this->loadCampuses();
    }

    public function loadSettings()
    {
        // 1. General settings
        $this->school_name = Setting::get('school_name', 'Yebook SIGS');
        $this->school_description = Setting::get('school_description', 'Système d\'Information de Gestion Scolaire');
        $this->school_id_number = Setting::get('school_id_number', 'ID-123456');
        $this->school_phone = Setting::get('school_phone', '+33 1 23 45 67 89');
        $this->school_email = Setting::get('school_email', 'contact@yebook.fr');
        $this->school_address = Setting::get('school_address', '123 Avenue de l\'Éducation, Paris');
        $this->school_province = Setting::get('school_province', 'Kinshasa');
        $this->school_ville = Setting::get('school_ville', 'Kinshasa');
        $this->school_commune = Setting::get('school_commune', 'Gombe');
        $this->school_code_ecole = Setting::get('school_code_ecole', '120409');

        // 2. Localization
        $this->default_theme = Setting::get('default_theme', 'system');
        $this->date_format = Setting::get('date_format', 'd/m/Y');
        $this->time_format = Setting::get('time_format', 'H:i');
        $this->currency = Setting::get('currency', 'EUR');
        $this->exchange_rate = Setting::get('exchange_rate', '1.0');

        // 3. Document configurations
        $this->doc_header_text = Setting::get('doc_header_text', 'Yebook - Excellence Éducative');
        $this->doc_footer_text = Setting::get('doc_footer_text', 'Généré automatiquement par le SIGS Yebook');

        $this->password_complexity = Setting::get('password_complexity', 'medium');

        // 4. Load from .env (with fallbacks)
        $this->app_name = env('APP_NAME', 'Laravel');
        $this->app_env = env('APP_ENV', 'local');
        $this->app_debug = env('APP_DEBUG', true);
        $this->app_url = env('APP_URL', 'http://localhost');

        $this->db_connection = env('DB_CONNECTION', 'mysql');
        $this->db_host = env('DB_HOST', '127.0.0.1');
        $this->db_port = env('DB_PORT', '3306');
        $this->db_database = env('DB_DATABASE', 'laravel');
        $this->db_username = env('DB_USERNAME', 'root');
        $this->db_password = env('DB_PASSWORD', '');

        $this->mail_mailer = env('MAIL_MAILER', 'log');
        $this->mail_host = env('MAIL_HOST', '127.0.0.1');
        $this->mail_port = env('MAIL_PORT', '2525');
        $this->mail_username = env('MAIL_USERNAME', '');
        $this->mail_password = env('MAIL_PASSWORD', '');
        $this->mail_encryption = env('MAIL_ENCRYPTION', '');
        $this->mail_from_address = env('MAIL_FROM_ADDRESS', 'hello@example.com');
        $this->mail_from_name = env('MAIL_FROM_NAME', '');

        $this->aws_access_key_id = env('AWS_ACCESS_KEY_ID', '');
        $this->aws_secret_access_key = env('AWS_SECRET_ACCESS_KEY', '');
        $this->aws_default_region = env('AWS_DEFAULT_REGION', 'us-east-1');
        $this->aws_bucket = env('AWS_BUCKET', '');

        // 5. Load other server and system settings
        $this->system_lang = Setting::get('system_lang', 'fr');
        $this->avatar_storage = Setting::get('avatar_storage', 'local');
        $this->devis_validation = Setting::get('devis_validation', 'manual');
        $this->devis_prefix = Setting::get('devis_prefix', 'DEV-');
        $this->devis_tax = Setting::get('devis_tax', '16.0');

        // 6. Electron settings
        $this->electron_host = env('ELECTRON_HOST', '127.0.0.1');
        $this->electron_port = env('ELECTRON_PORT', '8000');
        $this->electron_debug = env('ELECTRON_DEBUG', false);
        $this->offline_sync_interval = Setting::get('offline_sync_interval', '5');

        // 7. API settings
        $this->api_prefix = env('API_PREFIX', '/api');
        $this->api_rate_limit = env('API_RATE_LIMIT', '60');
        $this->api_cors_origin = env('API_CORS_ORIGIN', '*');
        $this->api_token_ttl = env('API_TOKEN_TTL', '1440');
    }

    public function loadUsers()
    {
        $this->users = User::latest()->get()->toArray();
    }

    public function loadRoles()
    {
        $this->availableRoles = Role::orderBy('name')->pluck('name')->toArray();
        if (empty($this->availableRoles)) {
            $this->availableRoles = ['Super Admin', 'Proprietaire', 'Directeur General', 'Prefet', 'Staff', 'Caissier', 'Comptable', 'Enseignant', 'RH'];
        }
    }

    public function loadCurrencies()
    {
        $this->currencies = Currency::orderBy('code')->get()->toArray();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveGeneral()
    {
        $this->validate([
            'school_name' => 'required|string',
            'school_email' => 'required|email',
        ]);

        Setting::set('school_name', $this->school_name);
        Setting::set('school_description', $this->school_description);
        Setting::set('school_id_number', $this->school_id_number);
        Setting::set('school_phone', $this->school_phone);
        Setting::set('school_email', $this->school_email);
        Setting::set('school_address', $this->school_address);
        Setting::set('school_province', $this->school_province);
        Setting::set('school_ville', $this->school_ville);
        Setting::set('school_commune', $this->school_commune);
        Setting::set('school_code_ecole', $this->school_code_ecole);

        session()->flash('success', 'Informations générales de l\'établissement sauvegardées.');
    }

    public function updatedCurrency($value)
    {
        $target = Currency::where('code', $value)->first();
        if ($target) {
            $this->exchange_rate = $target->exchange_rate;
        }
    }

    public function saveLocalisation()
    {
        Setting::set('default_theme', $this->default_theme);
        Setting::set('date_format', $this->date_format);
        Setting::set('time_format', $this->time_format);
        Setting::set('currency', $this->currency);
        Setting::set('exchange_rate', $this->exchange_rate);

        // Sync default currency in DB
        $targetCurrency = Currency::where('code', $this->currency)->first();
        if (!$targetCurrency) {
            $symbol = $this->currency === 'EUR' ? '€' : ($this->currency === 'USD' ? '$' : ($this->currency === 'XOF' ? 'CFA' : $this->currency));
            $name = $this->currency === 'EUR' ? 'Euro' : ($this->currency === 'USD' ? 'Dollar' : ($this->currency === 'XOF' ? 'FCFA' : $this->currency));
            $targetCurrency = Currency::create([
                'code' => $this->currency,
                'name' => $name,
                'symbol' => $symbol,
                'exchange_rate' => $this->exchange_rate ?: 1.0000,
                'is_default' => true,
                'is_active' => true
            ]);
        }

        Currency::where('id', '!=', $targetCurrency->id)->update(['is_default' => false]);
        $targetCurrency->update([
            'is_default' => true,
            'exchange_rate' => $this->exchange_rate ?: 1.0000,
            'is_active' => true
        ]);

        $this->dispatch('theme-updated', theme: $this->default_theme);
        $this->loadCurrencies();

        session()->flash('success', 'Options de localisation et d\'affichage sauvegardées.');
    }

    public function saveDocuments()
    {
        Setting::set('doc_header_text', $this->doc_header_text);
        Setting::set('doc_footer_text', $this->doc_footer_text);

        session()->flash('success', 'Modèles d\'en-tête et pied de page mis à jour.');
    }

    public function saveSecurity()
    {
        Setting::set('password_complexity', $this->password_complexity);
        session()->flash('success', 'Règles de sécurité des comptes enregistrées.');
    }

    public function getModuleLevels($user)
    {
        $levels = [
            'academic' => 'none',
            'finance' => 'none',
            'hr' => 'none',
            'admin' => 'none',
        ];

        if (!$user) return $levels;

        // Admin level
        if ($user->hasRole('Super Admin')) {
            $levels['admin'] = 'manager';
        }

        // Academic level
        if ($user->hasAnyRole(['Directeur General', 'Directeur Général', 'Prefet'])) {
            $levels['academic'] = 'manager';
        } elseif ($user->hasAnyRole(['Enseignant', 'Titulaire', 'Surveillant'])) {
            $levels['academic'] = 'user';
        }

        // Finance level
        if ($user->hasRole('Comptable')) {
            $levels['finance'] = 'manager';
        } elseif ($user->hasRole('Caissier')) {
            $levels['finance'] = 'user';
        }

        // HR level
        if ($user->hasRole('RH') || $user->hasRole('Responsable RH')) {
            $levels['hr'] = 'manager';
        } elseif ($user->hasAnyRole(['Staff', 'Secretaire', 'Secrétaire'])) {
            $levels['hr'] = 'user';
        }

        return $levels;
    }

    public function getRolesFromLevels($academic, $finance, $hr, $admin)
    {
        $roles = [];

        // Admin
        if ($admin === 'manager') {
            $roles[] = 'Super Admin';
        }

        // Academic
        if ($academic === 'manager') {
            $roles[] = 'Directeur General';
        } elseif ($academic === 'user') {
            $roles[] = 'Enseignant';
        }

        // Finance
        if ($finance === 'manager') {
            $roles[] = 'Comptable';
        } elseif ($finance === 'user') {
            $roles[] = 'Caissier';
        }

        // HR
        if ($hr === 'manager') {
            $roles[] = 'RH';
        } elseif ($hr === 'user') {
            $roles[] = 'Staff';
        }

        // Default to Staff if no roles are assigned
        if (empty($roles)) {
            $roles[] = 'Staff';
        }

        return $roles;
    }

    // CRUD User - Create
    public function createUser()
    {
        $this->validate([
            'new_user_name' => 'required|string|max:255',
            'new_user_email' => 'required|string|email|max:255|unique:users,email',
            'new_user_password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $this->new_user_name,
            'email' => $this->new_user_email,
            'password' => Hash::make($this->new_user_password),
            'type' => 'admin',
            'is_active' => true,
        ]);

        $roles = $this->getRolesFromLevels(
            $this->new_user_academic_level,
            $this->new_user_finance_level,
            $this->new_user_hr_level,
            $this->new_user_admin_level
        );

        $user->syncRoles($roles);

        $this->reset([
            'new_user_name', 'new_user_email', 'new_user_password',
            'new_user_academic_level', 'new_user_finance_level', 'new_user_hr_level', 'new_user_admin_level'
        ]);
        $this->loadUsers();
        session()->flash('success_users', 'Nouvel utilisateur créé avec succès.');
    }

    // CRUD User - Edit
    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->editing_user_id = $user->id;
        $this->editing_user_name = $user->name;
        $this->editing_user_email = $user->email;
        $this->editing_user_password = '';
        
        $levels = $this->getModuleLevels($user);
        $this->editing_user_academic_level = $levels['academic'];
        $this->editing_user_finance_level = $levels['finance'];
        $this->editing_user_hr_level = $levels['hr'];
        $this->editing_user_admin_level = $levels['admin'];
    }

    public function cancelEditUser()
    {
        $this->reset([
            'editing_user_id', 'editing_user_name', 'editing_user_email', 'editing_user_password',
            'editing_user_academic_level', 'editing_user_finance_level', 'editing_user_hr_level', 'editing_user_admin_level'
        ]);
    }

    // CRUD User - Update
    public function updateUser()
    {
        $this->validate([
            'editing_user_name' => 'required|string|max:255',
            'editing_user_email' => 'required|string|email|max:255|unique:users,email,' . $this->editing_user_id,
        ]);

        $user = User::findOrFail($this->editing_user_id);
        $user->name = $this->editing_user_name;
        $user->email = $this->editing_user_email;

        if ($this->editing_user_password) {
            $this->validate([
                'editing_user_password' => 'string|min:8',
            ]);
            $user->password = Hash::make($this->editing_user_password);
        }

        $user->save();

        $roles = $this->getRolesFromLevels(
            $this->editing_user_academic_level,
            $this->editing_user_finance_level,
            $this->editing_user_hr_level,
            $this->editing_user_admin_level
        );

        $user->syncRoles($roles);

        $this->cancelEditUser();
        $this->loadUsers();
        session()->flash('success_users', 'Utilisateur mis à jour avec succès.');
    }

    // CRUD User - Delete
    public function deleteUser($id)
    {
        if (\Illuminate\Support\Facades\Auth::id() == $id) {
            session()->flash('error_users', 'Vous ne pouvez pas supprimer l\'utilisateur actuellement connecté.');
            return;
        }

        User::findOrFail($id)->delete();
        $this->loadUsers();
        session()->flash('success_users', 'Utilisateur supprimé.');
    }

    // CRUD Currencies - Create
    public function createCurrency()
    {
        $this->validate([
            'new_currency_code' => 'required|string|max:10|unique:currencies,code',
            'new_currency_name' => 'required|string|max:255',
            'new_currency_exchange_rate' => 'required|numeric|min:0',
        ]);

        if ($this->new_currency_is_default) {
            Currency::query()->update(['is_default' => false]);
            // Sync to localization settings
            Setting::set('currency', strtoupper($this->new_currency_code));
            Setting::set('exchange_rate', $this->new_currency_exchange_rate);
            $this->currency = strtoupper($this->new_currency_code);
            $this->exchange_rate = $this->new_currency_exchange_rate;
        }

        Currency::create([
            'code' => strtoupper($this->new_currency_code),
            'name' => $this->new_currency_name,
            'symbol' => $this->new_currency_symbol,
            'exchange_rate' => $this->new_currency_exchange_rate,
            'is_default' => $this->new_currency_is_default,
            'is_active' => $this->new_currency_is_active,
        ]);

        $this->reset(['new_currency_code', 'new_currency_name', 'new_currency_symbol', 'new_currency_exchange_rate', 'new_currency_is_default', 'new_currency_is_active']);
        $this->loadCurrencies();
        session()->flash('success_currencies', 'Nouvelle devise ajoutée.');
    }

    // CRUD Currencies - Edit
    public function editCurrency($id)
    {
        $curr = Currency::findOrFail($id);
        $this->editing_currency_id = $curr->id;
        $this->editing_currency_code = $curr->code;
        $this->editing_currency_name = $curr->name;
        $this->editing_currency_symbol = $curr->symbol;
        $this->editing_currency_exchange_rate = $curr->exchange_rate;
        $this->editing_currency_is_default = $curr->is_default;
        $this->editing_currency_is_active = $curr->is_active;
    }

    public function cancelEditCurrency()
    {
        $this->reset(['editing_currency_id', 'editing_currency_code', 'editing_currency_name', 'editing_currency_symbol', 'editing_currency_exchange_rate', 'editing_currency_is_default', 'editing_currency_is_active']);
    }

    // CRUD Currencies - Update
    public function updateCurrency()
    {
        $this->validate([
            'editing_currency_code' => 'required|string|max:10|unique:currencies,code,' . $this->editing_currency_id,
            'editing_currency_name' => 'required|string|max:255',
            'editing_currency_exchange_rate' => 'required|numeric|min:0',
        ]);

        if ($this->editing_currency_is_default) {
            Currency::where('id', '!=', $this->editing_currency_id)->update(['is_default' => false]);
            // Sync to localization settings
            Setting::set('currency', strtoupper($this->editing_currency_code));
            Setting::set('exchange_rate', $this->editing_currency_exchange_rate);
            $this->currency = strtoupper($this->editing_currency_code);
            $this->exchange_rate = $this->editing_currency_exchange_rate;
        }

        $curr = Currency::findOrFail($this->editing_currency_id);
        $curr->update([
            'code' => strtoupper($this->editing_currency_code),
            'name' => $this->editing_currency_name,
            'symbol' => $this->editing_currency_symbol,
            'exchange_rate' => $this->editing_currency_exchange_rate,
            'is_default' => $this->editing_currency_is_default,
            'is_active' => $this->editing_currency_is_active,
        ]);

        $this->cancelEditCurrency();
        $this->loadCurrencies();
        session()->flash('success_currencies', 'Devise mise à jour avec succès.');
    }

    // CRUD Currencies - Delete
    public function deleteCurrency($id)
    {
        $curr = Currency::findOrFail($id);
        if ($curr->is_default) {
            session()->flash('error_currencies', 'Vous ne pouvez pas supprimer la devise par défaut.');
            return;
        }
        $curr->delete();
        $this->loadCurrencies();
        session()->flash('success_currencies', 'Devise supprimée avec succès.');
    }

    // Save Environment & Server Configuration
    public function saveSystemAndEnv()
    {
        // 1. Update Env values
        $this->updateEnvFile('APP_NAME', $this->app_name);
        $this->updateEnvFile('APP_ENV', $this->app_env);
        $this->updateEnvFile('APP_DEBUG', $this->app_debug ? 'true' : 'false');
        $this->updateEnvFile('APP_URL', $this->app_url);

        $this->updateEnvFile('DB_CONNECTION', $this->db_connection);
        $this->updateEnvFile('DB_HOST', $this->db_host);
        $this->updateEnvFile('DB_PORT', $this->db_port);
        $this->updateEnvFile('DB_DATABASE', $this->db_database);
        $this->updateEnvFile('DB_USERNAME', $this->db_username);
        $this->updateEnvFile('DB_PASSWORD', $this->db_password);

        $this->updateEnvFile('MAIL_MAILER', $this->mail_mailer);
        $this->updateEnvFile('MAIL_HOST', $this->mail_host);
        $this->updateEnvFile('MAIL_PORT', $this->mail_port);
        $this->updateEnvFile('MAIL_USERNAME', $this->mail_username);
        $this->updateEnvFile('MAIL_PASSWORD', $this->mail_password);
        $this->updateEnvFile('MAIL_ENCRYPTION', $this->mail_encryption ?: 'null');
        $this->updateEnvFile('MAIL_FROM_ADDRESS', $this->mail_from_address);
        $this->updateEnvFile('MAIL_FROM_NAME', $this->mail_from_name);

        $this->updateEnvFile('AWS_ACCESS_KEY_ID', $this->aws_access_key_id);
        $this->updateEnvFile('AWS_SECRET_ACCESS_KEY', $this->aws_secret_access_key);
        $this->updateEnvFile('AWS_DEFAULT_REGION', $this->aws_default_region);
        $this->updateEnvFile('AWS_BUCKET', $this->aws_bucket);

        // 2. Update Database system settings
        Setting::set('system_lang', $this->system_lang);
        Setting::set('avatar_storage', $this->avatar_storage);
        Setting::set('devis_validation', $this->devis_validation);
        Setting::set('devis_prefix', $this->devis_prefix);
        Setting::set('devis_tax', $this->devis_tax);

        // 3. Electron and Local Server
        $this->updateEnvFile('ELECTRON_HOST', $this->electron_host);
        $this->updateEnvFile('ELECTRON_PORT', $this->electron_port);
        $this->updateEnvFile('ELECTRON_DEBUG', $this->electron_debug ? 'true' : 'false');
        Setting::set('offline_sync_interval', $this->offline_sync_interval);

        // 4. API Configurations
        $this->updateEnvFile('API_PREFIX', $this->api_prefix);
        $this->updateEnvFile('API_RATE_LIMIT', $this->api_rate_limit);
        $this->updateEnvFile('API_CORS_ORIGIN', $this->api_cors_origin);
        $this->updateEnvFile('API_TOKEN_TTL', $this->api_token_ttl);

        session()->flash('success_server', 'Configurations système, serveurs local & Electron et variables d\'environnement .env mises à jour.');
    }

    // Direct helper method to edit .env files dynamically
    protected function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            if (strpos($content, "{$key}=") !== false) {
                // If it contains quotes or special characters or spaces, wrap it in double quotes
                $escapedValue = (strpos($value, ' ') !== false || strpos($value, '=') !== false || strpos($value, '$') !== false) 
                    ? '"' . $value . '"' 
                    : $value;
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }

            file_put_contents($path, $content);
        }
    }

    public function loadCampuses()
    {
        $this->campuses = \App\Models\Campus::all()->toArray();
    }

    public function addCampus()
    {
        $this->validate([
            'new_campus_name' => 'required|string|max:255',
            'new_campus_address' => 'nullable|string',
        ]);

        $school = auth()->user()->school ?? \App\Models\School::first();
        if (!$school) {
            session()->flash('error_campuses', 'Aucun établissement trouvé.');
            return;
        }

        \App\Models\Campus::create([
            'school_id' => $school->id,
            'name' => $this->new_campus_name,
            'address' => $this->new_campus_address,
        ]);

        $this->reset(['new_campus_name', 'new_campus_address']);
        $this->loadCampuses();
        session()->flash('success_campuses', 'Nouvelle succursale ajoutée.');
    }

    public function editCampus($id)
    {
        $c = \App\Models\Campus::findOrFail($id);
        $this->editing_campus_id = $c->id;
        $this->editing_campus_name = $c->name;
        $this->editing_campus_address = $c->address;
    }

    public function cancelEditCampus()
    {
        $this->reset(['editing_campus_id', 'editing_campus_name', 'editing_campus_address']);
    }

    public function updateCampus()
    {
        $this->validate([
            'editing_campus_name' => 'required|string|max:255',
            'editing_campus_address' => 'nullable|string',
        ]);

        $c = \App\Models\Campus::findOrFail($this->editing_campus_id);
        $c->update([
            'name' => $this->editing_campus_name,
            'address' => $this->editing_campus_address,
        ]);

        $this->cancelEditCampus();
        $this->loadCampuses();
        session()->flash('success_campuses', 'Succursale mise à jour.');
    }

    public function deleteCampus($id)
    {
        \App\Models\Campus::findOrFail($id)->delete();
        $this->loadCampuses();
        session()->flash('success_campuses', 'Succursale supprimée.');
    }
}
