<main class="relative min-h-screen overflow-hidden bg-zinc-950 px-4 py-8 text-zinc-950 dark:text-white sm:px-6 lg:grid lg:place-items-center lg:py-12">
    @if($welcomeStep < 5)
        @include('livewire.partials.welcome-tour')
    @else
    <div class="pointer-events-none absolute inset-0 opacity-50" aria-hidden="true">
        <div class="absolute -left-32 -top-32 size-96 rounded-full bg-violet-600/30 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-28 size-[30rem] rounded-full bg-fuchsia-500/20 blur-3xl"></div>
    </div>

    <div class="relative mx-auto grid w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-white/15 dark:bg-zinc-900 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="relative hidden min-h-[44rem] overflow-hidden bg-violet-700 p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <img src="{{ asset('images/african-restaurant.jpg') }}" alt="" class="absolute inset-0 size-full object-cover opacity-35" />
            <div class="absolute inset-0 bg-linear-to-br from-violet-950/95 via-violet-800/80 to-fuchsia-700/60"></div>
            <div class="relative">
                <span class="inline-flex rounded-2xl bg-white px-4 py-3 shadow-lg"><img src="{{ asset('images/yeboox-logo.png') }}" alt="Yeboox" class="h-10 w-auto" /></span>
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.25em] text-violet-200">Yetoo Tech · Installation Windows</p>
                <h1 class="mt-4 max-w-md text-4xl font-semibold leading-tight">Votre espace de gestion, prêt sur ce poste.</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-violet-100/85">PHP et SQLite fonctionnent localement. Vos opérations restent disponibles même sans connexion Internet.</p>
            </div>
            <div class="relative grid gap-3 text-sm">
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15"><flux:icon.circle-stack class="size-5" /> Base SQLite privée et persistante</div>
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15"><flux:icon.shield-check class="size-5" /> Administrateur principal sécurisé</div>
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15"><flux:icon.computer-desktop class="size-5" /> Optimisé pour Electron Windows</div>
            </div>
        </section>

        <section class="p-6 sm:p-10 lg:p-12">
            <div class="mb-8 flex items-start gap-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"><flux:icon.wrench-screwdriver class="size-6" /></span>
                <div>
                    <flux:heading level="1" size="xl">Première configuration</flux:heading>
                    <flux:text class="mt-1">Créez votre administrateur principal pour commencer avec une base de production vide.</flux:text>
                </div>
            </div>

            <form wire:submit="complete" class="space-y-7">
                <button type="button" wire:click="previousWelcome" class="text-sm font-semibold text-violet-600">← Revoir la présentation</button>
                <fieldset class="space-y-3">
                    <legend class="mb-3 flex items-center gap-2 text-sm font-semibold"><span class="grid size-7 place-items-center rounded-full bg-violet-600 text-xs text-white">1</span> Administrateur principal</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="adminName" label="Nom complet" icon="user" autocomplete="name" required />
                        <flux:input wire:model="adminEmail" type="email" label="Adresse e-mail" icon="envelope" autocomplete="email" required />
                        <flux:input wire:model="password" type="password" label="Mot de passe" icon="lock-closed" viewable autocomplete="new-password" required />
                        <flux:input wire:model="password_confirmation" type="password" label="Confirmation" icon="lock-closed" viewable autocomplete="new-password" required />
                    </div>
                </fieldset>

                <fieldset class="space-y-4 rounded-2xl border border-violet-200 bg-violet-50/60 p-5 dark:border-violet-900 dark:bg-violet-500/5">
                    <legend class="flex items-center gap-2 px-2 text-sm font-semibold"><span class="grid size-7 place-items-center rounded-full bg-violet-600 text-xs text-white">2</span> Monnaie de l’entreprise</legend>
                    <div class="flex gap-3 rounded-xl bg-white p-4 text-sm shadow-sm dark:bg-zinc-900">
                        <flux:icon.banknotes class="mt-0.5 size-5 shrink-0 text-violet-600" />
                        <p><strong>Une seule monnaie comptable.</strong> Tous les produits, ventes, stocks et écritures seront enregistrés dans la devise de base. La devise secondaire apparaîtra uniquement sur les factures et tickets clients.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:select wire:model.live="baseCurrency" label="Devise de base" description="Devise des prix et de la comptabilité">
                            @foreach($currencies as $code => $currency)
                                <flux:select.option value="{{ $code }}">{{ $currency['label'] }} ({{ $code }})</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="secondaryCurrency" label="Devise secondaire" description="Affichée sur les tickets et factures">
                            @foreach($currencies as $code => $currency)
                                @continue($code === $baseCurrency)
                                <flux:select.option value="{{ $code }}">{{ $currency['label'] }} ({{ $code }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:input wire:model="exchangeRate" inputmode="decimal" label="Taux de conversion" description="1 {{ $baseCurrency }} = combien de {{ $secondaryCurrency }} ?" suffix="{{ $secondaryCurrency }}" required />
                    <p class="text-xs text-zinc-500">Exemple : avec USD comme base et CDF comme secondaire, saisissez 2800 si 1 USD = 2 800 CDF.</p>
                </fieldset>

                <flux:callout icon="sparkles" heading="Prêt pour votre activité">Ajoutez vos propres produits et prix après cette étape. Aucun client, produit, rendez-vous, vente ou écriture comptable de démonstration ne sera ajouté.</flux:callout>

                <flux:button type="submit" variant="primary" color="violet" icon="check-circle" class="w-full justify-center" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="complete">Créer l’administrateur et démarrer</span>
                    <span wire:loading wire:target="complete">Préparation en cours…</span>
                </flux:button>
            </form>
        </section>
    </div>
    @endif
</main>
