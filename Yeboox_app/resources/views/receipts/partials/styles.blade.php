* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: '{{ $ticketSettings['font'] }}', sans-serif;
    font-size: {{ $ticketSettings['font_size'] }}px;
    color: {{ $ticketSettings['color'] }};
    width: {{ $paperWidth }};
    padding: 3mm;
    background: #fff;
    line-height: 1.35;
}
.receipt-shell { width: 100%; }
.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: 700; }
.small { font-size: .82em; }
.muted { opacity: .72; }
.company-logo { display: block; max-width: {{ $ticketSettings['logo_width'] }}mm; max-height: 15mm; margin: 0 auto 2mm; object-fit: contain; }
.company-name { font-size: 1.35em; font-weight: 800; line-height: 1.15; }
.fiscal { margin-top: 1.5mm; font-size: .78em; line-height: 1.35; }
.document-title { margin: 3mm 0 2mm; padding: 1.8mm; border-block: 1px solid currentColor; text-align: center; font-weight: 800; letter-spacing: .08em; }
.document-meta { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
.document-meta td { padding: .5mm 0; vertical-align: top; }
.rule { border-top: 1px dashed currentColor; margin: 2mm 0; opacity: .65; }
.rule-strong { border-top: 2px solid currentColor; margin: 2mm 0; }
.items, .totals, .payments { width: 100%; border-collapse: collapse; }
.items th, .items td, .totals td, .payments td { padding: 1mm 0; vertical-align: top; }
.items th { font-size: .82em; text-transform: uppercase; border-bottom: 1px dashed currentColor; }
.item-name { width: 52%; text-align: left; }
.item-qty { width: 13%; text-align: center; }
.item-price { width: 35%; text-align: right; }
.total-row td { padding: 1.8mm 0; border-block: 2px solid currentColor; font-size: 1.28em; font-weight: 900; }
.payment-title { margin: 2.5mm 0 1mm; font-size: .82em; font-weight: 800; letter-spacing: .08em; }
.change-row { font-weight: 900; }
.code-image { display: block; max-width: 46mm; max-height: 22mm; margin: 3mm auto 1mm; }
.code-image.qrcode { width: 22mm; height: 22mm; }
.footer { margin-top: 3mm; text-align: center; }
.powered { margin-top: 2mm; padding-top: 1.5mm; border-top: 1px dashed currentColor; font-size: .7em; text-align: center; opacity: .55; }

/* Six ticket personalities */
.template-minimal { padding-inline: 2mm; }
.template-minimal .document-title { border: 0; padding: 1mm; letter-spacing: .04em; }
.template-minimal .rule-strong { border-top-width: 1px; }

.template-classic .receipt-shell { border: 1px solid currentColor; padding: 2.5mm; }
.template-classic .company-name { font-family: serif; }
.template-classic .document-title { border: 3px double currentColor; }

.template-modern .company-name { letter-spacing: -.02em; }
.template-modern .document-title { color: #fff; background: {{ $ticketSettings['color'] }}; border: 0; border-radius: 1.5mm; }
.template-modern .total-row td { border: 0; background: #f0f0f0; padding-inline: 1.5mm; }

.template-elegant .receipt-shell { padding-top: 2mm; border-top: 4px double currentColor; }
.template-elegant .company-name { font-family: serif; font-style: italic; font-weight: 600; }
.template-elegant .document-title { border: 0; border-bottom: 1px solid currentColor; font-family: serif; }

.template-bold .company-name { font-size: 1.55em; text-transform: uppercase; }
.template-bold .document-title { border-width: 2px; font-size: 1.08em; }
.template-bold .total-row td { color: #fff; background: {{ $ticketSettings['color'] }}; border: 0; padding-inline: 1.5mm; font-size: 1.4em; }

.template-compact { padding: 1.5mm; line-height: 1.12; font-size: {{ max(8, $ticketSettings['font_size'] - 1) }}px; }
.template-compact .company-logo { max-height: 10mm; margin-bottom: 1mm; }
.template-compact .document-title { margin: 1.5mm 0; padding: .8mm; }
.template-compact .items th, .template-compact .items td, .template-compact .totals td, .template-compact .payments td { padding: .45mm 0; }
.template-compact .rule, .template-compact .rule-strong { margin: 1mm 0; }
.template-compact .footer { margin-top: 1.5mm; }

@media print {
    body { margin: 0; }
    @page { size: {{ $ticketSettings['paper_size'] === 'a4' ? 'A4' : $paperWidth.' auto' }}; margin: 0; }
}
