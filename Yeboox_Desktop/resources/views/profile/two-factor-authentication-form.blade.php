<flux:card class="overflow-hidden p-0! shadow-sm">
    <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300"><flux:icon.device-phone-mobile class="size-5" /></span>
                <div><flux:heading>Double authentification</flux:heading><flux:text size="sm">Ajoutez une vérification par code à votre connexion.</flux:text></div>
            </div>
            <flux:badge :color="$this->enabled ? 'emerald' : 'zinc'" :icon="$this->enabled ? 'check-circle' : 'minus-circle'">{{ $this->enabled ? 'Activée' : 'Désactivée' }}</flux:badge>
        </div>
    </div>

    <div class="space-y-5 p-5 sm:p-6">
        <div class="flex gap-3 rounded-2xl bg-sky-50 p-4 text-sky-950 dark:bg-sky-950/30 dark:text-sky-100">
            <flux:icon.shield-check class="mt-0.5 size-5 shrink-0 text-sky-600 dark:text-sky-300" />
            <p class="text-sm leading-6">Une application d’authentification génère un code temporaire demandé après votre mot de passe.</p>
        </div>

        @if ($this->enabled && $showingQrCode)
            <div class="grid gap-5 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800 md:grid-cols-[auto_minmax(0,1fr)] md:items-center">
                <div class="mx-auto rounded-xl bg-white p-3 ring-1 ring-zinc-200">{!! $this->user->twoFactorQrCodeSvg() !!}</div>
                <div class="min-w-0 space-y-3">
                    <flux:heading size="sm">Scanner le QR code</flux:heading>
                    <flux:text size="sm">Scannez-le avec votre application, ou utilisez cette clé de configuration.</flux:text>
                    <code class="block break-all rounded-xl bg-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">{{ decrypt($this->user->two_factor_secret) }}</code>
                    @if ($showingConfirmation)
                        <flux:input id="code" wire:model="code" wire:keydown.enter="confirmTwoFactorAuthentication" label="Code de vérification" icon="key" inputmode="numeric" autocomplete="one-time-code" autofocus />
                    @endif
                </div>
            </div>
        @endif

        @if ($this->enabled && $showingRecoveryCodes)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30">
                <div class="flex gap-3"><flux:icon.key class="size-5 shrink-0 text-amber-600" /><div><p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Codes de récupération</p><p class="mt-1 text-sm text-amber-800 dark:text-amber-300">Conservez ces codes dans un emplacement sécurisé.</p></div></div>
                <div class="mt-4 grid gap-2 rounded-xl bg-white p-4 font-mono text-sm text-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 sm:grid-cols-2">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $recoveryCode)
                        <div>{{ $recoveryCode }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
        @if (! $this->enabled)
            <x-confirms-password wire:then="enableTwoFactorAuthentication"><flux:button type="button" variant="primary" icon="shield-check" wire:loading.attr="disabled">Activer</flux:button></x-confirms-password>
        @else
            @if ($showingRecoveryCodes)
                <x-confirms-password wire:then="regenerateRecoveryCodes"><flux:button type="button" variant="ghost" icon="arrow-path">Régénérer les codes</flux:button></x-confirms-password>
            @elseif ($showingConfirmation)
                <x-confirms-password wire:then="confirmTwoFactorAuthentication"><flux:button type="button" variant="primary" icon="check">Confirmer</flux:button></x-confirms-password>
            @else
                <x-confirms-password wire:then="showRecoveryCodes"><flux:button type="button" variant="ghost" icon="key">Afficher les codes</flux:button></x-confirms-password>
            @endif
            <x-confirms-password wire:then="disableTwoFactorAuthentication"><flux:button type="button" variant="danger" icon="shield-exclamation" wire:loading.attr="disabled">Désactiver</flux:button></x-confirms-password>
        @endif
    </div>
</flux:card>
