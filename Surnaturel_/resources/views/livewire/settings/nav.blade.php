<div class="flex flex-wrap gap-2">
    @foreach ([['settings.company', 'Entreprise', 'settings.view'], ['settings.sites', 'Sièges, entrepôts & caisses', 'sites.view'], ['settings.users', 'Utilisateurs', 'users.view'], ['settings.roles', 'Rôles & permissions', 'roles.view'], ['settings.documents', 'Documents & signatures', 'settings.update'], ['settings.workflows', 'Circuits de validation', 'approvals.update']] as [$route, $label, $permission])
        @can($permission)
            <flux:button size="sm" :href="route($route)" wire:navigate :variant="request()->routeIs($route) ? 'primary' : 'ghost'">{{ $label }}</flux:button>
        @endcan
    @endforeach
</div>
