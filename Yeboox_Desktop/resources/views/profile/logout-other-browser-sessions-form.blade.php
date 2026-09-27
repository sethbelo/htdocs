<flux:card class="overflow-hidden p-0! shadow-sm">
    <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
        <div class="flex items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"><flux:icon.computer-desktop class="size-5" /></span>
            <div><flux:heading>Sessions et appareils</flux:heading><flux:text size="sm">Contrôlez les appareils connectés à votre compte.</flux:text></div>
        </div>
    </div>

    <div class="space-y-4 p-5 sm:p-6">
        <flux:text size="sm">Déconnectez les autres sessions si vous ne reconnaissez pas un appareil ou une activité récente.</flux:text>

        @if (count($this->sessions) > 0)
            <div class="divide-y divide-zinc-200 overflow-hidden rounded-2xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                @foreach ($this->sessions as $session)
                    <div class="flex items-center gap-4 p-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            @if ($session->agent->isDesktop())<flux:icon.computer-desktop class="size-5" />@else<flux:icon.device-phone-mobile class="size-5" />@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold">{{ $session->agent->platform() ?: 'Appareil inconnu' }} · {{ $session->agent->browser() ?: 'Navigateur inconnu' }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>{{ $session->ip_address }}</span><span aria-hidden="true">•</span>
                                @if ($session->is_current_device)
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-300">Cet appareil</span>
                                @else
                                    <span>Actif {{ $session->last_active }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($session->is_current_device)<flux:badge color="emerald" icon="check-circle" class="max-sm:hidden">Actuelle</flux:badge>@endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-700"><flux:icon.computer-desktop class="mx-auto size-7 text-zinc-400" /><flux:text size="sm" class="mt-2">Aucune autre session détectée.</flux:text></div>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
        <x-action-message on="loggedOut">Autres sessions déconnectées.</x-action-message>
        <flux:button type="button" icon="arrow-right-start-on-rectangle" wire:click="confirmLogout" wire:loading.attr="disabled">Déconnecter les autres</flux:button>
    </div>

    <flux:modal wire:model.live="confirmingLogout" class="md:w-[32rem]" :dismissible="false">
        <div class="space-y-5" x-data="{}" x-on:confirming-logout-other-browser-sessions.window="setTimeout(() => $refs.password?.focus(), 250)">
            <div class="flex items-start gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"><flux:icon.computer-desktop class="size-5" /></span><div><flux:heading size="lg">Déconnecter les autres appareils ?</flux:heading><flux:text class="mt-1">Votre session actuelle restera ouverte.</flux:text></div></div>
            <flux:input x-ref="password" wire:model="password" wire:keydown.enter="logoutOtherBrowserSessions" type="password" label="Mot de passe actuel" icon="lock-closed" autocomplete="current-password" viewable />
            <div class="flex justify-end gap-2"><flux:button type="button" variant="ghost" wire:click="$set('confirmingLogout', false)">Annuler</flux:button><flux:button type="button" variant="primary" icon="check" wire:click="logoutOtherBrowserSessions" wire:loading.attr="disabled">Confirmer</flux:button></div>
        </div>
    </flux:modal>
</flux:card>
