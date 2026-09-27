<?php

namespace App\Livewire\System;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\SequenceService;
use App\Support\Modules;
use App\Support\Tenant;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Master système : pilotage global de l'ERP (réservé Super Admin /
 * Administrateur). Société, documents, modules, numérotation, règles
 * métier, utilisateurs, rôles & droits, audit et maintenance.
 */
#[Title('Master système')]
class Master extends Component
{
    use InteractsWithUi, WithFileUploads, WithPagination;

    #[Url(as: 'onglet')]
    public string $tab = 'societe';

    // Société
    public array $company = [];

    public $logo = null;

    public $seal = null;

    // Documents
    public array $templates = [];

    public string $quoteTerms = '';

    public string $invoiceTerms = '';

    // Modules & numérotation
    public array $disabled = [];

    public array $numbering = [];

    // Règles métier
    public string $depositRate = '50';

    public array $payroll = [];

    public array $rate = ['quote_currency' => 'CDF', 'rate' => '', 'effective_date' => ''];

    // Utilisateurs
    public string $userSearch = '';

    public bool $showUser = false;

    public ?int $userId = null;

    public array $user = [];

    // Rôles
    public string $role = 'Direction Générale';

    public array $grants = [];

    public string $newRole = '';

    // Audit
    public string $auditSearch = '';

    public function mount(): void
    {
        $this->authorizeModule('system');
        $c = company();
        $this->company = [
            'name' => $c->name, 'legal_name' => $c->legal_name, 'registration_number' => $c->registration_number, 'id_nat' => data_get($c->settings, 'id_nat'),
            'tax_number' => $c->tax_number, 'vat_number' => $c->vat_number, 'email' => $c->email, 'phone' => $c->phone, 'website' => data_get($c->settings, 'website'),
            'slogan' => data_get($c->settings, 'slogan'), 'line1' => data_get($c->address, 'line1'), 'line2' => data_get($c->address, 'line2'), 'city' => data_get($c->address, 'city'),
            'bank_details' => data_get($c->settings, 'bank_details'), 'document_footer' => $c->document_footer, 'base_currency' => $c->base_currency,
        ];
        $this->templates = array_merge(['quote' => 'moderne', 'invoice' => 'moderne', 'purchase_order' => 'classique'], (array) data_get($c->settings, 'templates', []));
        $this->quoteTerms = (string) data_get($c->settings, 'quote_terms', '');
        $this->invoiceTerms = (string) data_get($c->settings, 'invoice_terms', '');
        $this->disabled = (array) data_get($c->settings, 'modules_disabled', []);
        $this->numbering = array_merge(SequenceService::DEFAULTS, (array) data_get($c->settings, 'numbering', []));
        $this->depositRate = (string) data_get($c->settings, 'orders.deposit_rate', 50);
        $this->payroll = array_replace(config('erp.payroll'), (array) data_get($c->settings, 'payroll', []));
        $this->payroll['exchange_rate'] ??= 2847;
        $this->rate['effective_date'] = now()->toDateString();
        $this->loadGrants();
    }

    private function canEdit(): bool
    {
        return auth()->user()->isMaster() || auth()->user()->can('system.manage');
    }

    private function guard(): void
    {
        abort_unless($this->canEdit(), 403, 'Modification réservée à l’administration.');
    }

    // ── 1. Société ──────────────────────────────────────────────────────────
    public function saveCompany(): void
    {
        $this->guard();
        $this->validate([
            'company.name' => ['required', 'string', 'max:160'], 'company.legal_name' => ['nullable', 'string', 'max:255'], 'company.registration_number' => ['nullable', 'string', 'max:80'],
            'company.id_nat' => ['nullable', 'string', 'max:80'], 'company.tax_number' => ['nullable', 'string', 'max:80'], 'company.email' => ['nullable', 'email'], 'company.phone' => ['nullable', 'string', 'max:60'],
            'company.website' => ['nullable', 'string', 'max:120'], 'company.slogan' => ['nullable', 'string', 'max:160'], 'company.line1' => ['nullable', 'string', 'max:200'], 'company.line2' => ['nullable', 'string', 'max:200'],
            'company.city' => ['nullable', 'string', 'max:80'], 'company.bank_details' => ['nullable', 'string', 'max:500'], 'company.document_footer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'], 'seal' => ['nullable', 'image', 'max:2048'],
        ], [], ['company.name' => 'nom']);
        $c = company();
        $settings = $c->settings ?? [];
        foreach (['id_nat', 'website', 'slogan', 'bank_details'] as $key) {
            $settings[$key] = $this->company[$key];
        }
        $data = [
            'name' => $this->company['name'], 'legal_name' => $this->company['legal_name'], 'registration_number' => $this->company['registration_number'], 'tax_number' => $this->company['tax_number'],
            'vat_number' => $this->company['vat_number'], 'email' => $this->company['email'], 'phone' => $this->company['phone'], 'document_footer' => $this->company['document_footer'],
            'address' => ['line1' => $this->company['line1'], 'line2' => $this->company['line2'], 'city' => $this->company['city'], 'country' => 'République démocratique du Congo'],
            'settings' => $settings,
        ];
        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('branding', 'public');
        }
        if ($this->seal) {
            $data['seal_path'] = $this->seal->store('branding', 'public');
        }
        $c->update($data);
        Tenant::flush();
        $this->reset(['logo', 'seal']);
        $this->toast('Identité de la société enregistrée.');
    }

    // ── 2. Documents ────────────────────────────────────────────────────────
    public function saveDocuments(): void
    {
        $this->guard();
        $valid = array_keys(config('erp.document_templates'));
        $this->validate(['templates.*' => ['required', Rule::in($valid)], 'quoteTerms' => ['nullable', 'string', 'max:2000'], 'invoiceTerms' => ['nullable', 'string', 'max:2000']]);
        Tenant::putSetting('templates', $this->templates);
        Tenant::putSetting('quote_terms', $this->quoteTerms);
        Tenant::putSetting('invoice_terms', $this->invoiceTerms);
        $this->toast('Modèles de documents enregistrés.');
    }

    // ── 3. Modules ──────────────────────────────────────────────────────────
    public function toggleModule(string $key): void
    {
        $this->guard();
        abort_if(! Modules::get($key) || (Modules::get($key)['core'] ?? false), 422);
        $this->disabled = in_array($key, $this->disabled, true) ? array_values(array_diff($this->disabled, [$key])) : [...$this->disabled, $key];
        Tenant::putSetting('modules_disabled', $this->disabled);
        $this->toast(in_array($key, $this->disabled, true) ? 'Module désactivé pour toute la société.' : 'Module activé.');
    }

    // ── 4. Numérotation ─────────────────────────────────────────────────────
    public function saveNumbering(): void
    {
        $this->guard();
        $this->validate(['numbering.*' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/']], ['numbering.*.regex' => 'Préfixe en majuscules et chiffres uniquement.']);
        Tenant::putSetting('numbering', $this->numbering);
        $this->toast('Préfixes enregistrés : ils s’appliquent aux prochaines pièces.');
    }

    // ── 5. Règles métier ────────────────────────────────────────────────────
    public function saveRules(): void
    {
        $this->guard();
        $this->validate([
            'depositRate' => ['required', 'numeric', 'between:0,100'],
            'payroll.cnss_employee_rate' => ['required', 'numeric', 'between:0,30'], 'payroll.cnss_employer_rate' => ['required', 'numeric', 'between:0,30'],
            'payroll.inpp_rate' => ['required', 'numeric', 'between:0,10'], 'payroll.onem_rate' => ['required', 'numeric', 'between:0,5'],
            'payroll.ipr_cap_rate' => ['required', 'numeric', 'between:0,60'], 'payroll.ipr_dependent_reduction' => ['required', 'numeric', 'between:0,10'],
            'payroll.overtime_multiplier' => ['required', 'numeric', 'between:1,3'], 'payroll.working_days' => ['required', 'integer', 'between:20,31'],
            'payroll.exchange_rate' => ['required', 'numeric', 'gt:0'], 'payroll.ipr_brackets.*.rate' => ['required', 'numeric', 'between:0,60'],
            'payroll.ipr_brackets.*.up_to' => ['nullable', 'numeric', 'min:0'],
        ]);
        Tenant::putSetting('orders.deposit_rate', (float) $this->depositRate);
        $payroll = $this->payroll;
        $payroll['ipr_brackets'] = array_values(array_map(fn ($b) => ['up_to' => ($b['up_to'] ?? '') === '' ? null : (float) $b['up_to'], 'rate' => (float) $b['rate']], $payroll['ipr_brackets']));
        Tenant::putSetting('payroll', $payroll);
        $this->toast('Règles métier enregistrées.');
    }

    public function addRate(): void
    {
        $this->guard();
        $this->validate(['rate.quote_currency' => ['required', 'in:CDF,EUR'], 'rate.rate' => ['required', 'numeric', 'gt:0'], 'rate.effective_date' => ['required', 'date']]);
        ExchangeRate::updateOrCreate(
            ['company_id' => company_id(), 'base_currency' => 'USD', 'quote_currency' => $this->rate['quote_currency'], 'effective_date' => $this->rate['effective_date']],
            ['rate' => $this->rate['rate'], 'created_by' => auth()->id()]
        );
        if ($this->rate['quote_currency'] === 'CDF') {
            $this->payroll['exchange_rate'] = (float) $this->rate['rate'];
        }
        $this->rate['rate'] = '';
        $this->toast('Taux de change enregistré.');
    }

    // ── 6. Utilisateurs ─────────────────────────────────────────────────────
    public function editUser(?int $id = null): void
    {
        $this->guard();
        $u = $id ? User::findOrFail($id) : null;
        $this->userId = $u?->id;
        $this->user = ['name' => $u?->name ?? '', 'email' => $u?->email ?? '', 'phone' => $u?->phone ?? '', 'job_title' => $u?->job_title ?? '', 'role' => $u?->roleLabel() ?? 'Commercial', 'is_active' => $u?->is_active ?? true, 'password' => '', 'employee_id' => $u?->employee?->id];
        $this->showUser = true;
    }

    public function saveUser(): void
    {
        $this->guard();
        $this->validate([
            'user.name' => ['required', 'string', 'max:120'], 'user.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->userId)],
            'user.phone' => ['nullable', 'string', 'max:40'], 'user.job_title' => ['nullable', 'string', 'max:120'], 'user.role' => ['required', 'exists:roles,name'],
            'user.is_active' => ['boolean'], 'user.password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8'], 'user.employee_id' => ['nullable', 'integer'],
        ], [], ['user.name' => 'nom', 'user.password' => 'mot de passe', 'user.role' => 'rôle']);
        abort_if($this->userId === auth()->id() && ! $this->user['is_active'], 422, 'Vous ne pouvez pas désactiver votre propre compte.');
        if ($this->user['role'] === 'Super Admin' && ! auth()->user()->isMaster()) {
            $this->addError('user.role', 'Seul un Super Admin peut attribuer ce rôle.');

            return;
        }

        DB::transaction(function () {
            $data = ['name' => $this->user['name'], 'email' => $this->user['email'], 'phone' => $this->user['phone'] ?: null, 'job_title' => $this->user['job_title'] ?: null, 'is_active' => $this->user['is_active']];
            if ($this->user['password']) {
                $data['password'] = $this->user['password'];
            }
            $user = $this->userId
                ? tap(User::findOrFail($this->userId))->update($data)
                : User::create($data + ['current_company_id' => company_id(), 'current_branch_id' => auth()->user()->current_branch_id, 'locale' => 'fr', 'email_verified_at' => now()]);
            if (! $this->userId) {
                $user->companies()->syncWithoutDetaching([company_id() => ['branch_id' => auth()->user()->current_branch_id, 'is_active' => true]]);
            }
            $user->syncRoles([$this->user['role']]);
            Employee::where('user_id', $user->id)->update(['user_id' => null]);
            if ($this->user['employee_id']) {
                Employee::where('company_id', company_id())->whereKey($this->user['employee_id'])->update(['user_id' => $user->id]);
            }
        });
        $this->showUser = false;
        $this->toast('Utilisateur enregistré.');
    }

    // ── 7. Rôles & droits ───────────────────────────────────────────────────
    public function updatedRole(): void
    {
        $this->loadGrants();
    }

    private function loadGrants(): void
    {
        $role = Role::where('name', $this->role)->first();
        $this->grants = $role ? $role->permissions->pluck('name')->mapWithKeys(fn ($p) => [str_replace('.', '__', $p) => true])->all() : [];
    }

    public function saveGrants(): void
    {
        $this->guard();
        abort_if($this->role === 'Super Admin', 422, 'Le rôle Super Admin dispose toujours de tous les droits.');
        $role = Role::where('name', $this->role)->firstOrFail();
        $permissions = collect($this->grants)->filter()->keys()->map(fn ($k) => str_replace('__', '.', $k))->filter(fn ($p) => in_array($p, Modules::permissions(), true))->values();
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role->syncPermissions($permissions->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->toast("Droits du rôle « {$this->role} » enregistrés.");
    }

    public function createRole(): void
    {
        $this->guard();
        $this->validate(['newRole' => ['required', 'string', 'max:60', 'unique:roles,name']], [], ['newRole' => 'nom du rôle']);
        Role::create(['name' => $this->newRole, 'guard_name' => 'web'])->givePermissionTo(['dashboard.view', 'messages.view']);
        $this->role = $this->newRole;
        $this->newRole = '';
        $this->loadGrants();
        $this->toast('Rôle créé : définissez ses droits.');
    }

    // ── 8. Maintenance ──────────────────────────────────────────────────────
    public function clearCaches(): void
    {
        $this->guard();
        Artisan::call('optimize:clear');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->toast('Caches vidés.');
    }

    public function backup(): ?StreamedResponse
    {
        abort_unless(auth()->user()->isMaster(), 403);
        if (config('database.default') !== 'sqlite') {
            $this->toast('Sauvegarde intégrée disponible uniquement en SQLite : utilisez mysqldump pour MySQL.', 'warning');

            return null;
        }
        $path = config('database.connections.sqlite.database');

        return response()->streamDownload(function () use ($path) {
            readfile($path);
        }, 'sauvegarde-sogeref-'.now()->format('Y-m-d-His').'.sqlite');
    }

    public function render()
    {
        $data = ['canEdit' => $this->canEdit()];
        if ($this->tab === 'utilisateurs') {
            $data['users'] = User::with('roles', 'employee')->whereHas('companies', fn ($q) => $q->where('companies.id', company_id()))
                ->when($this->userSearch, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->userSearch}%")->orWhere('email', 'like', "%{$this->userSearch}%")))
                ->orderBy('name')->get();
            $data['employees'] = Employee::where('company_id', company_id())->where('pay_type', 'monthly')->orderBy('last_name')->get();
        }
        if ($this->tab === 'audit') {
            $data['logs'] = AuditLog::with('user')->where('company_id', company_id())
                ->when($this->auditSearch, fn ($q) => $q->where(fn ($q) => $q->where('auditable_type', 'like', "%{$this->auditSearch}%")->orWhere('event', 'like', "%{$this->auditSearch}%")))
                ->latest('id')->paginate(25);
        }
        if ($this->tab === 'regles') {
            $data['rates'] = ExchangeRate::where('company_id', company_id())->latest('effective_date')->limit(10)->get();
        }
        if ($this->tab === 'systeme') {
            $data['info'] = [
                'Application' => config('app.name').' · environnement '.app()->environment(),
                'Laravel' => app()->version(), 'PHP' => PHP_VERSION, 'Livewire' => InstalledVersions::getPrettyVersion('livewire/livewire'),
                'Base de données' => config('database.default').(config('database.default') === 'sqlite' ? ' · '.round(@filesize(config('database.connections.sqlite.database')) / 1048576, 2).' Mo' : ''),
                'Fuseau horaire' => config('app.timezone'), 'Utilisateurs actifs' => User::where('is_active', true)->count(),
                'Écritures comptables' => DB::table('journal_entries')->where('company_id', company_id())->count(),
                'Pièces numérotées' => DB::table('sequences')->where('company_id', company_id())->sum(DB::raw('next_value - 1')),
            ];
        }
        $data['roles'] = Role::orderBy('name')->pluck('name');
        $data['sequences'] = DB::table('sequences')->where('company_id', company_id())->where('year', now()->year)->pluck('next_value', 'key');

        return view('livewire.system.master', $data);
    }
}
