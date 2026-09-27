<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-hero title="Mon profil" eyebrow="Compte & sécurité" description="Personnalisez votre identité, vos accès et la sécurité de votre compte Yeboox." icon="user-circle" badge-icon="shield-check" illustration="settings">
            <x-slot:actions>
                <flux:badge color="violet" icon="identification" class="bg-white/10! text-white! ring-white/20!">{{ Auth::user()->getRoleNames()->first() ?: 'Utilisateur' }}</flux:badge>
            </x-slot:actions>
        </x-page-hero>

        {{-- Carte d'identité en flux naturel : aucun positionnement fixe ou sticky. --}}
        <flux:card class="overflow-hidden p-0! shadow-sm">
            <div class="relative bg-linear-to-r from-violet-50 via-white to-indigo-50 p-5 dark:from-violet-950/30 dark:via-zinc-900 dark:to-indigo-950/30 sm:p-6">
                <div aria-hidden="true" class="absolute right-0 top-0 size-40 rounded-full bg-violet-200/30 blur-3xl dark:bg-violet-700/15"></div>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="relative mx-auto shrink-0 sm:mx-0">
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="Photo de {{ Auth::user()->name }}" class="size-24 rounded-2xl object-cover ring-4 ring-white shadow-md dark:ring-zinc-800">
                        <span class="absolute -bottom-1 -right-1 grid size-7 place-items-center rounded-full bg-emerald-500 text-white ring-4 ring-white dark:ring-zinc-900" title="Compte actif"><flux:icon.check class="size-4" /></span>
                    </div>

                    <div class="min-w-0 flex-1 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <flux:heading size="xl">{{ Auth::user()->name }}</flux:heading>
                            <flux:badge color="emerald" icon="check-badge">Actif</flux:badge>
                        </div>
                        <flux:text class="mt-1 break-all">{{ Auth::user()->email }}</flux:text>
                        <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                            <flux:badge color="violet" icon="identification">{{ Auth::user()->getRoleNames()->first() ?: 'Utilisateur' }}</flux:badge>
                            <flux:badge color="zinc" icon="calendar-days">Compte créé {{ Auth::user()->created_at?->translatedFormat('M Y') }}</flux:badge>
                        </div>
                    </div>

                    <flux:modal.trigger name="confirm-logout">
                        <flux:button type="button" variant="danger" icon="arrow-right-start-on-rectangle" class="w-full sm:w-auto">Déconnexion</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </flux:card>

        {{-- Navigation horizontale responsive, intégrée au document et jamais fixe. --}}
        <flux:card class="p-3! shadow-sm">
            <nav class="grid gap-2 sm:grid-cols-3" aria-label="Sections du profil">
                <a href="#informations" class="group flex items-center gap-3 rounded-xl bg-violet-50 px-3.5 py-3 ring-1 ring-violet-100 transition hover:bg-violet-100 dark:bg-violet-950/35 dark:ring-violet-900/60 dark:hover:bg-violet-950/60">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-violet-700 shadow-sm dark:bg-violet-900/60 dark:text-violet-200"><flux:icon.identification class="size-[1.125rem]" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-violet-800 dark:text-violet-200">Informations</span><span class="block truncate text-xs text-violet-600/80 dark:text-violet-300/70">Identité et photo</span></span>
                    <flux:icon.chevron-down class="size-4 text-violet-400 transition group-hover:translate-y-0.5" />
                </a>

                <a href="#securite" class="group flex items-center gap-3 rounded-xl px-3.5 py-3 transition hover:bg-zinc-100 dark:hover:bg-white/5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"><flux:icon.shield-check class="size-[1.125rem]" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">Sécurité</span><span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">Mot de passe</span></span>
                    <flux:icon.chevron-down class="size-4 text-zinc-400 transition group-hover:translate-y-0.5" />
                </a>

                <a href="#appareils" class="group flex items-center gap-3 rounded-xl px-3.5 py-3 transition hover:bg-zinc-100 dark:hover:bg-white/5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"><flux:icon.computer-desktop class="size-[1.125rem]" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">Appareils</span><span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">Sessions connectées</span></span>
                    <flux:icon.chevron-down class="size-4 text-zinc-400 transition group-hover:translate-y-0.5" />
                </a>
            </nav>
        </flux:card>

        <div class="space-y-6">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <section id="informations" class="scroll-mt-24">@livewire('profile.update-profile-information-form')</section>
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <section id="securite" class="scroll-mt-24">@livewire('profile.update-password-form')</section>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></div>
                <flux:text size="sm" class="font-semibold uppercase tracking-wider">Protection avancée</flux:text>
                <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></div>
            </div>

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <section class="scroll-mt-24">@livewire('profile.two-factor-authentication-form')</section>
            @endif

            <section id="appareils" class="scroll-mt-24">@livewire('profile.logout-other-browser-sessions-form')</section>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                @livewire('profile.delete-user-form')
            @endif
        </div>
    </div>
</x-app-layout>
