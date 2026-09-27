@php
    $field = 'flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 shadow-xs transition focus-within:border-brand-400 focus-within:ring-4 focus-within:ring-brand-100 dark:border-zinc-700 dark:bg-white/5 dark:focus-within:ring-brand-500/20';
    $input = 'h-13 w-full bg-transparent text-[15px] text-zinc-900 placeholder:text-zinc-400 focus:outline-none dark:text-white';
@endphp

<div class="flex flex-col gap-7">
    <div>
        <x-app-logo variant="full" class="animate-element mb-6 h-12 w-auto md:hidden" />
        <h1 class="animate-element animate-delay-100 font-display text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl dark:text-white">Connexion</h1>
        <p class="animate-element animate-delay-200 mt-2 text-zinc-500 dark:text-zinc-400">Identifiez-vous pour accéder à votre espace de travail.</p>
    </div>

    @if (session('status'))
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="session('status')" />
    @endif

    <form wire:submit="login" class="space-y-5">
        <div class="animate-element animate-delay-300">
            <label for="identifier" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nom d’utilisateur ou e-mail</label>
            <div class="{{ $field }}">
                <flux:icon.user variant="mini" class="shrink-0 text-zinc-400" />
                <input id="identifier" wire:model="identifier" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus placeholder="ex. j.mukendi ou j.mukendi@surnaturel.cd" class="{{ $input }}">
            </div>
            <flux:error name="identifier" />
        </div>

        <div class="animate-element animate-delay-400" x-data="{ show: false }">
            <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Mot de passe</label>
            <div class="{{ $field }}">
                <flux:icon.lock-closed variant="mini" class="shrink-0 text-zinc-400" />
                <input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" placeholder="Votre mot de passe" class="{{ $input }}">
                <button type="button" x-on:click="show = ! show" class="shrink-0 text-zinc-400 transition hover:text-zinc-700 dark:hover:text-white" :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                    <flux:icon.eye x-show="! show" variant="mini" /><flux:icon.eye-slash x-show="show" x-cloak variant="mini" />
                </button>
            </div>
            <flux:error name="password" />
        </div>

        <div class="animate-element animate-delay-500 flex flex-wrap items-center justify-between gap-3 text-sm" x-data="{ help: false }">
            <label class="flex cursor-pointer items-center gap-2.5">
                <input type="checkbox" wire:model="remember" class="size-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                <span class="text-zinc-700 dark:text-zinc-300">Rester connecté</span>
            </label>
            <button type="button" x-on:click="help = ! help" class="font-medium text-brand-600 hover:underline dark:text-brand-300">Mot de passe oublié ?</button>
            <p x-show="help" x-cloak x-transition class="basis-full rounded-xl bg-brand-50 p-3 text-xs text-brand-800 dark:bg-brand-500/10 dark:text-brand-200">
                Demandez à l’administrateur de réinitialiser votre mot de passe (Paramètres › Utilisateurs). Un mot de passe provisoire vous sera remis ; vous le changerez à la connexion.
            </p>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="animate-element animate-delay-600 flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="login">Se connecter</span>
            <span wire:loading wire:target="login">Connexion…</span>
            <flux:icon.arrow-right variant="micro" wire:loading.remove wire:target="login" />
        </button>
    </form>

    @if ($demoAccounts->isNotEmpty())
        <div class="animate-element animate-delay-700" x-data="{ open: false }">
            <button type="button" x-on:click="open = ! open" class="flex w-full items-center justify-center gap-1.5 text-sm text-zinc-500 hover:text-brand-600">
                <flux:icon.user-group variant="micro" /> Comptes de démonstration
                <flux:icon.chevron-down variant="micro" class="transition" ::class="open && 'rotate-180'" />
            </button>
            <div x-show="open" x-cloak x-collapse class="mt-3">
                <p class="mb-2 text-xs text-zinc-500">Cliquez sur un compte pour pré-remplir le formulaire (mot de passe « {{ \App\Support\DemoAccounts::PASSWORD }} »).</p>
                <div class="grid max-h-72 gap-1.5 overflow-y-auto pe-1 sm:grid-cols-2">
                    @foreach ($demoAccounts as $account)
                        <button type="button" wire:click="useDemoAccount('{{ $account['username'] }}')" wire:key="demo-{{ $account['username'] }}"
                            class="rounded-xl border border-zinc-200 px-3 py-2 text-left transition hover:border-brand-300 hover:bg-brand-50 dark:border-zinc-700 dark:hover:bg-brand-500/10">
                            <span class="block truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $account['label'] }}</span>
                            <span class="block truncate font-mono text-xs text-zinc-500">{{ $account['username'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
