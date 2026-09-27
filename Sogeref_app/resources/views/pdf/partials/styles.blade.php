{{-- Styles PDF communs (Dompdf : CSS 2.1, tableaux plutôt que flex/grid) --}}
<style>
    @page { margin: 118px 42px 92px 42px; }
    * { box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #1a2733; line-height: 1.45; }
    h1, h2, h3 { margin: 0; color: #104A63; }
    table { width: 100%; border-collapse: collapse; }
    .muted { color: #667c8c; }
    .small { font-size: 9px; }
    .right { text-align: right; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .nowrap { white-space: nowrap; }
    .navy { color: #104A63; }
    .upper { text-transform: uppercase; letter-spacing: .8px; }

    /* En-tête et pied de page fixes (répétés sur chaque page) */
    .pdf-header { position: fixed; top: -96px; left: 0; right: 0; height: 86px; }
    .pdf-footer { position: fixed; bottom: -72px; left: 0; right: 0; height: 64px; font-size: 8.5px; color: #667c8c; }
    .brand-rule td { height: 4px; padding: 0; }
    .pagenum:before { content: counter(page); }
    .pagecount:before { content: counter(pages); }

    /* Blocs */
    .box { border: 1px solid #d6dfe6; border-radius: 6px; padding: 10px 12px; }
    .box-fill { background: #f4f7f9; border-radius: 6px; padding: 10px 12px; }
    .label { font-size: 8.5px; color: #667c8c; text-transform: uppercase; letter-spacing: .8px; }
    .section-title { font-size: 11px; font-weight: bold; color: #104A63; text-transform: uppercase; letter-spacing: 1px; margin: 16px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #FFCE31; }

    /* Tableaux de lignes */
    .lines th { background: #104A63; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .6px; padding: 7px 8px; text-align: left; }
    .lines td { padding: 7px 8px; border-bottom: 1px solid #e3e9ee; vertical-align: top; }
    .lines tr.even td { background: #fafcfd; }
    .totals td { padding: 5px 8px; }
    .totals .grand td { background: #104A63; color: #fff; font-size: 12px; font-weight: bold; }

    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8.5px; font-weight: bold; background: #e9eef2; color: #3c4e5c; }
    .signature { height: 70px; border: 1px dashed #b6c4cf; border-radius: 6px; }
</style>
