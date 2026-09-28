{{-- Palette de commandes (Ctrl/⌘ + K) et bandeau hors ligne --}}
    {{-- ═══ 3. PALETTE DE COMMANDES (Ctrl/⌘ + K) ══════════════════════════════ --}}
    <div x-data="commandPalette(@js($palette))" x-on:open-palette.window="show()" x-on:keydown.window.ctrl.k.prevent="show()" x-on:keydown.window.meta.k.prevent="show()" x-on:keydown.escape.window="open = false">
        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center p-4 pt-[12vh]">
            <div class="overlay" x-on:click="open = false"></div>
            <div x-show="open" x-transition.scale.98 class="modal max-w-xl overflow-hidden">
                <div class="flex items-center gap-3 border-b border-line px-4">
                    <flux:icon.magnifying-glass variant="mini" class="text-fg-subtle" />
                    <input x-ref="search" x-model="query" x-on:input="active = 0" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                           class="h-14 flex-1 border-0 bg-transparent text-[15px] outline-none placeholder:text-fg-subtle" placeholder="Aller à… (commandes, caisse, stock…)">
                    <span class="kbd">Échap</span>
                </div>
                <ul class="max-h-[50vh] overflow-y-auto p-2">
                    <template x-for="(item, index) in filtered" :key="item.url + item.label">
                        <li>
                            <button type="button" x-on:click="go(item)" x-on:mouseenter="active = index" :class="index === active ? 'bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-white' : ''" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-[14px]">
                                <span x-text="item.label" class="font-medium"></span>
                                <span x-text="item.group" class="text-[11.5px] text-fg-subtle"></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-3 py-8 text-center text-[13px] text-fg-subtle">Aucun résultat.</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Bandeau hors ligne --}}
    <div x-data x-show="$store.sync.state === 'offline'" x-cloak x-transition.opacity
        class="fixed inset-x-0 bottom-4 z-[70] mx-auto flex w-fit max-w-[92vw] items-center gap-3 rounded-2xl bg-zinc-900 px-4 py-3 text-sm text-white shadow-2xl">
        <flux:icon.signal-slash variant="mini" class="text-carmin-400" />
        <span><strong x-text="$store.sync.label"></strong> — vos données sont sauvegardées dans le cloud ; la synchronisation reprend dès le retour du réseau.</span>
        <button type="button" x-on:click="$store.sync.ping()" class="rounded-lg bg-white/10 px-2.5 py-1 font-semibold hover:bg-white/20">Réessayer</button>
    </div>

