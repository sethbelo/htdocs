<form wire:submit="updatePassword">
    <flux:card class="overflow-hidden p-0! shadow-sm">
        <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"><flux:icon.shield-check class="size-5" /></span>
                <div><flux:heading>Sécurité du compte</flux:heading><flux:text size="sm">Utilisez un mot de passe long et unique.</flux:text></div>
            </div>
        </div>
        <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
            <flux:input wire:model="state.current_password" type="password" label="Mot de passe actuel" icon="lock-closed" autocomplete="current-password" viewable class="md:col-span-2" />
            <flux:input wire:model="state.password" type="password" label="Nouveau mot de passe" icon="key" autocomplete="new-password" viewable />
            <flux:input wire:model="state.password_confirmation" type="password" label="Confirmer le mot de passe" icon="check-circle" autocomplete="new-password" viewable />
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
            <x-action-message on="saved">Mot de passe mis à jour.</x-action-message>
            <flux:button type="submit" variant="primary" icon="shield-check">Mettre à jour</flux:button>
        </div>
    </flux:card>
</form>
