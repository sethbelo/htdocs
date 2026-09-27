@php
    $glass = 'rounded-2xl border border-zinc-200 bg-zinc-900/[.03] backdrop-blur-sm transition-colors focus-within:border-violet-400/70 focus-within:bg-violet-500/10 dark:border-zinc-700 dark:bg-white/5';
    $input = 'w-full rounded-2xl bg-transparent p-4 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none dark:text-white';
    $label = 'mb-1.5 block text-sm font-medium text-zinc-500 dark:text-zinc-400';
@endphp

<div class="flex flex-col gap-6">
    <div>
        <h1 class="animate-element animate-delay-100 text-4xl font-light leading-tight tracking-tighter text-zinc-900 md:text-5xl dark:text-white">Bienvenue</h1>
        <p class="animate-element animate-delay-200 mt-2 text-zinc-500 dark:text-zinc-400">Connectez-vous à votre espace de travail.</p>
    </div>

    @if (session('status'))
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="session('status')" />
    @endif

    <div class="animate-element animate-delay-300 grid grid-cols-3 gap-1 rounded-2xl border border-zinc-200 bg-zinc-900/[.03] p-1 dark:border-zinc-700 dark:bg-white/5" role="tablist" aria-label="Mode de connexion">
        @foreach ($modes as $option)
            <button type="button" role="tab" wire:click="setMode('{{ $option->value }}')" aria-selected="{{ $mode === $option->value ? 'true' : 'false' }}"
                @class([
                    'flex flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-xs font-medium transition sm:flex-row sm:justify-center sm:gap-2 sm:text-sm',
                    'bg-white text-violet-700 shadow-sm dark:bg-zinc-800 dark:text-violet-300' => $mode === $option->value,
                    'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => $mode !== $option->value,
                ])>
                <flux:icon :icon="$option->icon()" variant="mini" class="size-4" />
                <span class="text-center leading-tight">{{ $option->shortLabel() }}</span>
            </button>
        @endforeach
    </div>

    <form wire:submit="login" class="space-y-5" wire:key="form-{{ $mode }}">
        @if ($mode === 'password')
            <p class="animate-element animate-delay-300 -mt-2 text-xs text-zinc-500">Direction, administration, finance, commercial, graphisme, RH…</p>
            <div class="animate-element animate-delay-400">
                <label for="identifier" class="{{ $label }}">E-mail ou nom d’utilisateur</label>
                <div class="{{ $glass }}">
                    <input id="identifier" wire:model="identifier" type="text" autocomplete="username" autofocus placeholder="ex. directeur.general" class="{{ $input }}">
                </div>
                <flux:error name="identifier" />
            </div>
            <div class="animate-element animate-delay-500" x-data="{ show: false }">
                <label for="password" class="{{ $label }}">Mot de passe</label>
                <div class="{{ $glass }}">
                    <div class="relative">
                        <input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" placeholder="Votre mot de passe" class="{{ $input }} pr-12">
                        <button type="button" x-on:click="show = ! show" class="absolute inset-y-0 right-3 flex items-center text-zinc-400 transition hover:text-zinc-700 dark:hover:text-white" :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                            <flux:icon.eye x-show="! show" class="size-5" /><flux:icon.eye-slash x-show="show" x-cloak class="size-5" />
                        </button>
                    </div>
                </div>
                <flux:error name="password" />
            </div>
            <div class="animate-element animate-delay-600 flex items-center justify-between gap-3 text-sm" x-data="{ help: false }">
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" wire:model="remember" class="size-4 rounded border-zinc-300 text-violet-600 focus:ring-violet-500">
                    <span class="text-zinc-700 dark:text-zinc-300">Rester connecté</span>
                </label>
                <button type="button" x-on:click="help = ! help" class="text-violet-600 hover:underline dark:text-violet-400">Mot de passe oublié ?</button>
                <p x-show="help" x-cloak x-transition class="basis-full rounded-xl bg-violet-50 p-3 text-xs text-violet-800 dark:bg-violet-500/10 dark:text-violet-300">
                    Demandez à l’administrateur de réinitialiser votre mot de passe (Paramètres › Utilisateurs). Un mot de passe provisoire vous sera remis ; vous le changerez à la connexion.
                </p>
            </div>
        @else
            <div x-data="{
                    pin: $wire.entangle('pin'),
                    press(digit) { if (this.pin.length < 6) { this.pin += digit } },
                    erase() { this.pin = this.pin.slice(0, -1) },
                }" class="space-y-5">
                @if ($mode === 'pin')
                    <p class="animate-element animate-delay-300 -mt-2 text-xs text-zinc-500">Caissiers, gérants et responsables boutique.</p>
                    <div class="animate-element animate-delay-400">
                        <label for="username" class="{{ $label }}">Nom d’utilisateur</label>
                        <div class="{{ $glass }}">
                            <input id="username" wire:model="username" type="text" autocomplete="username" autofocus placeholder="ex. caissier" class="{{ $input }}"
                                x-init="try { if (! $wire.username) { $wire.username = localStorage.getItem('erp.lastPinUser') || '' } } catch (e) {}"
                                x-on:change="try { localStorage.setItem('erp.lastPinUser', $event.target.value) } catch (e) {}">
                        </div>
                        <flux:error name="username" />
                    </div>
                @else
                    <p class="animate-element animate-delay-300 -mt-2 text-xs text-zinc-500">Magasiniers, imprimeurs et livreurs : passez votre badge (RFID / NFC / QR) puis saisissez votre PIN.</p>
                    <div class="animate-element animate-delay-400">
                        <label for="badge" class="{{ $label }}">Badge</label>
                        <div class="{{ $glass }} flex items-center">
                            <flux:icon.identification class="ms-4 size-5 text-zinc-400" />
                            <input id="badge" wire:model="badge" type="password" autofocus placeholder="Passez le badge devant le lecteur…" class="{{ $input }}"
                                x-on:keydown.enter.prevent="$el.closest('form').querySelector('[data-pin-display]')?.focus()">
                        </div>
                        <flux:error name="badge" />
                    </div>
                @endif

                <div class="animate-element animate-delay-500">
                    <span class="{{ $label }}">PIN</span>
                    <div class="flex justify-center gap-3 rounded-2xl py-2 outline-none focus-visible:ring-2 focus-visible:ring-violet-400" data-pin-display tabindex="0"
                        x-on:keydown="if (/^[0-9]$/.test($event.key)) press($event.key); if ($event.key === 'Backspace') erase(); if ($event.key === 'Enter') $wire.login()">
                        <template x-for="index in 6" :key="index">
                            <span class="size-3.5 rounded-full border-2 transition" :class="pin.length >= index ? 'scale-110 border-violet-600 bg-violet-600' : 'border-zinc-300 dark:border-zinc-600'"></span>
                        </template>
                    </div>
                    <flux:error name="pin" />
                    <div class="mx-auto mt-2 grid max-w-xs grid-cols-3 gap-2">
                        @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $digit)
                            <button type="button" x-on:click="press('{{ $digit }}')" class="{{ $glass }} h-12 text-lg font-semibold text-zinc-800 active:scale-95 dark:text-white">{{ $digit }}</button>
                        @endforeach
                        <button type="button" x-on:click="pin = ''" class="h-12 rounded-2xl text-sm text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">Effacer</button>
                        <button type="button" x-on:click="press('0')" class="{{ $glass }} h-12 text-lg font-semibold text-zinc-800 active:scale-95 dark:text-white">0</button>
                        <button type="button" x-on:click="erase()" class="flex h-12 items-center justify-center rounded-2xl text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Supprimer le dernier chiffre"><flux:icon.backspace /></button>
                    </div>
                </div>
            </div>
        @endif

        <button type="submit" wire:loading.attr="disabled" class="animate-element animate-delay-700 flex w-full items-center justify-center gap-2 rounded-2xl bg-violet-600 py-4 font-medium text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="login">Se connecter</span>
            <span wire:loading wire:target="login">Connexion…</span>
            <flux:icon.arrow-right variant="micro" wire:loading.remove wire:target="login" />
        </button>
    </form>

    @if ($demoAccounts->isNotEmpty())
        <div class="animate-element animate-delay-800" x-data="{ open: false }">
            <div class="relative flex items-center justify-center">
                <span class="w-full border-t border-zinc-200 dark:border-zinc-700"></span>
                <button type="button" x-on:click="open = ! open" class="absolute flex items-center gap-1.5 bg-zinc-50 px-4 text-sm text-zinc-500 hover:text-violet-600 dark:bg-zinc-950">
                    <flux:icon.user-group variant="micro" /> Comptes de démonstration
                    <flux:icon.chevron-down variant="micro" class="transition" ::class="open && 'rotate-180'" />
                </button>
            </div>
            <div x-show="open" x-cloak x-collapse class="mt-4 max-h-80 space-y-3 overflow-y-auto pe-1">
                <p class="text-xs text-zinc-500">Cliquez sur un compte pour pré-remplir le formulaire. Mot de passe « {{ \App\Support\DemoAccounts::PASSWORD }} », PIN « {{ \App\Support\DemoAccounts::PIN }} ».</p>
                @foreach ($modes as $option)
                    @if ($demoAccounts->has($option->value))
                        <div>
                            <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-400"><flux:icon :icon="$option->icon()" variant="micro" /> {{ $option->shortLabel() }}</div>
                            <div class="grid gap-1.5 sm:grid-cols-2">
                                @foreach ($demoAccounts[$option->value] as $account)
                                    <button type="button" wire:click="useDemoAccount('{{ $account['username'] }}')" wire:key="demo-{{ $account['username'] }}"
                                        class="rounded-xl border border-zinc-200 px-3 py-2 text-left transition hover:border-violet-400 hover:bg-violet-50 dark:border-zinc-700 dark:hover:bg-violet-500/10">
                                        <span class="block truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $account['label'] }}</span>
                                        <span class="block truncate font-mono text-xs text-zinc-500">{{ $account['badge'] ?? $account['username'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
