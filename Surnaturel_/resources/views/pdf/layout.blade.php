<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: {{ $format === 'ticket' ? '6pt 8pt' : ($format === 'card' ? '0' : '28pt 32pt') }}; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; font-size: {{ $format === 'ticket' ? '8pt' : ($format === 'a5' ? '8.5pt' : '9.5pt') }}; margin: 0; }
        h1, h2, h3 { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #71717a; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .small { font-size: 0.85em; }
        .items th { background: #f4f4f5; text-align: left; padding: 5pt 4pt; font-size: 0.85em; text-transform: uppercase; color: #52525b; }
        .items td { padding: 5pt 4pt; border-bottom: 0.5pt solid #e4e4e7; vertical-align: top; }
        .totals td { padding: 3pt 4pt; }
        .totals .grand td { font-size: 1.15em; font-weight: bold; border-top: 1pt solid #18181b; }
        .box { border: 0.5pt solid #d4d4d8; border-radius: 4pt; padding: 8pt; }
        .badge { display: inline-block; padding: 2pt 6pt; border-radius: 8pt; background: #eef2ff; color: #4338ca; font-size: 0.85em; font-weight: bold; }
        .dashed { border-top: 1pt dashed #71717a; margin: 5pt 0; }
        .ticket td { padding: 1.5pt 0; vertical-align: top; }
        .footer { position: fixed; bottom: -12pt; left: 0; right: 0; font-size: 7pt; color: #a1a1aa; text-align: center; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
