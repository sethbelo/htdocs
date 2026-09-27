{{-- ═══ MODÈLE « CLASSIQUE » — format administratif historique SOGEREF ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @include('pdf.partials.styles')
    <style>
        @page { margin: 40px 42px 70px 42px; }
        .grid td, .grid th { border: 1px solid #8a9dac; }
        .grid th { background: #fff; color: #1a2733; text-transform: none; letter-spacing: 0; font-size: 10.5px; font-weight: normal; padding: 6px 8px; }
        .grid td { border-top: 0; border-bottom: 0; padding: 6px 8px; }
        .grid tr:last-child td { border-bottom: 1px solid #8a9dac; height: 60px; }
        .classic-footer { position: fixed; bottom: -46px; left: 0; right: 0; font-size: 8.5px; color: #667c8c; border-top: 1px solid #d6dfe6; padding-top: 6px; }
    </style>
</head>
<body>
<div class="classic-footer">
    <table><tr><td>{{ $company->document_footer }}</td><td class="right nowrap">Page <span class="pagenum"></span></td></tr></table>
</div>

<table>
    <tr>
        <td style="width: 50%; vertical-align: top;"><img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 60px;"></td>
        <td style="vertical-align: top; text-align: right;">
            <div style="font-size: 22px; font-weight: bold; color: #0d2240;">{{ ucfirst(mb_strtolower($docTitle)) }}</div>
            <div style="font-size: 13px; font-weight: bold; color: #9e0f16;">Réf. : {{ $document->number }}{{ $isProforma ? ' - Proforma' : '' }}</div>
            <div style="margin-top: 4px; color: #0d2240;">Date {{ $type === 'quote' ? 'du devis' : 'facturation' }} : {{ $document->issue_date->format('d/m/Y') }}</div>
            <div style="color: #0d2240;">Date {{ $type === 'quote' ? 'de validité' : 'échéance' }} : {{ ($type === 'quote' ? $document->valid_until : $document->due_date)?->format('d/m/Y') ?? '—' }}</div>
            <div style="color: #0d2240;">Code {{ strtolower($partyLabel) }} : {{ $contact->reference }}</div>
        </td>
    </tr>
</table>

<table style="margin-top: 22px;">
    <tr>
        <td style="width: 48%; vertical-align: top;">
            <div style="margin-bottom: 4px;">Émetteur :</div>
            <div style="background: #e9eef2; padding: 12px 14px; font-size: 11px; line-height: 1.6;">
                <strong style="font-size: 13px; color: #0d2240;">{{ strtoupper($company->name) }}</strong><br>
                N° RCCM: {{ $company->registration_number }}<br>ID.NAT: {{ data_get($company->settings, 'id_nat') }}<br>N° Impôt: {{ $company->tax_number }}<br>
                {{ data_get($company->address, 'line1') }},<br>{{ data_get($company->address, 'line2') }}, {{ data_get($company->address, 'city') }}/RDC<br><br>Tél.: {{ $company->phone }}
            </div>
        </td>
        <td style="width: 4%;"></td>
        <td style="width: 48%; vertical-align: top;">
            <div style="margin-bottom: 4px;">Adressé à :</div>
            <div style="border: 1px solid #8a9dac; padding: 12px 14px; min-height: 130px; font-size: 11px; line-height: 1.6;">
                <strong style="font-size: 13px;">{{ strtoupper($contact->display_name) }}</strong><br>
                {{ $contactAddress }}@if ($contact->phone)<br>Tél. : {{ $contact->phone }}@endif
            </div>
        </td>
    </tr>
</table>

<div style="border: 1px solid #d6dfe6; padding: 4px 8px; margin-top: 16px;"><strong>Percepteur (trice) :</strong> {{ $document->creator?->name }}</div>
<p class="right" style="margin: 8px 0 4px;">Montants exprimés en {{ $currencyLabel }}</p>

<table class="grid lines">
    <thead><tr><th>Désignation</th><th class="center" style="width: 8%;">TVA</th><th class="right" style="width: 12%;">P.U. HT</th><th class="right" style="width: 8%;">Qté</th><th class="right" style="width: 14%;">Total HT</th></tr></thead>
    <tbody>
        @foreach ($document->items as $item)
            <tr>
                <td>{{ $item->product?->sku ? $item->product->sku.' - ' : '' }}{{ $item->description }}@if ($item->product?->description && $item->product->description !== $item->description)<br><strong><em>{{ $item->product->description }}</em></strong>@endif</td>
                <td class="center">{{ qty($item->tax_rate) }}%</td>
                <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                <td class="right">{{ qty($item->quantity) }}</td>
                <td class="right">{{ number_format($item->subtotal, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        <tr><td>@if ($document->notes)<br><strong><em><u>N.B. :</u></em></strong><br><em>{!! nl2br(e($document->notes)) !!}</em>@endif</td><td></td><td></td><td></td><td></td></tr>
    </tbody>
</table>

<table style="margin-top: 10px;">
    <tr>
        <td style="width: 55%; vertical-align: top;">
            <table>
                <tr><td class="bold" style="width: 45%; padding: 3px 0;">Conditions de règlement:</td><td>{{ $contact->payment_terms ? $contact->payment_terms.' jours' : 'À réception' }}</td></tr>
                <tr><td class="bold" style="padding: 3px 0;">Mode de règlement:</td><td>Espèces, M-Pesa ou virement</td></tr>
            </table>
            <p class="small" style="margin-top: 10px;">Arrêté à la somme de : <strong>{{ \App\Support\AmountInWords::make((float) $document->total, $document->currency_code) }}</strong></p>
        </td>
        <td style="width: 45%; vertical-align: top;">
            <table class="totals">
                <tr><td>Total HT</td><td class="right">{{ number_format((float) $document->subtotal - (float) $document->discount_total, 2, ',', ' ') }}</td></tr>
                @if ((float) $document->tax_total > 0)<tr><td>Total TVA</td><td class="right">{{ number_format($document->tax_total, 2, ',', ' ') }}</td></tr>@endif
                <tr><td style="background: #e9eef2;">Total TTC</td><td class="right" style="background: #e9eef2;">{{ number_format($document->total, 2, ',', ' ') }}</td></tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
