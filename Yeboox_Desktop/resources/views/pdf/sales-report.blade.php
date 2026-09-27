<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport détaillé des ventes</title>
    <style>
        @page { margin: 25mm 12mm 18mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        .header { position: fixed; top: -20mm; left: 0; right: 0; height: 17mm; border-bottom: 2px solid #6d28d9; }
        .header-table, .summary-table, .data-table { width: 100%; border-collapse: collapse; }
        .logo { width: 92px; max-height: 38px; object-fit: contain; }
        .company { font-size: 15px; font-weight: bold; color: #3b0764; }
        .muted { color: #71717a; }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; height: 8mm; padding-top: 5px; border-top: 1px solid #d4d4d8; color: #71717a; font-size: 7px; }
        h1 { margin: 0 0 3px; color: #3b0764; font-size: 19px; text-transform: uppercase; letter-spacing: .6px; }
        h2 { margin: 13px 0 6px; color: #3f3f46; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; }
        .period { margin-bottom: 12px; padding: 8px 10px; border-left: 4px solid #7c3aed; background: #f5f3ff; }
        .summary-table { margin-bottom: 10px; table-layout: fixed; }
        .summary-table td { padding: 8px; border-right: 5px solid white; background: #fafafa; vertical-align: top; }
        .summary-table td:last-child { border-right: 0; }
        .metric-label { color: #71717a; font-size: 7px; text-transform: uppercase; letter-spacing: .3px; }
        .metric-value { margin-top: 3px; color: #27272a; font-size: 13px; font-weight: bold; }
        .metric-positive { color: #047857; }
        .data-table { table-layout: fixed; }
        .data-table thead { display: table-header-group; }
        .data-table tr { page-break-inside: avoid; }
        .data-table th { padding: 6px 5px; background: #312e81; color: white; font-size: 7px; text-align: left; text-transform: uppercase; }
        .data-table td { padding: 4px 5px; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        .data-table tbody tr:nth-child(even) td { background: #fafafa; }
        .data-table tfoot td { padding: 7px 5px; border-top: 2px solid #312e81; border-bottom: 0; background: #f5f3ff; font-weight: bold; }
        .amount { text-align: right; white-space: nowrap; }
        .category-name { font-weight: bold; color: #4c1d95; }
        .note { margin-top: 10px; padding: 7px 9px; border: 1px solid #e4e4e7; background: #fafafa; color: #52525b; font-size: 7px; }
        @include('pdf.partials.document-theme')
    </style>
</head>
<body class="document-template-{{ $preferences['template'] }}" data-document-template="{{ $preferences['template'] }}">
    <header class="header"><table class="header-table"><tr><td style="width:110px">@if($logo)<img src="{{ $logo }}" class="logo" alt="Logo">@endif</td><td><div class="company">{{ $company['name'] }}</div><div class="muted">{{ $company['address'] }}@if($company['phone']) · {{ $company['phone'] }}@endif @if($company['email']) · {{ $company['email'] }}@endif</div><div class="muted">@if($company['rccm'])RCCM {{ $company['rccm'] }} · @endif @if($company['nif'])NIF {{ $company['nif'] }} · @endif @if($company['tva'])TVA {{ $company['tva'] }}@endif</div></td><td class="right" style="width:190px"><strong>DOCUMENT DE GESTION</strong><br><span class="document-model">Modèle {{ $reportTemplateMeta['label'] }}</span><br><span class="muted">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</span></td></tr></table></header>
    <footer class="footer"><table class="header-table"><tr><td>Rapport confidentiel · {{ $company['name'] }} · Modèle {{ $reportTemplateMeta['label'] }}</td><td class="right">Période {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} · Édité par {{ $generatedBy }}</td></tr></table></footer>

    <main class="document-frame">
    <h1>Rapport détaillé des ventes</h1>
    <section class="document-intro">
        <table class="document-intro-table"><tr><td><div class="document-kicker">Dossier de gestion</div><strong>{{ $reportTemplateMeta['label'] }}</strong> · {{ $reportTemplateMeta['description'] }}</td><td class="right"><div class="document-kicker">Période de référence</div>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td></tr></table>
    </section>
    <div class="document-kicker">Table des matières</div>
    <table class="document-toc" aria-label="Table des matières"><tr><td class="toc-number">01</td><td class="toc-title">Synthèse financière et indicateurs</td><td class="right muted">Vue générale</td></tr><tr><td class="toc-number">02</td><td class="toc-title">Chiffre d’affaires par catégorie</td><td class="right muted">Analyse</td></tr><tr><td class="toc-number">03</td><td class="toc-title">Détail des produits vendus</td><td class="right muted">Registre</td></tr></table>
    <div class="period">
        <strong>Période analysée :</strong> du {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} au {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        @if($selectedCategory)
            · <strong>Catégorie :</strong> {{ $selectedCategory }}
        @else
            · Toutes les catégories
        @endif
    </div>

    <table class="summary-table"><tr><td><div class="metric-label">Chiffre d’affaires TTC</div><div class="metric-value">{{ number_format($totals['revenue'], 2, ',', ' ') }} {{ $company['currency'] }}</div></td><td><div class="metric-label">Chiffre d’affaires HT</div><div class="metric-value">{{ number_format($totals['net_before_tax'], 2, ',', ' ') }} {{ $company['currency'] }}</div></td><td><div class="metric-label">TVA collectée</div><div class="metric-value">{{ number_format($totals['tax'], 2, ',', ' ') }} {{ $company['currency'] }}</div></td><td><div class="metric-label">Coût des ventes</div><div class="metric-value">{{ number_format($totals['cost'], 2, ',', ' ') }} {{ $company['currency'] }}</div></td><td><div class="metric-label">Bénéfice brut HT</div><div class="metric-value metric-positive">{{ number_format($totals['profit'], 2, ',', ' ') }} {{ $company['currency'] }}</div></td><td><div class="metric-label">Marge brute</div><div class="metric-value">{{ number_format($totals['margin'], 1, ',', ' ') }} %</div></td></tr></table>

    <h2 class="document-section-title">02 · Chiffre d’affaires par catégorie</h2>
    <table class="data-table"><thead><tr><th style="width:24%">Catégorie</th><th class="right">Ventes</th><th class="right">Quantité</th><th class="right">CA HT</th><th class="right">TVA</th><th class="right">CA TTC</th><th class="right">Coût</th><th class="right">Bénéfice</th><th class="right">Marge</th><th class="right">Part CA</th></tr></thead><tbody>@forelse($categories as $categoryRow)<tr><td class="category-name">{{ $categoryRow['name'] }}</td><td class="amount">{{ $categoryRow['orders'] }}</td><td class="amount">{{ number_format($categoryRow['quantity'], 3, ',', ' ') }}</td><td class="amount">{{ number_format($categoryRow['net_before_tax'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($categoryRow['tax'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($categoryRow['revenue'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($categoryRow['cost'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($categoryRow['profit'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($categoryRow['margin'], 1, ',', ' ') }} %</td><td class="amount">{{ number_format($categoryRow['share'], 1, ',', ' ') }} %</td></tr>@empty<tr><td colspan="10" class="center muted">Aucune vente sur cette période.</td></tr>@endforelse</tbody><tfoot><tr><td>TOTAL GÉNÉRAL</td><td class="amount">{{ $totals['orders'] }}</td><td class="amount">{{ number_format($totals['quantity'], 3, ',', ' ') }}</td><td class="amount">{{ number_format($totals['net_before_tax'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($totals['tax'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($totals['revenue'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($totals['cost'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($totals['profit'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($totals['margin'], 1, ',', ' ') }} %</td><td class="amount">100 %</td></tr></tfoot></table>

    <div class="note"><strong>Méthode :</strong> le bénéfice brut correspond au chiffre d’affaires hors TVA après remises, diminué du coût d’achat enregistré sur les produits. Les remises sont ventilées proportionnellement entre les catégories. Ce document est un état de gestion et ne remplace pas les écritures du journal OHADA.</div>

    <h2 class="document-section-title">03 · Détail des produits vendus</h2>
    <table class="data-table"><thead><tr><th style="width:7%">Vente</th><th style="width:10%">Date</th><th style="width:12%">Catégorie</th><th style="width:18%">Produit</th><th class="right" style="width:6%">Qté</th><th class="right">HT net</th><th class="right">TVA</th><th class="right">TTC</th><th class="right">Coût</th><th class="right">Bénéfice</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row['reference'] }}</td><td>{{ $row['date']->format('d/m/Y H:i') }}</td><td>{{ $row['category'] }}</td><td><strong>{{ $row['product'] }}</strong><br><span class="muted">{{ $row['customer'] }}</span></td><td class="amount">{{ number_format($row['quantity'], 3, ',', ' ') }}</td><td class="amount">{{ number_format($row['net_before_tax'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($row['tax'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($row['revenue'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($row['cost'], 2, ',', ' ') }}</td><td class="amount">{{ number_format($row['profit'], 2, ',', ' ') }}</td></tr>@empty<tr><td colspan="10" class="center muted">Aucune ligne de vente disponible.</td></tr>@endforelse</tbody></table>
    </main>
</body>
</html>
