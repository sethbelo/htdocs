<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>État des stocks</title>
    <style>
        @page { margin: 24mm 12mm 17mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        .header { position: fixed; top: -19mm; left: 0; right: 0; height: 16mm; border-bottom: 2px solid #6d28d9; font-size: 7px; line-height: 1.12; }
        .header table { line-height: 1.12; }
        .header td { vertical-align: middle; }
        .footer { position: fixed; bottom: -11mm; left: 0; right: 0; height: 7mm; padding-top: 5px; border-top: 1px solid #d4d4d8; color: #71717a; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; }
        .logo { width: 90px; max-height: 36px; object-fit: contain; }
        .company { color: #3b0764; font-size: 12px; font-weight: bold; }
        .muted { color: #71717a; }
        .right { text-align: right; }
        h1 { margin: 0 0 3px; color: #3b0764; font-size: 19px; text-transform: uppercase; letter-spacing: .6px; }
        h2 { margin: 12px 0 6px; color: #3f3f46; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
        .context { margin-bottom: 10px; padding: 8px 10px; border-left: 4px solid #7c3aed; background: #f5f3ff; }
        .metrics { margin-bottom: 10px; table-layout: fixed; }
        .metrics td { padding: 8px; border-right: 5px solid white; background: #fafafa; vertical-align: top; }
        .metrics td:last-child { border-right: 0; }
        .metric-label { color: #71717a; font-size: 7px; text-transform: uppercase; letter-spacing: .3px; }
        .metric-value { margin-top: 3px; color: #27272a; font-size: 13px; font-weight: bold; }
        .data { table-layout: fixed; }
        .data thead { display: table-header-group; }
        .data tr { page-break-inside: avoid; }
        .data th { padding: 6px 5px; background: #312e81; color: white; font-size: 7px; text-align: left; text-transform: uppercase; }
        .data td { padding: 4px 5px; border-bottom: 1px solid #e4e4e7; vertical-align: middle; }
        .data tbody tr:nth-child(even) td { background: #fafafa; }
        .amount { text-align: right; white-space: nowrap; }
        .status { display: inline-block; min-width: 45px; padding: 2px 5px; border-radius: 8px; text-align: center; font-size: 7px; font-weight: bold; }
        .ok { background: #ecfdf5; color: #047857; }
        .alert { background: #fffbeb; color: #b45309; }
        .out { background: #fff1f2; color: #be123c; }
        .category { font-weight: bold; color: #4c1d95; }
        .summary td { padding: 5px; border-bottom: 1px solid #e4e4e7; }
        .movement-in { color: #047857; font-weight: bold; }
        .movement-out { color: #be123c; font-weight: bold; }
        .signatures { margin-top: 18px; page-break-inside: avoid; table-layout: fixed; }
        .signatures td { width: 50%; height: 72px; padding: 8px 14px; border: 1px solid #d4d4d8; vertical-align: top; }
        .signature-line { margin-top: 38px; border-top: 1px solid #71717a; padding-top: 4px; color: #71717a; }
        .stamp-zone { margin-left: 18px; border: 1px dashed #a1a1aa; border-radius: 50%; width: 58px; height: 58px; line-height: 58px; text-align: center; color: #a1a1aa; }
        @include('pdf.partials.document-theme')
    </style>
</head>
<body class="document-template-{{ $preferences['template'] }}" data-document-template="{{ $preferences['template'] }}">
    <header class="header"><table><tr><td style="width:110px">@if($logo)<img src="{{ $logo }}" class="logo" alt="Logo">@endif</td><td><div class="company">{{ $company['name'] }}</div>@if(!empty($establishment['name']))<div><strong>Établissement : {{ $establishment['name'] }}</strong>@if($establishment['code']) · {{ $establishment['code'] }}@endif</div>@endif<div class="muted">{{ $establishment['address'] ?: $company['address'] }}@if($establishment['phone'] ?: $company['phone']) · {{ $establishment['phone'] ?: $company['phone'] }}@endif @if($establishment['email'] ?: $company['email']) · {{ $establishment['email'] ?: $company['email'] }}@endif</div><div class="muted">@if($company['rccm'])RCCM {{ $company['rccm'] }} · @endif @if($company['nif'])NIF/Impôt {{ $company['nif'] }} · @endif @if($company['tva'])TVA {{ $company['tva'] }}@endif</div></td><td class="right" style="width:190px"><strong>DOCUMENT DE GESTION</strong><br><span class="document-model">Modèle {{ $reportTemplateMeta['label'] }}</span><br><span class="muted">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</span></td></tr></table></header>
    <footer class="footer"><table><tr><td>État confidentiel · {{ $company['name'] }}@if(!empty($establishment['name'])) / {{ $establishment['name'] }}@endif · Modèle {{ $reportTemplateMeta['label'] }}</td><td class="right">Devise comptable {{ $currency }} · Édité par {{ $generatedBy }}</td></tr></table></footer>

    <main class="document-frame">
    <h1>État des stocks</h1>
    <section class="document-intro">
        <table class="document-intro-table"><tr><td><div class="document-kicker">Document d’inventaire</div><strong>{{ $reportTemplateMeta['label'] }}</strong> · {{ $reportTemplateMeta['description'] }}</td><td class="right"><div class="document-kicker">Date d’édition</div>{{ $generatedAt->format('d/m/Y à H:i') }}</td></tr></table>
    </section>
    <div class="document-kicker">Table des matières</div>
    <table class="document-toc" aria-label="Table des matières"><tr><td class="toc-number">01</td><td class="toc-title">Indicateurs et périmètre du stock</td><td class="right muted">Synthèse</td></tr><tr><td class="toc-number">02</td><td class="toc-title">Détail des articles</td><td class="right muted">Registre</td></tr><tr><td class="toc-number">03</td><td class="toc-title">Entrées, sorties et réapprovisionnements</td><td class="right muted">Mouvements</td></tr><tr><td class="toc-number">04</td><td class="toc-title">Synthèse par catégorie et validation</td><td class="right muted">Analyse</td></tr></table>
    <div class="context"><strong>Période :</strong> {{ $period['start']->format('d/m/Y') }} au {{ $period['end']->format('d/m/Y') }} · <strong>Périmètre :</strong> {{ $filters['category'] ?? 'Toutes les catégories' }} · <strong>Statut :</strong> {{ ['in_stock' => 'Disponible', 'alert' => 'Alerte', 'out' => 'Rupture'][$filters['status'] ?? ''] ?? 'Tous' }}@if(!empty($filters['search'])) · <strong>Recherche :</strong> {{ $filters['search'] }}@endif</div>

    <table class="metrics"><tr><td><div class="metric-label">Références</div><div class="metric-value">{{ number_format($totals['articles']) }}</div></td><td><div class="metric-label">Quantité totale</div><div class="metric-value">{{ number_format($totals['quantity'], 3, ',', ' ') }}</div></td><td><div class="metric-label">Valeur du stock</div><div class="metric-value">{{ \App\Support\DisplayCurrency::format($totals['value'], $currency) }}</div></td><td><div class="metric-label">Sous alerte</div><div class="metric-value">{{ number_format($totals['alerts']) }}</div></td><td><div class="metric-label">En rupture</div><div class="metric-value" style="color:#be123c">{{ number_format($totals['out']) }}</div></td></tr></table>
    <table class="metrics"><tr><td><div class="metric-label">Mouvements</div><div class="metric-value">{{ number_format($movementTotals['count']) }}</div></td><td><div class="metric-label">Entrées</div><div class="metric-value movement-in">+{{ number_format($movementTotals['entries_quantity'], 3, ',', ' ') }}</div></td><td><div class="metric-label">Sorties</div><div class="metric-value movement-out">−{{ number_format($movementTotals['exits_quantity'], 3, ',', ' ') }}</div></td><td><div class="metric-label">Réapprovisionné</div><div class="metric-value">{{ number_format($movementTotals['supplies_quantity'], 3, ',', ' ') }}</div></td><td><div class="metric-label">Valeur appro.</div><div class="metric-value">{{ \App\Support\DisplayCurrency::format($movementTotals['supplies_value'], $currency) }}</div></td></tr></table>

    <h2 class="document-section-title">02 · Détail des articles</h2>
    <table class="data"><thead><tr><th style="width:10%">SKU</th><th style="width:22%">Article</th><th style="width:14%">Catégorie</th><th style="width:9%">Unité</th><th class="right">Stock</th><th class="right">Seuil</th><th class="right">Coût unit.</th><th class="right">Valeur</th><th class="right" style="width:8%">Statut</th></tr></thead><tbody>@forelse($products as $product)@php
        $isOut = (float) $product->stock_qty <= 0;
        $isAlert = ! $isOut && (float) $product->stock_qty <= (float) $product->alert_qty;
    @endphp<tr><td>{{ $product->sku ?: '—' }}</td><td><strong>{{ $product->name }}</strong></td><td class="category">{{ $product->category ?: 'Sans catégorie' }}</td><td>{{ $product->unitOfMeasure?->symbol ?: 'unité' }}</td><td class="amount">{{ number_format($product->stock_qty, 3, ',', ' ') }}</td><td class="amount">{{ number_format($product->alert_qty, 3, ',', ' ') }}</td><td class="amount">{{ \App\Support\DisplayCurrency::format($product->cost, $currency) }}</td><td class="amount"><strong>{{ \App\Support\DisplayCurrency::format((float) $product->stock_qty * (float) $product->cost, $currency) }}</strong></td><td class="right"><span class="status {{ $isOut ? 'out' : ($isAlert ? 'alert' : 'ok') }}">{{ $isOut ? 'Rupture' : ($isAlert ? 'Alerte' : 'Disponible') }}</span></td></tr>@empty<tr><td colspan="9" style="padding:18px;text-align:center" class="muted">Aucun article ne correspond aux filtres.</td></tr>@endforelse</tbody></table>

    <h2 class="document-section-title">03 · Entrées, sorties et réapprovisionnements</h2>
    <table class="data"><thead><tr><th style="width:12%">Date</th><th style="width:19%">Article</th><th style="width:13%">Référence</th><th style="width:13%">Opération</th><th class="right" style="width:9%">Quantité</th><th class="right" style="width:12%">Coût unit.</th><th style="width:13%">Agent</th><th>Observation</th></tr></thead><tbody>@forelse($movements as $movement)@php
        $reasonLabel = ['supply' => 'Réappro.', 'adjustment' => 'Ajustement', 'initial_stock' => 'Stock initial', 'physical_inventory' => 'Inventaire', 'sale' => 'Vente', 'offline_sync' => 'Vente synchronisée'][$movement->reason] ?? ucfirst(str_replace('_', ' ', $movement->reason));
        $unitCost = (float) $movement->unit_cost ?: (float) ($movement->product?->cost ?? 0);
    @endphp<tr><td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td><td><strong>{{ $movement->product?->name ?? 'Article supprimé' }}</strong><br><span class="muted">{{ $movement->product?->sku ?: '—' }}</span></td><td>{{ $movement->reference ?: '—' }}</td><td>{{ $reasonLabel }}</td><td class="amount {{ $movement->type === 'in' ? 'movement-in' : 'movement-out' }}">{{ $movement->type === 'in' ? '+' : '−' }}{{ number_format($movement->quantity, 3, ',', ' ') }}</td><td class="amount">{{ \App\Support\DisplayCurrency::format($unitCost, $currency) }}</td><td>{{ $movement->user?->name ?: 'Système' }}</td><td>{{ $movement->description ?: '—' }}</td></tr>@empty<tr><td colspan="8" style="padding:18px;text-align:center" class="muted">Aucun mouvement sur la période sélectionnée.</td></tr>@endforelse</tbody></table>

    <h2 class="document-section-title">04 · Synthèse par catégorie et validation</h2>
    <table class="summary"><thead><tr><th style="text-align:left">Catégorie</th><th class="right">Références</th><th class="right">Quantité</th><th class="right">Valeur</th></tr></thead><tbody>@foreach($categories as $category)<tr><td class="category">{{ $category['name'] }}</td><td class="amount">{{ $category['articles'] }}</td><td class="amount">{{ number_format($category['quantity'], 3, ',', ' ') }}</td><td class="amount">{{ \App\Support\DisplayCurrency::format($category['value'], $currency) }}</td></tr>@endforeach</tbody></table>
    <table class="signatures"><tr><td><strong>Établi et imprimé par</strong><br>{{ $generatedBy }}<div class="signature-line">Date et signature</div></td><td><table><tr><td style="border:0;padding:0"><strong>Validation de l’établissement</strong><div class="signature-line">Nom, fonction et signature</div></td><td style="border:0;padding:0;width:78px"><div class="stamp-zone">CACHET</div></td></tr></table></td></tr></table>
    </main>
</body>
</html>
