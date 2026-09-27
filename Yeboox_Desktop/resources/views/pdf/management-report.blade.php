@php
    $titles = ['sales' => 'Rapport des ventes', 'stock' => 'Rapport des mouvements de stock', 'sessions' => 'Rapport des sessions de caisse'];
    $title = $titles[$reportType] ?? 'Rapport de gestion';
    $sections = match($reportType) {
        'sales' => ['Synthèse des encaissements', 'Registre détaillé des ventes'],
        'stock' => ['Mouvements de stock', 'Alertes de rupture'],
        default => ['Synthèse des sessions', 'Registre des ouvertures et clôtures'],
    };
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 25mm 12mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        .header { position: fixed; top: -20mm; left: 0; right: 0; height: 17mm; border-bottom: 2px solid #6d28d9; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; height: 8mm; padding-top: 5px; border-top: 1px solid #d4d4d8; color: #71717a; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; }
        .logo { width: 92px; max-height: 38px; object-fit: contain; }
        .company { font-size: 15px; font-weight: bold; color: #3b0764; }
        .muted { color: #71717a; }
        .right { text-align: right; }
        .center { text-align: center; }
        h1 { margin: 0 0 5px; color: #3b0764; font-size: 19px; text-transform: uppercase; letter-spacing: .6px; }
        h2 { margin: 12px 0 6px; color: #3f3f46; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; }
        .metrics { margin-bottom: 10px; table-layout: fixed; }
        .metrics td { padding: 8px; border-right: 5px solid white; background: #fafafa; vertical-align: top; }
        .metric-label { color: #71717a; font-size: 7px; text-transform: uppercase; }
        .metric-value { margin-top: 3px; font-size: 13px; font-weight: bold; }
        .data { table-layout: fixed; }
        .data thead { display: table-header-group; }
        .data tr { page-break-inside: avoid; }
        .data th { padding: 6px 5px; background: #312e81; color: white; font-size: 7px; text-align: left; text-transform: uppercase; }
        .data td { padding: 5px; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        .data tbody tr:nth-child(even) td { background: #fafafa; }
        .amount { text-align: right; white-space: nowrap; }
        .status { display: inline-block; padding: 2px 6px; border-radius: 8px; background: #f4f4f5; font-weight: bold; }
        @include('pdf.partials.document-theme')
    </style>
</head>
<body class="document-template-{{ $preferences['template'] }}" data-document-template="{{ $preferences['template'] }}">
    <header class="header"><table><tr><td style="width:110px">@if($logo)<img src="{{ $logo }}" class="logo" alt="Logo">@endif</td><td><div class="company">{{ $company['name'] }}</div><div class="muted">{{ $company['address'] }}@if($company['phone']) · {{ $company['phone'] }}@endif @if($company['email']) · {{ $company['email'] }}@endif</div><div class="muted">RCCM {{ $company['rccm'] ?: '—' }} · NIF/Impôt {{ $company['nif'] ?: '—' }} · TVA {{ $company['tva'] ?: '—' }}</div></td><td class="right" style="width:190px"><strong>DOCUMENT ADMINISTRATIF</strong><br><span class="document-model">Modèle {{ $reportTemplateMeta['label'] }}</span><br><span class="muted">{{ $generatedAt->format('d/m/Y à H:i') }}</span></td></tr></table></header>
    <footer class="footer"><table><tr><td>{{ $company['name'] }} · Document confidentiel · {{ $reportTemplateMeta['label'] }}</td><td class="right">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} · Édité par {{ $generatedBy }}</td></tr></table></footer>

    <main class="document-frame">
        <h1>{{ $title }}</h1>
        <section class="document-intro"><table class="document-intro-table"><tr><td><div class="document-kicker">Dossier de pilotage</div>{{ $reportTemplateMeta['description'] }}</td><td class="right"><div class="document-kicker">Période</div>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td></tr></table></section>
        <div class="document-kicker">Table des matières</div>
        <table class="document-toc" aria-label="Table des matières">@foreach($sections as $section)<tr><td class="toc-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td><td class="toc-title">{{ $section }}</td><td class="right muted">Section {{ $loop->iteration }}</td></tr>@endforeach</table>

        @if($reportType === 'sales')
            @php($total = (float) $sales->sum('total_amount'))
            <h2 class="document-section-title">01 · Synthèse des encaissements</h2>
            <table class="metrics"><tr><td><div class="metric-label">Ventes</div><div class="metric-value">{{ $sales->count() }}</div></td><td><div class="metric-label">Chiffre d’affaires</div><div class="metric-value">{{ \App\Support\DisplayCurrency::format($total, $company['currency']) }}</div></td><td><div class="metric-label">Panier moyen</div><div class="metric-value">{{ \App\Support\DisplayCurrency::format($sales->count() ? $total / $sales->count() : 0, $company['currency']) }}</div></td><td><div class="metric-label">Secteur</div><div class="metric-value">{{ $department ?: 'Tous' }}</div></td></tr></table>
            <h2 class="document-section-title">02 · Registre détaillé des ventes</h2>
            <table class="data"><thead><tr><th>Date</th><th>Référence</th><th>Client</th><th>Secteur</th><th>Paiement</th><th class="right">Montant</th></tr></thead><tbody>@forelse($sales as $sale)<tr><td>{{ $sale->created_at->format('d/m/Y H:i') }}</td><td>#{{ $sale->id }}</td><td>{{ $sale->customer?->name ?? 'Client passant' }}</td><td>{{ ucfirst($sale->department) }}</td><td>{{ ucfirst($sale->payment_method) }}</td><td class="amount">{{ \App\Support\DisplayCurrency::format($sale->total_amount, $company['currency']) }}</td></tr>@empty<tr><td colspan="6" class="center muted">Aucune vente sur cette période.</td></tr>@endforelse</tbody></table>
        @elseif($reportType === 'stock')
            <h2 class="document-section-title">01 · Mouvements de stock</h2>
            <table class="data"><thead><tr><th>Date</th><th>Produit</th><th>Type</th><th class="right">Quantité</th><th>Motif</th><th>Utilisateur</th></tr></thead><tbody>@forelse($movements as $movement)<tr><td>{{ $movement->created_at->format('d/m/Y H:i') }}</td><td>{{ $movement->product?->name ?? 'Article supprimé' }}</td><td>{{ $movement->type === 'in' ? 'Entrée' : 'Sortie' }}</td><td class="amount">{{ number_format($movement->quantity, 3, ',', ' ') }}</td><td>{{ $movement->description ?: '—' }}</td><td>{{ $movement->user?->name ?? 'Système' }}</td></tr>@empty<tr><td colspan="6" class="center muted">Aucun mouvement sur cette période.</td></tr>@endforelse</tbody></table>
            <h2 class="document-section-title">02 · Alertes de rupture</h2>
            <table class="data"><thead><tr><th>SKU</th><th>Article</th><th>Catégorie</th><th class="right">Stock</th><th class="right">Seuil</th><th>État</th></tr></thead><tbody>@forelse($lowStockProducts as $product)<tr><td>{{ $product->sku ?: '—' }}</td><td>{{ $product->name }}</td><td>{{ $product->category ?: 'Sans catégorie' }}</td><td class="amount">{{ number_format($product->stock_qty, 3, ',', ' ') }}</td><td class="amount">{{ number_format($product->alert_qty, 3, ',', ' ') }}</td><td><span class="status">{{ $product->stock_qty <= 0 ? 'Rupture' : 'Alerte' }}</span></td></tr>@empty<tr><td colspan="6" class="center muted">Aucune alerte active.</td></tr>@endforelse</tbody></table>
        @else
            @php($sessionRevenue = $sessions->sum(fn($session) => $session->sales->sum('total_amount')))
            <h2 class="document-section-title">01 · Synthèse des sessions</h2>
            <table class="metrics"><tr><td><div class="metric-label">Sessions</div><div class="metric-value">{{ $sessions->count() }}</div></td><td><div class="metric-label">Ouvertes</div><div class="metric-value">{{ $sessions->where('status', 'open')->count() }}</div></td><td><div class="metric-label">Clôturées</div><div class="metric-value">{{ $sessions->where('status', 'closed')->count() }}</div></td><td><div class="metric-label">Encaissements</div><div class="metric-value">{{ \App\Support\DisplayCurrency::format($sessionRevenue, $company['currency']) }}</div></td></tr></table>
            <h2 class="document-section-title">02 · Registre des ouvertures et clôtures</h2>
            <table class="data"><thead><tr><th>Ouverture</th><th>Fermeture</th><th>Caissier</th><th>Statut</th><th class="right">Ventes</th><th class="right">Montant</th></tr></thead><tbody>@forelse($sessions as $session)<tr><td>{{ $session->opened_at?->format('d/m/Y H:i') }}</td><td>{{ $session->closed_at?->format('d/m/Y H:i') ?? 'En cours' }}</td><td>{{ $session->user?->name ?? 'Système' }}</td><td>{{ ucfirst($session->status) }}</td><td class="amount">{{ $session->sales->count() }}</td><td class="amount">{{ \App\Support\DisplayCurrency::format($session->sales->sum('total_amount'), $company['currency']) }}</td></tr>@empty<tr><td colspan="6" class="center muted">Aucune session sur cette période.</td></tr>@endforelse</tbody></table>
        @endif
    </main>
</body>
</html>
