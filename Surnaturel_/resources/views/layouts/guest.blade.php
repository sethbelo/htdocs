<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-dvh bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-white">
    <div class="flex min-h-dvh flex-col overflow-x-clip md:h-dvh md:flex-row">
        {{-- Colonne formulaire --}}
        <main class="flex flex-1 items-start justify-center px-5 py-8 sm:px-8 md:items-center md:overflow-y-auto md:py-10">
            <div class="w-full max-w-md">
                <div class="animate-element mb-8 hidden md:block">
                    <x-app-logo variant="full" class="h-14 w-auto" />
                </div>
                {{ $slot }}
                <p class="mt-10 text-center text-xs text-zinc-400">© {{ now()->year }} {{ \App\Models\Setting::get('company.legal_name', \App\Models\Setting::get('company.name')) }}</p>
            </div>
        </main>

        {{-- Colonne visuelle : carrousel des services (bandeau en haut sur mobile) --}}
        <section class="order-first h-[52svh] min-h-[320px] max-h-[540px] p-3 md:order-none md:h-auto md:max-h-none md:min-h-0 md:flex-[1.05] md:p-4 lg:flex-[1.2]" aria-label="Nos services">
            <div class="animate-slide-right animate-delay-200 relative h-full overflow-hidden rounded-3xl shadow-xl ring-1 ring-black/5">
                <x-auth.services-carousel />
            </div>
        </section>
    </div>
    @fluxScripts
</body>
</html>
