{{-- Ticket thermique 80 mm à la charte SURNATUREL PRINT (impression monochrome) --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8pt 9pt; }
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 7.6pt; color: #000; margin: 0; line-height: 1.3; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1.2pt 0; vertical-align: top; }
        .center { text-align: center; } .right { text-align: right; } .bold { font-weight: bold; }
        .small { font-size: 6.6pt; } .muted { color: #444; }
        .sep { border-top: .8pt dashed #000; margin: 5pt 0; }
        .rule { border-top: 1.6pt solid #000; margin: 5pt 0; }
        .title { font-size: 10pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; }
        .inverse { background: #000; color: #fff; padding: 4pt; text-align: center; }
        .total td { font-size: 10.5pt; font-weight: bold; padding-top: 3pt; }
        .box { border: 1.2pt solid #000; padding: 5pt; text-align: center; margin-top: 5pt; }
    </style>
</head>
<body>
    @include('pdf.partials.ticket-header')
    @yield('content')
    <div class="sep"></div>
    <div class="center small">{{ $company['footer'] }}@if ($company['slogan'])<br><span class="bold">{{ $company['slogan'] }}</span>@endif</div>
    <div class="center small muted" style="margin-top: 3pt;">Édité le {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
