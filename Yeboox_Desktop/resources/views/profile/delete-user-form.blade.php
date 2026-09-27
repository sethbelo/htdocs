<flux:card class="overflow-hidden border-rose-200! p-0! shadow-sm dark:border-rose-900/60!">
    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div class="flex items-start gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300"><flux:icon.exclamation-triangle class="size-5" /></span>
            <div><flux:heading>Zone sensible</flux:heading><flux:text size="sm" class="mt-1 max-w-xl">La suppression du compte est définitive et efface les ressources personnelles associées.</flux:text></div>
        </div>
        <flux:button type="button" variant="danger" icon="trash" wire:click="confirmUserDeletion" wire:loading.attr="disabled">Supprimer mon compte</flux:button>
    </div>

    <flux:modal wire:model.live="confirmingUserDeletion" class="md:w-[32rem]" :dismissible="false">
        <div class="space-y-5" x-data="{}" x-on:confirming-delete-user.window="setTimeout(() => $refs.password?.focus(), 250)">
            <div class="flex items-start gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300"><flux:icon.trash class="size-5" /></span><div><flux:heading size="lg">Supprimer définitivement le compte ?</flux:heading><flux:text class="mt-1">Cette action est irréversible. Confirmez avec votre mot de passe.</flux:text></div></div>
            <flux:input x-ref="password" wire:model="password" wire:keydown.enter="deleteUser" type="password" label="Mot de passe actuel" icon="lock-closed" autocomplete="current-password" viewable />
            <div class="flex justify-end gap-2"><flux:button type="button" variant="ghost" wire:click="$set('confirmingUserDeletion', false)">Annuler</flux:button><flux:button type="button" variant="danger" icon="trash" wire:click="deleteUser" wire:loading.attr="disabled">Supprimer définitivement</flux:button></div>
        </div>
    </flux:modal>
</flux:card>
