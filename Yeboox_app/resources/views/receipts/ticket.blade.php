<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @php
        $paperWidth = $ticketSettings['paper_size'] === 'a4' ? '190mm' : $ticketSettings['paper_size'].'mm';
        $currencySymbol = $establishment?->defaultCurrency?->symbol ?? 'FC';
        $logoSource = null;
        if ($ticketSettings['show_logo'] && $establishment?->logo_path) {
            $logoPath = public_path('storage/'.$establishment->logo_path);
            $logoSource = file_exists($logoPath) ? $logoPath : null;
        }
    @endphp
    <style>@include('receipts.partials.styles')</style>
</head>
<body class="template-{{ $ticketSettings['template'] }}">
    @include('receipts.partials.content', ['isAddition' => false])
</body>
</html>
