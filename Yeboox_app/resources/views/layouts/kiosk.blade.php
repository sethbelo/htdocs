<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>Yeboox POS — Kiosque</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-user-select: none;
            user-select: none;
            overflow: hidden;
            touch-action: manipulation;
        }
        /* Hide scrollbars */
        ::-webkit-scrollbar { display: none; }
        * { scrollbar-width: none; }

        /* Kiosk-specific large touch targets */
        .kiosk-btn {
            min-height: 64px;
            min-width: 64px;
            font-size: 1.1rem;
        }
        .kiosk-product-card {
            min-height: 180px;
            touch-action: manipulation;
        }
        /* Prevent text selection & context menu on touch */
        * { -webkit-touch-callout: none; }
    </style>
</head>
<body class="bg-slate-950 text-white h-screen w-screen overflow-hidden">
    {{ $slot }}

    @livewireScripts
    @fluxScripts
</body>
</html>
