{{--
    Papier à en-tête SURNATUREL PRINT (A4, A5, A4 paysage) : factures, devis, bons, rapports, fiches.
    Variables : $docTitle, $docReference, $docSubtitle, $watermark ; section « content ».
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle ?? 'Document' }} {{ $docReference ?? '' }}</title>
    @include('pdf.partials.styles')
    @stack('styles')
</head>
<body>
    @include('pdf.partials.header')
    @include('pdf.partials.footer')
    @if (filled($watermark ?? null))<div class="watermark">{{ $watermark }}</div>@endif
    <main>@yield('content')</main>
</body>
</html>
