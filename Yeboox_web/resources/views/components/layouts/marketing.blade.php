<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Yeboox est le logiciel de caisse et de gestion conçu pour les commerces africains : ventes, stock, clients, facturation, comptabilité SYSCOHADA et pilotage multi-sites.">
    <meta name="keywords" content="logiciel de caisse Afrique, POS Kinshasa, gestion de stock RDC, caisse restaurant, comptabilité SYSCOHADA, point de vente africain, Yeboox">
    <meta name="author" content="Yetu Technologie">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="theme-color" content="#101a46">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="alternate" hreflang="fr" href="{{ route('home') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_CD">
    <meta property="og:site_name" content="Yeboox">
    <meta property="og:title" content="Yeboox — La caisse qui fait avancer votre commerce">
    <meta property="og:description" content="Vendez, gérez vos stocks et pilotez chaque point de vente avec une solution pensée pour les réalités africaines.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image" content="{{ asset('images/marketing/yeboox-african-retail-pos.png') }}">
    <meta property="og:image:alt" content="Commerçante africaine utilisant le point de vente Yeboox">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Yeboox — POS et gestion pour les entreprises africaines">
    <meta name="twitter:description" content="Encaissez, suivez vos stocks et prenez de meilleures décisions, même avec une connexion instable.">
    <meta name="twitter:image" content="{{ asset('images/marketing/yeboox-african-retail-pos.png') }}">
    <title>Yeboox — Logiciel de caisse, stock et gestion en Afrique</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/yeboox-icon.png') }}">
    <script>
        (() => {
            const storedTheme = localStorage.getItem('yeboox-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', storedTheme ? storedTheme === 'dark' : prefersDark);
            window.yebooxTheme = {
                toggle() {
                    const dark = !document.documentElement.classList.contains('dark');
                    document.documentElement.classList.toggle('dark', dark);
                    localStorage.setItem('yeboox-theme', dark ? 'dark' : 'light');
                },
            };
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization', '@id' => route('home').'#organization',
                    'name' => 'Yeboox', 'legalName' => 'Yetu Technologie', 'url' => route('home'),
                    'logo' => asset('images/brand/yeboox-logo.png'), 'email' => config('services.yeboox.sales_email'),
                    'areaServed' => 'Africa',
                ],
                [
                    '@type' => 'SoftwareApplication', '@id' => route('home').'#software', 'name' => 'Yeboox',
                    'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web, Windows, Android',
                    'description' => 'Plateforme SaaS de point de vente, stock, CRM et comptabilité SYSCOHADA pour les entreprises africaines.',
                    'image' => asset('images/marketing/yeboox-african-retail-pos.png'),
                    'offers' => ['@type' => 'AggregateOffer', 'lowPrice' => '19', 'highPrice' => '79', 'priceCurrency' => 'USD', 'offerCount' => '3'],
                    'publisher' => ['@id' => route('home').'#organization'],
                ],
                ['@type' => 'WebSite', '@id' => route('home').'#website', 'name' => 'Yeboox', 'url' => route('home'), 'inLanguage' => 'fr-CD', 'publisher' => ['@id' => route('home').'#organization']],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        ['@type' => 'Question', 'name' => 'Yeboox fonctionne-t-il sans connexion internet ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Oui. Le point de vente Desktop continue les ventes hors-ligne et synchronise les données dès que la connexion revient.']],
                        ['@type' => 'Question', 'name' => 'Quelle formule choisir pour commencer ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Essentiel convient à un premier point de vente, Pro ajoute les fonctions de pilotage avancées et Réseau accompagne les entreprises multi-sites.']],
                        ['@type' => 'Question', 'name' => 'Yeboox prend-il en charge plusieurs devises ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Oui. Yeboox accompagne les opérations en USD et en CDF avec un taux de change configurable.']],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="min-h-screen bg-[#f7f7f2] font-sans text-slate-950 antialiased selection:bg-blue-600 selection:text-white dark:bg-[#070b19] dark:text-white">
    <a href="#contenu" class="sr-only z-[100] rounded-full bg-white px-5 py-3 font-bold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Aller au contenu</a>
    <flux:accent color="blue" />
    {{ $slot }}
    <flux:toast />
    @livewireScripts
    @fluxScripts
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-to-request', () => requestAnimationFrame(() => {
                document.getElementById('demande')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }));
        });
    </script>
</body>
</html>
