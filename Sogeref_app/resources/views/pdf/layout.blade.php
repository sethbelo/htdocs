{{--
    Papier à en-tête SOGEREF (rapports, bons, fiches, bulletins…).
    Variables : $docTitle, $docReference, $docSubtitle ; section « content ».
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
    <main>@yield('content')</main>
</body>
</html>
