<div class="space-y-6">
    <x-page-header title="Rôles & permissions" subtitle="Matrice module × action. Le périmètre par siège est défini sur chaque utilisateur." />
    @include('livewire.settings.nav')

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="panel p-2 space-y-1">
            @foreach ($roles as $role)
                <button wire:click="selectRole({{ $role->id }})" wire:key="role-{{ $role->id }}" @class(['flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm', 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' => $roleId === $role->id, 'hover:bg-zinc-50 dark:hover:bg-zinc-800' => $roleId !== $role->id])>
                    <span>{{ config("erp.roles.{$role->name}.label", $role->name) }}</span><flux:badge size="sm">{{ $role->users_count }}</flux:badge>
                </button>
            @endforeach
            @can('roles.create')
                <form wire:submit="createRole" class="flex gap-1 p-1 pt-2">
                    <flux:input wire:model="newRole" size="sm" placeholder="Nouveau rôle" />
                    <flux:button type="submit" size="sm" icon="plus" />
                </form>
            @endcan
        </div>

        <div class="panel overflow-x-auto p-4 xl:col-span-4">
            @if ($current?->name === 'directeur-general')
                <flux:callout icon="shield-check" heading="Le Directeur Général dispose de toutes les fonctionnalités de l’application." text="Ce rôle n’est pas modifiable : c’est le seul profil à accès complet." />
            @elseif ($current)
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading>{{ config("erp.roles.{$current->name}.label", $current->name) }}</flux:heading>
                    @can('roles.update')<flux:button size="sm" variant="primary" icon="check" wire:click="save">Enregistrer</flux:button>@endcan
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-zinc-500">
                            <th class="sticky left-0 bg-white py-2 text-left dark:bg-zinc-900">Module</th>
                            @foreach ($actions as $label)<th class="px-1 py-2 font-medium">{{ $label }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($modules as $module => $moduleLabel)
                            <tr wire:key="m-{{ $module }}">
                                <td class="sticky left-0 bg-white py-1.5 pe-3 dark:bg-zinc-900"><button type="button" wire:click="toggleModule('{{ $module }}')" class="font-medium hover:text-indigo-600">{{ $moduleLabel }}</button></td>
                                @foreach ($actions as $action => $label)
                                    <td class="px-1 text-center"><input type="checkbox" wire:model="permissions" value="{{ $module }}.{{ $action }}" class="size-4 rounded border-zinc-300 text-indigo-600" title="{{ $label }} — {{ $moduleLabel }}"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <flux:heading size="sm" class="mt-6">Permissions spéciales</flux:heading>
                <div class="mt-2 grid gap-2 md:grid-cols-2">
                    @foreach ($specials as $permission => $label)
                        <label class="flex items-center gap-2 text-sm" wire:key="sp-{{ $permission }}"><input type="checkbox" wire:model="permissions" value="{{ $permission }}" class="size-4 rounded border-zinc-300 text-indigo-600"> {{ $label }}</label>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
