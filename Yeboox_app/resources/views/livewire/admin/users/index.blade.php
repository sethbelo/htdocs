<div class="space-y-6">
    <div class="yeboox-page-head">
        <div><flux:heading size="xl">Utilisateurs</flux:heading><flux:subheading>Comptes, rôles et droits d’accès de votre équipe.</flux:subheading></div>
        @can('users.create')<flux:button wire:click="openForm" variant="primary" icon="user-plus">Nouvel utilisateur</flux:button>@endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="yeboox-card-tone p-5" style="--tone:#2563eb"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.users class="size-5"/></div><div><p class="text-2xl font-semibold">{{ $users->total() }}</p><flux:text size="sm">Comptes affichés</flux:text></div></div></div>
        <div class="yeboox-card-tone p-5" style="--tone:#10b981"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.check-circle class="size-5"/></div><div><p class="text-2xl font-semibold">{{ $users->filter(fn ($user) => $user->status->value === 'active')->count() }}</p><flux:text size="sm">Actifs sur cette page</flux:text></div></div></div>
        <div class="yeboox-card-tone p-5" style="--tone:#8b5cf6"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.shield-check class="size-5"/></div><div><p class="text-2xl font-semibold">{{ $roles->count() }}</p><flux:text size="sm">Rôles configurés</flux:text></div></div></div>
    </div>

    <flux:card><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher par nom ou e-mail…" clearable /></flux:card>

    <flux:card class="p-0!">
        <flux:table :paginate="$users" pagination:scroll-to>
            <flux:table.columns><flux:table.column>Utilisateur</flux:table.column><flux:table.column>Contact</flux:table.column><flux:table.column>Rôle</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Actions</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell><div class="flex items-center gap-3"><flux:avatar :initials="$user->initials"/><div class="min-w-0"><p class="truncate font-medium">{{ $user->name }}</p><flux:text size="sm">{{ $user->username ?: 'Compte standard' }}</flux:text></div></div></flux:table.cell>
                        <flux:table.cell><p>{{ $user->email }}</p><flux:text size="sm">{{ $user->phone ?: 'Sans téléphone' }}</flux:text></flux:table.cell>
                        <flux:table.cell><flux:badge color="blue" icon="shield-check">{{ ucfirst($user->roles->first()?->name ?? 'Sans rôle') }}</flux:badge></flux:table.cell>
                        <flux:table.cell><flux:badge :color="$user->status->value === 'active' ? 'emerald' : 'red'" :icon="$user->status->value === 'active' ? 'check-circle' : 'pause-circle'">{{ $user->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end"><flux:dropdown position="bottom" align="end"><flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" square/><flux:menu>@can('users.edit')<flux:menu.item icon="pencil-square" wire:click="openForm({{ $user->id }})">Modifier</flux:menu.item>@endcan @can('users.delete')<flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete({{ $user->id }})">Supprimer</flux:menu.item>@endcan</flux:menu></flux:dropdown></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="5"><div class="yeboox-empty"><div><flux:icon.user-group class="mx-auto size-10 text-zinc-300"/><flux:heading class="mt-3">Aucun utilisateur</flux:heading><flux:text>Créez le premier compte de votre équipe.</flux:text></div></div></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal wire:model.self="showFormModal" class="md:w-[44rem]">
        <form wire:submit="save" class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#2563eb"><flux:icon.user-plus class="size-5"/></div><div><flux:heading size="lg">{{ $editingId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</flux:heading><flux:text>Renseignez son identité, son rôle et ses accès.</flux:text></div></div>
            <div class="grid gap-5 sm:grid-cols-2"><flux:input wire:model="name" label="Nom complet" required/><flux:input wire:model="email" type="email" label="E-mail" required/><flux:input wire:model="phone" label="Téléphone"/><flux:select wire:model="role" label="Rôle">@foreach($roles as $item)<flux:select.option value="{{ $item->name }}">{{ ucfirst($item->name) }}</flux:select.option>@endforeach</flux:select><flux:select wire:model="status" label="Statut"><flux:select.option value="active">Actif</flux:select.option><flux:select.option value="suspended">Suspendu</flux:select.option></flux:select><flux:input wire:model="password" type="password" viewable label="{{ $editingId ? 'Nouveau mot de passe' : 'Mot de passe' }}" :required="!$editingId"/></div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showFormModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="check">Enregistrer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5"/></div><div><flux:heading size="lg">Supprimer l’utilisateur ?</flux:heading><flux:text>Le compte « {{ $deleteName }} » perdra immédiatement son accès.</flux:text></div></div>@error('delete')<x-dismissible-alert variant="danger">{{ $message }}</x-dismissible-alert>@enderror<div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Supprimer</flux:button></div></div></flux:modal>
</div>
