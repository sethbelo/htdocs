<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $isAddition ? (($provisionalDocumentType ?? 'addition') === 'proforma' ? 'REÇU PROFORMA' : 'ADDITION') : $ticketSettings['header_text'] }} — {{ $establishment->name ?? 'POS' }}</title>
    @php
        $paperWidth = $ticketSettings['paper_size'] === 'a4' ? '190mm' : $ticketSettings['paper_size'].'mm';
        $currencySymbol ??= $establishment?->defaultCurrency?->symbol ?? 'FC';
        $currencyDecimals ??= 0;
        $logoSource = $ticketSettings['show_logo'] ? $establishment?->logo_url : null;
    @endphp
    <style>@include('receipts.partials.styles')</style>
</head>
<body class="template-{{ $ticketSettings['template'] }}">
    @include('receipts.partials.content')

    @if($ticketSettings['auto_print'])
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.print();
                setTimeout(() => window.close(), 1500);
            });
        </script>
    @endif
</body>
</html>
