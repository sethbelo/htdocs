<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public string $deleteName = '';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'vendeur';

    public string $status = 'active';

    public string $password = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetForm();
        if ($id) {
            abort_unless(auth()->user()->can('users.edit'), 403);
            $user = User::forEstablishment()->with('roles')->findOrFail($id);
            $this->editingId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->role = $user->roles->first()?->name ?? 'vendeur';
            $this->status = $user->status->value;
        } else {
            abort_unless(auth()->user()->can('users.create'), 403);
        }
        $this->showFormModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->editingId ? 'users.edit' : 'users.create'), 403);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::exists('roles', 'name')],
            'status' => ['required', 'in:active,suspended'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
        ]);

        if ($this->editingId === auth()->id() && $this->status === 'suspended') {
            throw ValidationException::withMessages(['status' => 'Vous ne pouvez pas suspendre votre propre compte.']);
        }

        $values = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'status' => $this->status,
            'establishment_id' => auth()->user()->establishment_id,
        ];
        if ($this->password !== '') {
            $values['password'] = Hash::make($this->password);
        }
        if (! $this->editingId) {
            $values['email_verified_at'] = now();
        }

        $user = $this->editingId ? User::forEstablishment()->findOrFail($this->editingId) : new User;
        $user->fill($values)->save();
        $user->syncRoles([$this->role]);

        $this->showFormModal = false;
        session()->flash('success', $this->editingId ? 'Utilisateur modifié.' : 'Utilisateur créé.');
        $this->resetForm();
    }

    public function requestDelete(int $id): void
    {
        abort_unless(auth()->user()->can('users.delete'), 403);
        $user = User::forEstablishment()->findOrFail($id);
        $this->deleteId = $user->id;
        $this->deleteName = $user->name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('users.delete'), 403);
        if ($this->deleteId === auth()->id()) {
            throw ValidationException::withMessages(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }
        User::forEstablishment()->findOrFail($this->deleteId)->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Utilisateur supprimé.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'password']);
        $this->role = 'vendeur';
        $this->status = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::query()
            ->forEstablishment()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        $roles = Role::orderBy('name')->get();

        return view('livewire.admin.users.index', compact('users', 'roles'))
            ->layout('layouts.admin', ['title' => 'Utilisateurs', 'breadcrumb' => 'Utilisateurs']);
    }
}
