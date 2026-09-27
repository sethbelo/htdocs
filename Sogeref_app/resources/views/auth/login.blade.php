<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#104A63">
    <title>Connexion · SOGEREF ERP</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/icon.svg') }}">
    <script>(() => { try { const m = localStorage.getItem('sogeref-theme'); if (m === 'dark') document.documentElement.classList.add('dark'); } catch (e) {} })();</script>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-canvas">
<div class="grid min-h-full lg:grid-cols-[1.05fr_1fr]">

    {{-- ═══ 1. PANNEAU DE MARQUE ═══════════════════════════════════════════════
         Motif « rubans » du Brand Guideline (jaune, turquoise, rouge) sur bleu. --}}
    <section class="relative hidden overflow-hidden bg-brand-600 text-white lg:flex lg:flex-col">
        <svg class="absolute inset-0 size-full opacity-[.16]" viewBox="0 0 800 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <g fill="none" stroke-linecap="round">
                <path d="M60 120c60-40 120 40 180 0" stroke="#FFCE31" stroke-width="34"/>
                <path d="M520 80c50 30 90-20 150 20" stroke="#F7313A" stroke-width="34"/>
                <path d="M300 300c40 50 110 30 150 80" stroke="#3ACECE" stroke-width="34"/>
                <path d="M620 360c40-40 100 10 140-30" stroke="#FFCE31" stroke-width="34"/>
                <path d="M80 520c70 10 90 70 160 60" stroke="#F7313A" stroke-width="34"/>
                <path d="M420 620c50-30 100 40 170 10" stroke="#3ACECE" stroke-width="34"/>
                <path d="M160 800c40-50 110-20 150-60" stroke="#FFCE31" stroke-width="34"/>
                <path d="M600 780c50 30 90-10 150 30" stroke="#F7313A" stroke-width="34"/>
            </g>
        </svg>
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-brand-900/80 to-transparent"></div>

        <div class="relative flex flex-1 flex-col justify-between p-12 xl:p-16">
            <x-ui.logo variant="horizontal" white class="h-16 w-auto self-start" />
            <div class="max-w-lg">
                <p class="eyebrow !text-sun-300">Progiciel de gestion intégré</p>
                <h1 class="mt-3 font-display text-[40px] leading-[1.08] font-bold">Sculptons vos rêves,<br>bâtissons la réalité.</h1>
                <p class="mt-4 text-[15px] leading-relaxed text-white/75">Du devis à la livraison : atelier, chantiers, stock, caisse, finance, ressources humaines et paie réunis dans un seul outil.</p>
                <ul class="mt-8 grid grid-cols-2 gap-3 text-[13px] text-white/85">
                    @foreach (['Suivi de commande de bout en bout', 'Atelier & fiches de fabrication', 'Chantiers & listes de présence', 'Paie mensuelle et journaliers'] as $point)
                        <li class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-sun-400 text-brand-900"><x-ui.icon name="check" variant="s" class="size-3.5" /></span>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
            <p class="text-[12px] text-white/50">© {{ date('Y') }} SOGEREF Sarl · Av. du Port, Concession SCTP / ex ONATRA, Gombe — Kinshasa, RDC</p>
        </div>
    </section>

    {{-- ═══ 2. FORMULAIRE ═════════════════════════════════════════════════════ --}}
    <section class="flex flex-col justify-center px-6 py-12 sm:px-12">
        <div class="mx-auto w-full max-w-sm">
            <x-ui.logo variant="horizontal" class="mb-10 h-14 w-auto lg:hidden dark:hidden" />
            <x-ui.logo variant="horizontal" white class="mb-10 hidden h-14 w-auto dark:block lg:!hidden" />

            <h2 class="font-display text-[28px] font-semibold text-fg">Connexion</h2>
            <p class="mt-1 text-[14px] text-fg-muted">Accédez à votre espace de travail SOGEREF.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" x-data="{ show: false }">
                @csrf
                <div>
                    <label for="email" class="label">Adresse e-mail</label>
                    <div class="relative">
                        <x-ui.icon name="envelope" class="pointer-events-none absolute top-1/2 left-3 size-[18px] -translate-y-1/2 text-fg-subtle" />
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input h-11 pl-10 @error('email') input-error @enderror" placeholder="prenom.nom@sogeref.cd">
                    </div>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="label">Mot de passe</label>
                    <div class="relative">
                        <x-ui.icon name="lock-closed" class="pointer-events-none absolute top-1/2 left-3 size-[18px] -translate-y-1/2 text-fg-subtle" />
                        <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" class="input h-11 pr-11 pl-10" placeholder="••••••••">
                        <button type="button" x-on:click="show = !show" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-fg-subtle hover:text-fg" :aria-label="show ? 'Masquer' : 'Afficher'">
                            <x-ui.icon name="eye" class="size-[18px]" x-show="!show" /><x-ui.icon name="eye-slash" class="size-[18px]" x-show="show" x-cloak />
                        </button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-[13.5px] text-fg-muted"><input type="checkbox" name="remember" class="checkbox"> Rester connecté sur cet appareil</label>
                <button type="submit" class="btn btn-primary btn-lg w-full">Se connecter <x-ui.icon name="arrow-right" class="size-[18px]" /></button>
            </form>

            @if (app()->environment('local'))
                <div class="mt-8 rounded-xl border border-dashed border-line-strong p-4 text-[12.5px] text-fg-muted">
                    <p class="font-semibold text-fg">Comptes de démonstration</p>
                    <p class="mt-1">master@sogeref.cd · direction@sogeref.cd · caisse@sogeref.cd · atelier@sogeref.cd · rh@sogeref.cd — mot de passe <span class="kbd">Sogeref@2026</span></p>
                </div>
            @endif
            <div class="brand-rule mt-10 rounded-full"></div>
        </div>
    </section>
</div>
@livewireScripts
</body>
</html>
