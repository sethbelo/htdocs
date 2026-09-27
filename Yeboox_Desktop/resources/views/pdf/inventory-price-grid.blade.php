<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Grille tarifaire · {{ $company['name'] }}</title>
    <style>
        @page { margin: 28mm 12mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #27272a; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
        .document-header { position: fixed; top: -23mm; left: 0; right: 0; height: 19mm; padding: 3mm 0 2mm; border-bottom: 3px solid {{ $priceGridTheme['accent'] }}; background: {{ $priceGridTheme['soft'] }}; color: {{ $priceGridTheme['dark'] }}; }
        .document-footer { position: fixed; bottom: -12mm; left: 0; right: 0; height: 8mm; padding-top: 5px; border-top: 1px solid {{ $priceGridTheme['border'] }}; color: #71717a; font-size: 7px; }
        .header-table, .footer-table, .price-table, .card-grid { width: 100%; border-collapse: collapse; }
        .logo { width: 92px; max-height: 42px; object-fit: contain; }
        .company-name { font-size: 14px; font-weight: bold; color: {{ $priceGridTheme['dark'] }}; }
        .muted { color: #71717a; }
        .right { text-align: right; }
        .title-block { margin-bottom: 11px; padding: 12px 14px; border: 1px solid {{ $priceGridTheme['border'] }}; border-left: 7px solid {{ $priceGridTheme['accent'] }}; background: {{ $priceGridTheme['soft'] }}; }
        .kicker { color: {{ $priceGridTheme['accent'] }}; font-size: 7px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; }
        h1 { margin: 2px 0 1px; color: {{ $priceGridTheme['dark'] }}; font-size: 22px; text-transform: uppercase; letter-spacing: .5px; }
        .category { margin: 0 0 14px; }
        .category-heading { padding: 5px 8px; border-left: 5px solid {{ $priceGridTheme['accent'] }}; background: {{ $priceGridTheme['soft'] }}; color: {{ $priceGridTheme['dark'] }}; font-size: 11px; text-align: left; text-transform: uppercase; letter-spacing: .45px; }
        .price-table th.category-heading { background: {{ $priceGridTheme['soft'] }}; color: {{ $priceGridTheme['dark'] }}; font-size: 11px; }
        .category-count { float: right; color: {{ $priceGridTheme['accent'] }}; font-size: 7px; }
        .price-table thead { display: table-header-group; }
        .price-table tr { page-break-inside: avoid; }
        .price-table th { padding: 5px 7px; background: {{ $priceGridTheme['dark'] }}; color: white; font-size: 7px; text-align: left; text-transform: uppercase; }
        .price-table td { padding: 6px 7px; border-bottom: 1px solid {{ $priceGridTheme['border'] }}; vertical-align: middle; }
        .price-table tr:nth-child(even) td { background: {{ $priceGridTheme['soft'] }}; }
        .item-name { color: {{ $priceGridTheme['dark'] }}; font-weight: bold; }
        .item-meta { margin-top: 1px; color: #71717a; font-size: 7px; }
        .price { color: {{ $priceGridTheme['accent'] }}; font-size: 10px; font-weight: bold; white-space: nowrap; }
        .tax { color: #71717a; font-size: 6px; font-weight: normal; text-transform: uppercase; }
        .card-grid { table-layout: fixed; }
        .card-grid td { padding: 3px; vertical-align: top; }
        .item-card { min-height: 45px; padding: 8px 9px; border: 1px solid {{ $priceGridTheme['border'] }}; border-radius: {{ $priceGridTheme['shape'] === 'rounded' ? '9px' : ($priceGridTheme['shape'] === 'organic' ? '12px 2px 12px 2px' : ($priceGridTheme['shape'] === 'pill' ? '18px' : '2px')) }}; background: white; page-break-inside: avoid; }
        .item-card .price { margin-top: 5px; }
        .layout-menu .price-table td { border-bottom-style: dashed; background: white !important; }
        .layout-list .price-table thead tr:last-child { display: none; }
        .layout-list .price-table td { padding-top: 8px; padding-bottom: 8px; }
        .template-prestige .document-header { background: #1c1917; color: #fffbeb; }
        .template-prestige .company-name { color: #fef3c7; }
        .template-prestige .title-block { border-width: 2px 0; border-left: 0; background: white; text-align: center; }
        .template-minimal .document-header, .template-minimal .title-block, .template-minimal .category-heading { background: white; }
        .template-minimal .title-block { padding-left: 0; border-width: 0 0 1px; border-left: 0; }
        .template-corporate .title-block { border-left-width: 12px; }
        .template-bistro .category-heading { border-radius: 12px; }
        .template-botanique .category-heading { border-radius: 14px 2px 14px 2px; }
        .template-retro .document-header { border-bottom-style: dashed; border-bottom-width: 4px; }
        .template-retro .title-block { border: 3px double {{ $priceGridTheme['accent'] }}; text-align: center; }
        .template-digital .document-header { background: {{ $priceGridTheme['dark'] }}; color: white; }
        .template-digital .company-name { color: white; }
        .template-digital .category-heading { border-left: 0; border-bottom: 2px solid {{ $priceGridTheme['accent'] }}; background: white; }
        .template-catalogue .category-heading { border-left: 0; border-radius: 4px; background: {{ $priceGridTheme['dark'] }}; color: white; }
        .template-mosaique .item-card { border-top: 4px solid {{ $priceGridTheme['accent'] }}; }
        .template-atelier .title-block, .template-atelier .category-heading { border-radius: 0; background: #f4f4f5; }
        .template-celebration .title-block { border-radius: 16px; }
        .empty { padding: 45px; border: 1px dashed {{ $priceGridTheme['border'] }}; text-align: center; color: #71717a; }
    </style>
</head>
<body class="template-{{ $priceGridTemplate }} layout-{{ $priceGridTheme['layout'] }}">
    <header class="document-header">
        <table class="header-table"><tr>
            <td style="width:110px">@if($logo)<img src="{{ $logo }}" class="logo" alt="Logo">@endif</td>
            <td><div class="company-name">{{ $company['name'] }}</div><div class="muted">{{ collect([$company['address'], $company['phone'], $company['email']])->filter()->join(' · ') }}</div></td>
            <td class="right" style="width:165px"><strong>GRILLE TARIFAIRE</strong><br><span class="muted">Modèle {{ $priceGridTheme['label'] }} · {{ $currency }}</span></td>
        </tr></table>
    </header>

    <footer class="document-footer">
        <table class="footer-table"><tr><td>{{ $company['name'] }} · Tarifs édités le {{ $generatedAt->format('d/m/Y') }}</td><td class="right">Devise {{ \App\Support\DisplayCurrency::baseCode() }} · {{ $generatedBy }}</td></tr></table>
    </footer>

    <main>
        <section class="title-block">
            <div class="kicker">Catalogue officiel · {{ $priceGridTheme['description'] }}</div>
            <h1>Nos tarifs</h1>
            <div class="muted">
                Prix classés par catégorie
                @if($selectedCategory) · Sélection : {{ $selectedCategory }} @endif
                · Montants exprimés en {{ $currency }}
            </div>
        </section>

        @forelse($groups as $categoryName => $products)
            <section class="category">
                @if(in_array($priceGridTheme['layout'], ['cards', 'columns', 'mosaic'], true))
                    @php $columnCount = $priceGridTheme['layout'] === 'mosaic' ? 3 : 2; @endphp
                    <table class="card-grid">
                        <thead><tr><th colspan="{{ $columnCount }}" class="category-heading">{{ $categoryName }} <span class="category-count">{{ $products->count() }} ARTICLE{{ $products->count() > 1 ? 'S' : '' }}</span></th></tr></thead>
                        <tbody>
                            @foreach($products->chunk($columnCount) as $row)
                                <tr>
                                    @foreach($row as $product)
                                        <td style="width:{{ 100 / $columnCount }}%"><div class="item-card">
                                            <div class="item-name">{{ $product->name }}</div>
                                            <div class="item-meta">@if($showSku){{ $product->sku ?: 'Sans SKU' }} · @endif{{ ['product' => 'Bien', 'service' => 'Service', 'combo' => 'Combo'][$product->type] ?? ucfirst($product->type) }}@if($product->type === 'product' && $product->unitOfMeasure) · / {{ $product->unitOfMeasure->symbol }}@endif</div>
                                            <div class="price">{{ \App\Support\DisplayCurrency::format($product->grossUnitPrice(), $currency) }} @if($showTax)<span class="tax">TTC · TVA {{ number_format((float) $product->sales_tax_rate, 0) }}%</span>@endif</div>
                                        </div></td>
                                    @endforeach
                                    @for($empty = $row->count(); $empty < $columnCount; $empty++)<td></td>@endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="price-table">
                        <thead>
                            <tr><th colspan="{{ $showSku ? 4 : 3 }}" class="category-heading">{{ $categoryName }} <span class="category-count">{{ $products->count() }} ARTICLE{{ $products->count() > 1 ? 'S' : '' }}</span></th></tr>
                            <tr><th>Article</th>@if($showSku)<th style="width:19%">Référence</th>@endif<th style="width:16%">Type</th><th class="right" style="width:25%">Prix</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr><td><div class="item-name">{{ $product->name }}</div>@if($product->type === 'product' && $product->unitOfMeasure)<div class="item-meta">Unité : {{ $product->unitOfMeasure->name }}</div>@endif</td>@if($showSku)<td>{{ $product->sku ?: '—' }}</td>@endif<td>{{ ['product' => 'Bien', 'service' => 'Service', 'combo' => 'Combo'][$product->type] ?? ucfirst($product->type) }}</td><td class="right"><span class="price">{{ \App\Support\DisplayCurrency::format($product->grossUnitPrice(), $currency) }}</span>@if($showTax)<br><span class="tax">TTC · TVA {{ number_format((float) $product->sales_tax_rate, 0) }}%</span>@endif</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @empty
            <div class="empty"><strong>Aucun article actif à publier.</strong><br>Choisissez une autre catégorie ou activez des articles dans l’inventaire.</div>
        @endforelse
    </main>
</body>
</html>
