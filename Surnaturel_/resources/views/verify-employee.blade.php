<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    @include('partials.head', ['title' => 'Vérification de carte'])
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="grid min-h-dvh place-items-center bg-zinc-50 p-5 font-sans antialiased dark:bg-zinc-950">
    <main class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
        <div class="bg-zinc-900 px-6 py-5"><x-app-logo variant="full" tone="light" class="h-10 w-auto" /></div>
        <div class="brand-rule rounded-none"></div>
        <div class="space-y-5 p-6 text-center">
            @if ($photo = $employee->photoDataUri())
                <img src="{{ $photo }}" alt="" class="mx-auto size-28 rounded-2xl object-cover ring-4 ring-white shadow-md">
            @else
                <div class="mx-auto grid size-28 place-items-center rounded-2xl bg-zinc-100 font-display text-3xl font-bold text-zinc-500 dark:bg-white/10">{{ $employee->initials() }}</div>
            @endif
            <div>
                <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">{{ $employee->fullName() }}</h1>
                <p class="font-semibold text-[#E6007E]">{{ $employee->job_title ?? 'Agent' }}</p>
                <p class="text-sm text-zinc-500">{{ $employee->department?->name }} · {{ $employee->matricule }}</p>
            </div>
            @if ($valid)
                <div class="flex items-center justify-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <flux:icon.check-badge /> Carte valide jusqu’au {{ $employee->card_expires_at->format('d/m/Y') }}
                </div>
            @else
                <div class="flex items-center justify-center gap-2 rounded-2xl bg-red-50 px-4 py-3 font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
                    <flux:icon.x-circle /> {{ $employee->is_active ? 'Carte expirée' : 'Agent ne faisant plus partie du personnel' }}
                </div>
            @endif
            <p class="text-xs text-zinc-400">Vérification effectuée le {{ now()->format('d/m/Y à H:i') }} · {{ $company['name'] }} · {{ $company['phone'] }}</p>
        </div>
    </main>
</body>
</html>
