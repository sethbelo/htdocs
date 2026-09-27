{{--
    Charte documentaire SURNATUREL PRINT (Dompdf : CSS 2.1, tableaux plutôt que flex/grid).
    Encre #16161D · Magenta #E6007E · Cyan #00A6E6 · Jaune #FFE500.
--}}
@php
    $a5 = ($format ?? 'a4') === 'a5';
    $landscape = ($format ?? 'a4') === 'landscape';
@endphp
<style>
    @page { margin: {{ $a5 ? '88pt 26pt 70pt 26pt' : '104pt 34pt 78pt 34pt' }}; }
    * { box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: {{ $a5 ? '8pt' : '8.8pt' }}; color: #1d1d27; line-height: 1.42; margin: 0; }
    h1, h2, h3 { margin: 0; color: #16161D; }
    table { width: 100%; border-collapse: collapse; }
    p { margin: 4pt 0; }
    .muted { color: #6b6b80; }
    .small { font-size: {{ $a5 ? '6.8pt' : '7.4pt' }}; }
    .right { text-align: right; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .nowrap { white-space: nowrap; }
    .ink { color: #16161D; }
    .magenta { color: #E6007E; }
    .cyan { color: #0088C0; }
    .upper { text-transform: uppercase; letter-spacing: .6pt; }
    .mono { font-family: Courier, monospace; }

    /* En-tête et pied de page fixes (répétés sur chaque page) */
    .pdf-header { position: fixed; top: {{ $a5 ? '-72pt' : '-86pt' }}; left: 0; right: 0; }
    .pdf-footer { position: fixed; bottom: {{ $a5 ? '-58pt' : '-64pt' }}; left: 0; right: 0; font-size: {{ $a5 ? '6.3pt' : '6.9pt' }}; color: #6b6b80; line-height: 1.35; }
    .brand-rule td { height: 3.2pt; padding: 0; }
    .pagenum:before { content: counter(page); }
    .pagecount:before { content: counter(pages); }
    .watermark { position: fixed; top: {{ $landscape ? '160pt' : '300pt' }}; left: 0; right: 0; text-align: center; font-size: {{ $a5 ? '54pt' : '78pt' }}; font-weight: bold; color: #E6007E; opacity: .07; transform: rotate(-28deg); letter-spacing: 6pt; }

    /* Blocs */
    .box { border: .8pt solid #dcdce6; border-radius: 5pt; padding: 8pt 10pt; }
    .box-fill { background: #f5f5f9; border-radius: 5pt; padding: 8pt 10pt; }
    .box-accent { border-left: 3pt solid #E6007E; background: #fdf2f8; padding: 8pt 10pt; }
    .label { font-size: {{ $a5 ? '6.3pt' : '6.8pt' }}; color: #6b6b80; text-transform: uppercase; letter-spacing: .7pt; }
    .value { font-weight: bold; color: #16161D; }
    .section-title { font-size: {{ $a5 ? '8pt' : '9pt' }}; font-weight: bold; color: #16161D; text-transform: uppercase; letter-spacing: 1pt; margin: 13pt 0 5pt; padding-bottom: 3pt; border-bottom: 1.6pt solid #E6007E; }
    .kv td { padding: 2.2pt 0; vertical-align: top; }
    .kv td.k { color: #6b6b80; width: 34%; }

    /* Tableaux de lignes */
    .lines th { background: #16161D; color: #fff; font-size: {{ $a5 ? '6.5pt' : '7pt' }}; text-transform: uppercase; letter-spacing: .5pt; padding: 5.5pt 6pt; text-align: left; }
    .lines th.right { text-align: right; }
    .lines td { padding: 5.5pt 6pt; border-bottom: .6pt solid #e6e6ee; vertical-align: top; }
    .lines tr.even td { background: #fafafc; }
    .lines tr.total td { background: #f1f1f6; font-weight: bold; border-bottom: 0; }
    .totals td { padding: 4pt 7pt; }
    .totals .grand td { background: #16161D; color: #fff; font-size: {{ $a5 ? '9.5pt' : '10.5pt' }}; font-weight: bold; }
    .totals .due td { background: #E6007E; color: #fff; font-weight: bold; }

    .badge { display: inline-block; padding: 1.5pt 7pt; border-radius: 8pt; font-size: {{ $a5 ? '6.3pt' : '6.9pt' }}; font-weight: bold; background: #ececf3; color: #3a3a4d; text-transform: uppercase; letter-spacing: .4pt; }
    .badge-magenta { background: #fce7f3; color: #b0005f; }
    .badge-cyan { background: #e0f4fc; color: #006d99; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-yellow { background: #fef9c3; color: #854d0e; }
    .kpi { border: .8pt solid #dcdce6; border-top: 2.5pt solid #16161D; border-radius: 4pt; padding: 6pt 8pt; }
    .kpi .value { font-size: {{ $a5 ? '10pt' : '12pt' }}; }
    .signature { height: 50pt; border: .8pt dashed #b7b7c8; border-radius: 4pt; }
    .stamp { border: 1.6pt solid #16a34a; color: #16a34a; border-radius: 4pt; padding: 4pt 8pt; font-weight: bold; text-transform: uppercase; display: inline-block; font-size: 7.5pt; }
    .page-break { page-break-after: always; }
</style>
