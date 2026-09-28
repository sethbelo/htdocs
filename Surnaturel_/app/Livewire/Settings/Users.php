<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Title('Paramètres — Utilisateurs')]
class Users extends Component
{
    use InteractsWithToasts, WithPagination;

    public string $state = '';

    public ?int $siteFilter = null;

    public string $search = '';

    public string $role = '';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var list<string> */
    public array $roles = [];

    /** @var list<int|string> */
    public array $siteIds = [];

    public ?string $generatedPassword = null;

    public function create(): void
    {
        $this->authorize('users.create');
        $this->editingId = null;
        $this->form = ['name' => '', 'username' => '', 'email' => '', 'phone' => '', 'badge_code' => '', 'default_site_id' => auth()->user()->default_site_id, 'session_timeout_minutes' => '', 'is_active' => true];
        $this->roles = [];
        $this->siteIds = [auth()->user()->default_site_id];
        $this->modal('user')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('users.update');
        $user = User::query()->findOrFail($id);
        $this->editingId = $id;
        $this->form = $user->only(['name', 'username', 'email', 'phone', 'badge_code', 'default_site_id', 'session_timeout_minutes', 'is_active']);
        $this->roles = $user->getRoleNames()->all();
        $this->siteIds = $user->sites()->pluck('sites.id')->all();
        $this->modal('user')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'users.update' : 'users.create');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.username' => ['required', 'alpha_dash:ascii', 'max:50', Rule::unique('users', 'username')->ignore($this->editingId)],
            'form.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.badge_code' => ['nullable', 'string', 'max:64', Rule::unique('users', 'badge_code')->ignore($this->editingId)],
            'form.default_site_id' => ['nullable', 'exists:sites,id'],
            'form.session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'form.is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
            'siteIds' => ['required', 'array', 'min:1'],
            'siteIds.*' => ['exists:sites,id'],
        ], [], ['roles' => 'rôles', 'siteIds' => 'sièges', 'form.username' => 'nom d’utilisateur'])['form'];

        if (array_intersect(['super-admin', 'directeur-general'], $this->roles) && ! auth()->user()->hasAnyRole(['super-admin', 'directeur-general'])) {
            $this->addError('roles', 'Seuls le Directeur Général et le Master administrateur peuvent attribuer ce rôle.');

            return;
        }

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);
            $user->update($data);
        } else {
            $this->generatedPassword = Str::password(12, symbols: false);
            $user = User::query()->create($data + ['password' => $this->generatedPassword, 'must_change_password' => true]);
        }

        $user->syncRoles($this->roles);
        $user->sites()->sync($this->siteIds);

        $this->modal('user')->close();
        $this->toast('Utilisateur enregistré.');

        if ($this->generatedPassword) {
            $this->modal('password')->show();
        }
    }

    public function resetPassword(int $id): void
    {
        $this->authorize('users.update');
        $this->generatedPassword = Str::password(12, symbols: false);
        User::query()->findOrFail($id)->forceFill(['password' => Hash::make($this->generatedPassword), 'must_change_password' => true])->save();
        $this->modal('password')->show();
    }

    public ?int $pinUserId = null;

    public string $newPin = '';

    public function openPin(int $id): void
    {
        $this->authorize('users.update');
        $this->pinUserId = $id;
        $this->newPin = (string) random_int(1000, 9999);
        $this->modal('pin')->show();
    }

    /**
     * Définit le PIN (obligatoire pour les connexions « Caisse / Atelier » et « Badge »).
     */
    public function savePin(): void
    {
        $this->authorize('users.update');
        $this->validate(['newPin' => ['required', 'digits_between:4,6']], [], ['newPin' => 'PIN']);
        User::query()->findOrFail($this->pinUserId)->setPin($this->newPin);
        $this->reset('pinUserId', 'newPin');
        $this->modal('pin')->close();
        $this->toast('PIN enregistré. Communiquez-le à l’utilisateur de façon sécurisée.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('users.update');
        $user = User::query()->findOrFail($id);
        abort_if($user->is(auth()->user()), 422);
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function closePassword(): void
    {
        $this->generatedPassword = null;
        $this->modal('password')->close();
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::query()->with('roles', 'sites')
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->when($this->role, fn ($q) => $q->role($this->role))
                ->when($this->state !== '', fn ($q) => $q->where('is_active', $this->state === 'active'))
                ->when($this->siteFilter, fn ($q) => $q->whereHas('sites', fn ($w) => $w->whereKey($this->siteFilter)))
                ->orderBy('name')->paginate(25),
            'allRoles' => Role::query()->orderBy('name')->pluck('name'),
            'sites' => Site::query()->orderBy('name')->get(),
        ]);
    }
}
