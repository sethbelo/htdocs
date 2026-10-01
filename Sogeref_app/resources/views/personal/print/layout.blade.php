{{-- Vue d'impression de l'Espace personnel (navigateur) : en-tête SOGEREF, impression automatique --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Impression' }} — SOGEREF</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm 14mm 16mm; }
        body { background: #fff; color: #0f2733; }
        .sheet td, .sheet th { cursor: default; }
        @media print { .print-actions { display: none !important; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body class="bg-white text-[14px]">
    <div class="mx-auto max-w-4xl px-6 py-8 print:p-0">
        <div class="print-actions mb-6 flex justify-end gap-2">
            <button type="button" onclick="window.close()" class="btn btn-ghost btn-sm">Fermer</button>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">Imprimer</button>
        </div>
        <header class="flex items-end justify-between gap-6">
            <img src="{{ asset('brand/logo-horizontal.png') }}" alt="SOGEREF Sarl" class="h-12">
            <div class="text-right">
                <p class="font-display text-[18px] font-bold tracking-wide text-brand-600">{{ $heading ?? '' }}</p>
                @isset($reference)<p class="text-[12px] font-semibold text-flame-500">{{ $reference }}</p>@endisset
                <p class="text-[11.5px] text-ink-500">{{ auth()->user()->name }} · imprimé le {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </header>
        <div class="brand-rule mt-3 mb-6"></div>
        @yield('content')
        <footer class="mt-10 border-t border-ink-200 pt-3 text-center text-[10.5px] text-ink-500">{{ company()->document_footer }}</footer>
    </div>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
