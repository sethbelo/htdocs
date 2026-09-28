{{-- Actions de la barre supérieure : synchronisation, installation, notifications, apparence, compte --}}
<div class="flex shrink-0 items-center gap-0.5 sm:gap-1">
    <button type="button" x-data x-on:click="$store.sync.ping()" class="sync-pill max-md:hidden" :data-state="$store.sync.state"
        :title="$store.sync.state === 'offline' ? 'Connexion perdue : les écrans reprendront automatiquement.' : 'Données synchronisées avec le serveur cloud — cliquer pour vérifier'">
        <span class="sync-dot"></span>
        <flux:icon.cloud variant="micro" class="size-4" x-show="$store.sync.state === 'synced'" />
        <flux:icon.arrow-path variant="micro" class="size-4 animate-spin" x-show="$store.sync.state === 'syncing'" x-cloak />
        <flux:icon.signal-slash variant="micro" class="size-4" x-show="$store.sync.state === 'offline'" x-cloak />
        <span x-text="$store.sync.label"></span>
    </button>
    <button type="button" x-data x-show="$store.install.available" x-cloak x-on:click="$store.install.run()" class="shell-icon-btn max-sm:hidden" title="Installer l’application sur cet ordinateur">
        <flux:icon.computer-desktop variant="mini" />
    </button>

    @if ($user->hasConsolidatedView())
        <flux:badge size="sm" color="indigo" icon="globe-alt" class="mr-1 max-sm:hidden">Vue consolidée</flux:badge>
    @else
        <flux:badge size="sm" color="zinc" icon="map-pin" class="mr-1 max-sm:hidden">{{ $user->sites->pluck('name')->implode(' · ') ?: 'Aucun siège' }}</flux:badge>
    @endif

    <livewire:notifications-bell />

    <flux:dropdown position="bottom" align="end" class="max-sm:hidden">
        <button type="button" class="shell-icon-btn" aria-label="Apparence">
            <flux:icon.swatch variant="mini" />
        </button>
        <flux:menu>
            <flux:menu.radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:menu.radio value="light" icon="sun">Clair</flux:menu.radio>
                <flux:menu.radio value="dark" icon="moon">Sombre</flux:menu.radio>
                <flux:menu.radio value="system" icon="computer-desktop">Auto</flux:menu.radio>
            </flux:menu.radio.group>
            <flux:menu.separator />
            <div class="px-2 pt-1 pb-1.5" x-data="{ level: document.documentElement.dataset.bg || 'soft', set(v) { this.level = v; document.documentElement.dataset.bg = v; try { localStorage.setItem('erp-bg', v) } catch (e) {} } }">
                <div class="mb-1.5 text-[11px] font-semibold tracking-wide text-fg-subtle uppercase">Image de fond</div>
                <div class="segmented w-full">
                    <button type="button" class="flex-1" x-on:click="set('off')" :aria-pressed="level === 'off'">Aucune</button>
                    <button type="button" class="flex-1" x-on:click="set('soft')" :aria-pressed="level === 'soft'">Douce</button>
                    <button type="button" class="flex-1" x-on:click="set('full')" :aria-pressed="level === 'full'">Visible</button>
                </div>
            </div>
        </flux:menu>
    </flux:dropdown>

    <flux:dropdown position="bottom" align="end">
        <button type="button" class="ml-1 rounded-full p-0.5 transition hover:ring-4 hover:ring-brand-100 dark:hover:ring-white/10" aria-label="Mon compte">
            <span class="avatar size-8 bg-brand-600 text-[11px] text-white">{{ $user->initials() }}</span>
        </button>
        <x-user-menu :user="$user" />
    </flux:dropdown>
</div>
