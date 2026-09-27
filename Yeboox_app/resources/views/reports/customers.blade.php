<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des clients - {{ $establishment->name }}</title>
    <style>
        @page { margin: {{ $template === 'compact' ? '18px' : '28px' }} 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: {{ $template === 'compact' ? '7.5px' : '8.5px' }}; }
        .header { margin-bottom: 16px; padding: {{ $template === 'compact' ? '9px 0' : '15px' }}; }
        .modern .header { color: #fff; border-radius: 9px; background: #1d4ed8; }
        .classic .header { border: 2px solid #27272a; text-align: center; }
        .compact .header { border-bottom: 2px solid #2563eb; }
        .logo { float: right; max-width: 70px; max-height: 44px; }
        h1 { margin: 0 0 3px; font-size: {{ $template === 'compact' ? '15px' : '19px' }}; }
        .meta { margin-top: 4px; opacity: .78; }
        .summary { width: 100%; margin: 0 0 13px; border-collapse: separate; border-spacing: 7px 0; }
        .summary td { padding: 9px 11px; border: 1px solid #dbeafe; border-radius: 7px; background: #eff6ff; }
        .summary strong { display: block; color: #1d4ed8; font-size: 14px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th { padding: {{ $template === 'compact' ? '5px 4px' : '7px 5px' }}; color: #fff; background: {{ $template === 'classic' ? '#27272a' : '#2563eb' }}; font-size: 7px; text-align: left; text-transform: uppercase; }
        .data td { padding: {{ $template === 'compact' ? '4px' : '6px 5px' }}; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        .classic .data td { border: 1px solid #d4d4d8; }
        .data tbody tr:nth-child(even) { background: #f8fafc; }
        .right { text-align: right; } .center { text-align: center; }
        .badge { padding: 2px 5px; border-radius: 8px; color: #1d4ed8; background: #dbeafe; font-size: 6.5px; }
        .muted { color: #71717a; font-size: 7px; }
        .footer { position: fixed; right: 0; bottom: -22px; left: 0; color: #71717a; font-size: 7px; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body class="{{ $template }}">
    <div class="header">
        @if ($logoData)<img class="logo" src="{{ $logoData }}" alt="Logo">@endif
        <h1>Liste des clients</h1>
        <div>{{ $establishment->name }}</div>
        <div class="meta">Édité le {{ now()->format('d/m/Y à H:i') }} - Modèle {{ ucfirst($template) }}</div>
    </div>
    <table class="summary"><tr>
        <td><strong>{{ $customers->count() }}</strong> clients</td>
        <td><strong>{{ number_format($customers->sum('loyalty_points'), 0, ',', '.') }}</strong> points fidélité</td>
        <td><strong>{{ $customers->whereNotNull('loyaltyCard')->count() }}</strong> cartes émises</td>
        <td><strong>{{ number_format($customers->sum('total_purchases'), 0, ',', '.') }} FC</strong> achats cumulés</td>
    </tr></table>
    <table class="data">
        <thead><tr><th>Client</th><th>Contact</th><th>Ville</th><th>Carte</th><th>Niveau</th><th class="right">Points</th><th class="right">Total achats</th><th class="center">Commandes</th><th>Dernier achat</th></tr></thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->full_name }}</strong>@if($customer->company)<br><span class="muted">{{ $customer->company }}</span>@endif</td>
                    <td>{{ $customer->phone ?: '-' }}@if($customer->email)<br><span class="muted">{{ $customer->email }}</span>@endif</td>
                    <td>{{ $customer->city ?: '-' }}</td>
                    <td>@if($customer->loyaltyCard)<span class="badge">{{ strtoupper($customer->loyaltyCard->type) }}</span><br><span class="muted">{{ $customer->loyaltyCard->card_number }}</span>@else - @endif</td>
                    <td>{{ ucfirst($customer->loyalty_level) }}</td>
                    <td class="right">{{ number_format($customer->loyalty_points, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($customer->total_purchases, 0, ',', '.') }} FC</td>
                    <td class="center">{{ $customer->total_orders }}</td>
                    <td>{{ $customer->last_purchase_at?->format('d/m/Y') ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Aucun client ne correspond aux filtres.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">{{ $establishment->document_footer ?: config('app.name') }} - Page <span class="page-number"></span></div>
</body>
</html>
