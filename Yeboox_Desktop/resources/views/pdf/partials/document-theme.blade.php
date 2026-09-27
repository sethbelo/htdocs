{{--
    Shared Dompdf theme. The values come exclusively from DocumentPreferences,
    which guarantees that every exported report uses the model active in Configuration.
--}}
body {
    font-family: "{{ \App\Support\DocumentPreferences::pdfFontFamily($preferences['font_family']) }}", sans-serif;
    font-size: {{ $preferences['font_size'] }}px;
    font-weight: {{ $preferences['font_weight'] }};
}
.header { border-color: {{ $documentTheme['accent'] }}; }
.footer { border-color: {{ $documentTheme['border'] }}; }
.company, h1, .category, .category-name { color: {{ $documentTheme['dark'] }}; }
.context, .period, .note { border-color: {{ $documentTheme['accent'] }}; background: {{ $documentTheme['soft'] }}; }
.metrics td, .summary-table td { background: {{ $documentTheme['soft'] }}; }
.data th, .data-table th { background: {{ $documentTheme['dark'] }}; }
.data td, .data-table td, .summary td { border-color: {{ $documentTheme['border'] }}; }
.data tbody tr:nth-child(even) td, .data-table tbody tr:nth-child(even) td { background: {{ $documentTheme['soft'] }}; }
.data-table tfoot td { border-color: {{ $documentTheme['accent'] }}; background: {{ $documentTheme['soft'] }}; }
.document-frame { border-radius: {{ $documentTheme['radius'] }}; }
.document-model { color: {{ $documentTheme['accent'] }}; font-size: 7px; font-weight: bold; letter-spacing: .35px; text-transform: uppercase; }
.document-intro { margin: 0 0 10px; padding: 9px 11px; border: 1px solid {{ $documentTheme['border'] }}; border-left: 5px solid {{ $documentTheme['accent'] }}; border-radius: {{ $documentTheme['radius'] }}; background: {{ $documentTheme['soft'] }}; }
.document-intro-table, .document-toc { width: 100%; border-collapse: collapse; }
.document-intro-table td { padding: 2px 6px 2px 0; vertical-align: top; }
.document-kicker { color: {{ $documentTheme['accent'] }}; font-size: 7px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }
.document-toc { margin: 5px 0 11px; table-layout: fixed; }
.document-toc td { padding: 5px 7px; border-bottom: 1px solid {{ $documentTheme['border'] }}; }
.document-toc .toc-number { width: 34px; color: {{ $documentTheme['accent'] }}; font-weight: bold; text-align: center; }
.document-toc .toc-title { color: {{ $documentTheme['dark'] }}; font-weight: bold; }
.document-section-title { page-break-after: avoid; }

@switch($documentTheme['header'])
    @case('band')
        .header { padding: 5px 8px; border: 0; background: {{ $documentTheme['dark'] }}; color: white; }
        .header .company, .header .muted, .header .document-model { color: white; }
        @break
    @case('line')
        .header { border-width: 0 0 1px; background: white; }
        .context, .period, .note { border-width: 0 0 1px; background: white; }
        .metrics td, .summary-table td { background: white; border-bottom: 1px solid {{ $documentTheme['border'] }}; }
        @break
    @case('sidebar')
        .header { padding-left: 9px; border-width: 0 0 1px 6px; }
        h1 { padding-left: 8px; border-left: 6px solid {{ $documentTheme['accent'] }}; }
        @break
    @case('editorial')
        .header { border-width: 3px 0 1px; }
        h1, h2 { font-family: "DejaVu Serif", serif; text-transform: none; }
        h1 { letter-spacing: 0; }
        @break
    @case('geometric')
        .header { padding: 5px 9px; border: 0; border-radius: 0 14px; background: {{ $documentTheme['soft'] }}; }
        .context, .period, .note { border-radius: 0 10px; }
        @break
    @case('double')
        .header { border: 3px double {{ $documentTheme['accent'] }}; padding: 4px 7px; }
        .data th, .data-table th { border: 1px solid white; }
        .data td, .data-table td { border: 1px solid {{ $documentTheme['border'] }}; }
        @break
    @case('elegant')
        .header { border-width: 1px 0; }
        h1, h2, .company { font-family: "DejaVu Serif", serif; }
        h1 { text-align: center; text-transform: none; }
        @break
    @case('modern')
        .header { padding: 5px 8px; border: 0; background: {{ $documentTheme['soft'] }}; border-radius: 10px; }
        .context, .period, .note { border-radius: 8px; }
        @break
    @case('framed')
        .document-frame { padding: 9px; border: 2px solid {{ $documentTheme['accent'] }}; }
        .header { border-width: 0 0 2px; }
        @break
    @case('compact')
        body { font-size: {{ max(8, $preferences['font_size'] - 1) }}px; line-height: 1.2; }
        .header { height: 14mm; border-width: 0 0 3px; }
        h1 { margin-bottom: 2px; font-size: 16px; }
        h2 { margin: 7px 0 4px; }
        .context, .period, .note { margin-bottom: 6px; padding: 5px 7px; }
        .metrics td, .summary-table td { padding: 5px; }
        .data td, .data th, .data-table td, .data-table th { padding: 3px 4px; }
        @break
    @case('official')
        .header { padding: 5px 8px; border: 1px solid {{ $documentTheme['accent'] }}; border-top: 5px solid {{ $documentTheme['dark'] }}; }
        h1 { padding-bottom: 5px; border-bottom: 3px solid {{ $documentTheme['accent'] }}; letter-spacing: .8px; }
        .document-intro { border-left-width: 8px; }
        @break
    @case('heritage')
        .header { border-width: 1px 0 4px; }
        h1, h2, .company { font-family: "DejaVu Serif", serif; }
        h1 { text-align: center; text-transform: none; letter-spacing: .2px; }
        .document-intro { border-width: 1px 0; border-radius: 0; }
        @break
    @case('sahara')
        .header { padding: 5px 9px; border: 0; border-radius: 8px 2px; background: {{ $documentTheme['soft'] }}; }
        h1 { color: {{ $documentTheme['dark'] }}; }
        .document-intro, .context, .period, .note { border-radius: 8px 2px; }
        @break
    @case('digital')
        .header { padding: 4px 8px; border: 1px solid {{ $documentTheme['border'] }}; border-bottom: 4px solid {{ $documentTheme['accent'] }}; background: {{ $documentTheme['dark'] }}; color: white; }
        .header .company, .header .muted, .header .document-model { color: white; }
        h1 { font-family: "DejaVu Sans Mono", monospace; letter-spacing: 1px; }
        .document-intro { border-style: dashed; }
        .data th, .data-table th { font-family: "DejaVu Sans Mono", monospace; }
        @break
@endswitch
