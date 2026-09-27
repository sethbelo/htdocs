<x-guest-layout>
    <main
        x-data="loginGallery"
        class="grid min-h-dvh overflow-hidden bg-white lg:h-dvh lg:grid-cols-[minmax(430px,46%)_1fr] dark:bg-zinc-950"
    >
        <section class="relative z-10 flex min-h-dvh items-center justify-center px-6 py-8 sm:px-10 lg:min-h-0 lg:px-12 xl:px-20">
            <div class="w-full max-w-md">
                <header class="mb-8">
                    <div class="mb-8 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-600/25" aria-hidden="true">
                                <flux:icon.calculator class="size-6" />
                            </span>
                            <div>
                                <flux:heading size="lg">Yeboox</flux:heading>
                                <flux:text size="sm">Application de gestion desktop</flux:text>
                            </div>
                        </div>
                        <flux:badge color="emerald" icon="check-circle" size="sm">En ligne</flux:badge>
                    </div>

                    <flux:heading level="1" class="text-4xl! font-semibold! tracking-tight! sm:text-5xl!">
                        Bon retour parmi nous
                    </flux:heading>
                    <flux:text class="mt-3 max-w-sm text-base!">
                        Accédez à votre espace de gestion sécurisé et continuez votre activité.
                    </flux:text>
                </header>

                @if($errors->any())
                    <flux:callout class="mb-5" variant="danger" icon="exclamation-circle" heading="Connexion impossible" text="{{ $errors->first() }}" />
                @endif

                @session('status')
                    <flux:callout class="mb-5" variant="success" icon="check-circle" text="{{ $value }}" />
                @endsession

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <flux:input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        label="Adresse e-mail"
                        icon="envelope"
                        placeholder="nom@entreprise.com"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <flux:field>
                        <div class="mb-1.5 flex items-center justify-between gap-3">
                            <flux:label for="password">Mot de passe</flux:label>
                            @if(Route::has('password.request'))
                                <flux:link href="{{ route('password.request') }}" variant="subtle" class="text-xs">Mot de passe oublié ?</flux:link>
                            @endif
                        </div>
                        <flux:input
                            id="password"
                            type="password"
                            name="password"
                            icon="lock-closed"
                            placeholder="Votre mot de passe"
                            required
                            autocomplete="current-password"
                            viewable
                        />
                    </flux:field>

                    <div class="flex items-center justify-between gap-4">
                        <flux:checkbox id="remember_me" name="remember" label="Rester connecté" />
                        <flux:text size="sm" class="hidden items-center gap-1.5 sm:flex">
                            <flux:icon.shield-check class="size-4" /> Accès chiffré
                        </flux:text>
                    </div>

                    <flux:button type="submit" variant="primary" icon:trailing="arrow-right" class="w-full">
                        Se connecter
                    </flux:button>
                </form>

                <footer class="mt-8 flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    <flux:text size="sm">© {{ now()->year }} Yetoo Technologie</flux:text>
                    <flux:badge color="zinc" icon="computer-desktop" size="sm">Application locale</flux:badge>
                </footer>
            </div>
        </section>

        <section
            class="relative m-4 hidden min-h-0 overflow-hidden rounded-[2rem] bg-zinc-950 lg:block"
            aria-label="Les solutions Yeboox"
            x-on:mouseenter="pause"
            x-on:mouseleave="start"
            x-on:focusin="pause"
            x-on:focusout="start"
        >
            @php
                $slides = [
                    ['image' => 'images/african-bar.jpg', 'label' => 'Commerce', 'title' => 'Une caisse rapide, même aux heures de pointe.', 'text' => 'Encaissement, stocks et suivi des ventes dans une seule application.'],
                    ['image' => 'images/african-hotel.jpg', 'label' => 'Hôtellerie', 'title' => 'Pilotez chaque service avec une vue claire.', 'text' => 'Centralisez les opérations et gardez vos équipes synchronisées.'],
                    ['image' => 'images/african-pharmacy.jpg', 'label' => 'Pharmacie', 'title' => 'Le bon produit, au bon moment.', 'text' => 'Maîtrisez les niveaux de stock et les mouvements sensibles.'],
                    ['image' => 'images/african-restaurant.jpg', 'label' => 'Restauration', 'title' => 'Transformez le service en expérience fluide.', 'text' => 'Prenez les commandes, encaissez et analysez sans ralentir la salle.'],
                    ['image' => 'images/african-spa.jpg', 'label' => 'Bien-être', 'title' => 'Vos rendez-vous et ventes réunis.', 'text' => 'Une organisation simple pour offrir un accueil remarquable.'],
                ];
            @endphp

            @foreach($slides as $index => $slide)
                <img
                    x-show="active === {{ $index }}"
                    x-transition:enter="transition-opacity duration-700 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-700 motion-reduce:transition-none"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    src="{{ asset($slide['image']) }}"
                    alt=""
                    class="absolute inset-0 size-full object-cover"
                    @if($index !== 0) x-cloak @endif
                >
            @endforeach

            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/15 to-black/10"></div>
            <div class="absolute inset-x-0 top-0 flex items-center justify-between p-7">
                <flux:badge color="zinc" icon="sparkles" class="border-white/15! bg-black/25! text-white! backdrop-blur-xl">Yeboox pour votre métier</flux:badge>
                <flux:badge color="emerald" icon="wifi" class="border-white/15! bg-black/25! text-white! backdrop-blur-xl">Prêt à travailler</flux:badge>
            </div>

            <div class="absolute inset-x-0 bottom-0 p-8 xl:p-10">
                @foreach($slides as $index => $slide)
                    <div x-show="active === {{ $index }}" @if($index !== 0) x-cloak @endif>
                        <flux:badge color="violet" size="sm">{{ $slide['label'] }}</flux:badge>
                        <flux:heading level="2" class="mt-4 max-w-xl text-3xl! font-semibold! text-white! xl:text-4xl!">
                            {{ $slide['title'] }}
                        </flux:heading>
                        <p class="mt-3 max-w-lg text-sm leading-6 text-white/75 xl:text-base">{{ $slide['text'] }}</p>
                    </div>
                @endforeach

                <div class="mt-7 flex items-center justify-between gap-5">
                    <div class="flex items-center gap-2" role="group" aria-label="Choisir une image">
                        @foreach($slides as $index => $slide)
                            <flux:button
                                type="button"
                                size="sm"
                                variant="subtle"
                                square
                                x-on:click="go({{ $index }})"
                                x-bind:class="active === {{ $index }} ? 'bg-white! text-zinc-950!' : 'bg-white/15! text-white! hover:bg-white/25!'"
                                aria-label="Afficher {{ $slide['label'] }}"
                            >
                                <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                            </flux:button>
                        @endforeach
                    </div>
                    <flux:text size="sm" class="text-white/65!">
                        <span x-text="String(active + 1).padStart(2, '0')"></span> / {{ str_pad((string) count($slides), 2, '0', STR_PAD_LEFT) }}
                    </flux:text>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('loginGallery', () => ({
                active: 0,
                timer: null,
                init() { this.start() },
                destroy() { this.pause() },
                start() {
                    this.pause()
                    this.timer = window.setInterval(() => {
                        this.active = (this.active + 1) % {{ count($slides) }}
                    }, 5500)
                },
                pause() {
                    if (this.timer) window.clearInterval(this.timer)
                    this.timer = null
                },
                go(index) {
                    this.active = index
                    this.start()
                },
            }))
        })
    </script>
</x-guest-layout>
