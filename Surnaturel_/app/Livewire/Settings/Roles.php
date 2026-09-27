<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Services\Support\PermissionRegistry;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Title('Paramètres — Rôles & permissions')]
class Roles extends Component
{
    use InteractsWithToasts;

    public ?int $roleId = null;

    /** @var list<string> */
    public array $permissions = [];

    public string $newRole = '';

    public function mount(): void
    {
        $this->selectRole(Role::query()->where('name', '!=', 'directeur-general')->orderBy('name')->value('id'));
    }

    public function selectRole(?int $id): void
    {
        $this->roleId = $id;
        $this->permissions = $id ? Role::query()->findOrFail($id)->permissions()->pluck('name')->all() : [];
    }

    public function toggleModule(string $module): void
    {
        $modulePermissions = array_map(fn (string $action): string => "{$module}.{$action}", array_keys(config('erp.actions')));
        $allGranted = array_diff($modulePermissions, $this->permissions) === [];

        $this->permissions = $allGranted
            ? array_values(array_diff($this->permissions, $modulePermissions))
            : array_values(array_unique([...$this->permissions, ...$modulePermissions]));
    }

    public function save(): void
    {
        $this->authorize('roles.update');
        $role = Role::query()->findOrFail($this->roleId);
        abort_if($role->name === 'directeur-general', 403);

        $valid = array_values(array_intersect($this->permissions, app(PermissionRegistry::class)->all()));
        $role->syncPermissions($valid);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        activity('Role')->performedOn($role)->causedBy(auth()->user())->withProperties(['permissions' => $valid])->log('Permissions modifiées');

        $this->toast('Permissions du rôle enregistrées.');
    }

    public function createRole(): void
    {
        $this->authorize('roles.create');
        $this->validate(['newRole' => ['required', 'string', 'max:60']], [], ['newRole' => 'nom du rôle']);
        $role = Role::findOrCreate(Str::slug($this->newRole), 'web');
        $this->reset('newRole');
        $this->selectRole($role->id);
        $this->toast('Rôle créé.');
    }

    public function render()
    {
        return view('livewire.settings.roles', [
            'roles' => Role::query()->withCount('users')->orderBy('name')->get(),
            'modules' => config('erp.modules'),
            'actions' => config('erp.actions'),
            'specials' => config('erp.special_permissions'),
            'current' => $this->roleId ? Role::query()->find($this->roleId) : null,
        ]);
    }
}
