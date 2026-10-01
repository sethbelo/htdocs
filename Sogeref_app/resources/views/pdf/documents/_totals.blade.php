<table style="margin-top: 12px;">
    <tr>
        <td style="width: 54%; vertical-align: top; padding-right: 14px;">
            <div class="box-fill">
                <div class="label">Arrêté{{ ($isProforma ?? false) ? 'e la présente facture pro forma' : ($type === 'quote' ? ' le présent devis' : ($type === 'purchase_order' ? ' le présent bon de commande' : 'e la présente facture')) }} à la somme de</div>
                <div class="bold navy" style="margin-top: 3px;">{{ \App\Support\AmountInWords::make((float) $document->total, $document->currency_code) }}</div>
            </div>
            @if ($type === 'invoice' && (float) $document->amount_paid > 0)
                <table style="margin-top: 8px;" class="small">
                    <tr><td>Déjà réglé</td><td class="right bold">{{ money($document->amount_paid, $document->currency_code) }}</td></tr>
                    <tr><td>Reste à payer</td><td class="right bold" style="color: #F7313A;">{{ money($document->balance_due, $document->currency_code) }}</td></tr>
                </table>
            @endif
        </td>
        <td style="width: 46%; vertical-align: top;">
            <table class="totals">
                <tr><td>Total HT brut</td><td class="right">{{ money($document->subtotal, $document->currency_code) }}</td></tr>
                @if ((float) $document->discount_total > 0)<tr><td>Remises</td><td class="right">− {{ money($document->discount_total, $document->currency_code) }}</td></tr>@endif
                <tr><td>Total HT net</td><td class="right">{{ money((float) $document->subtotal - (float) $document->discount_total, $document->currency_code) }}</td></tr>
                <tr><td>TVA</td><td class="right">{{ money($document->tax_total, $document->currency_code) }}</td></tr>
                <tr class="grand"><td>Total TTC</td><td class="right">{{ money($document->total, $document->currency_code) }}</td></tr>
            </table>
        </td>
    </tr>
</table>
