{{-- ═══ REÇU « CARNET À SOUCHE » — exemplaire client + souche d'archive sur une page A4 ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $payment->reference }}</title>
    @include('pdf.partials.styles')
    <style>
        @page { margin: 34px 40px; }
        .copy { border: 1.5px solid #104A63; border-radius: 8px; padding: 12px 16px; height: 330px; }
        .copy-tag { background: #FFCE31; color: #104A63; font-size: 8.5px; font-weight: bold; letter-spacing: 1px; padding: 2px 8px; border-radius: 8px; }
        .cut { border-top: 1.5px dashed #8a9dac; margin: 22px 0; text-align: center; }
        .cut span { background: #fff; color: #8a9dac; font-size: 8px; position: relative; top: -7px; padding: 0 8px; }
    </style>
</head>
<body>
    @foreach (['EXEMPLAIRE CLIENT', 'SOUCHE — ARCHIVE SOGEREF'] as $copy)
        <div class="copy">
            <table>
                <tr>
                    <td style="width: 55%;"><img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 40px;"></td>
                    <td class="right"><span class="copy-tag">{{ $copy }}</span><br><span style="font-size: 16px; font-weight: bold; color: #104A63;">REÇU N° {{ $payment->reference }}</span><br><span class="muted">{{ $payment->paid_at->format('d/m/Y à H:i') }}</span></td>
                </tr>
            </table>
            <table style="margin-top: 12px;">
                <tr><td class="muted" style="width: 26%;">Reçu de</td><td class="bold" style="font-size: 12px;">{{ $payerName }}</td></tr>
                <tr><td class="muted" style="padding-top: 5px;">La somme de</td><td class="bold navy" style="font-size: 16px; padding-top: 5px;">{{ money($payment->amount, $payment->currency_code) }}</td></tr>
                <tr><td class="muted" style="padding-top: 5px;">En lettres</td><td style="padding-top: 5px;">{{ $amountWords }}</td></tr>
                <tr><td class="muted" style="padding-top: 5px;">Règlement</td><td style="padding-top: 5px;">{{ $methodLabel }}{{ $payment->external_reference ? ' · Réf. '.$payment->external_reference : '' }}</td></tr>
                <tr><td class="muted" style="padding-top: 5px;">Pour</td><td style="padding-top: 5px;">{{ $purpose }}</td></tr>
            </table>
            <table style="margin-top: 14px;">
                <tr>
                    <td style="width: 48%;"><div class="label">Le client</div><div class="signature" style="height: 50px; margin-top: 3px;"></div></td>
                    <td style="width: 4%;"></td>
                    <td style="width: 48%;"><div class="label">Le caissier — {{ $cashier }}</div><div class="signature" style="height: 50px; margin-top: 3px;">@if ($sealPath)<img src="{{ $sealPath }}" style="height: 44px; margin: 2px;">@endif</div></td>
                </tr>
            </table>
        </div>
        @if ($loop->first)<div class="cut"><span>✂ découper ici</span></div>@endif
    @endforeach
    <p class="center small muted" style="margin-top: 10px;">{{ $company->name }} — {{ $company->document_footer }}</p>
</body>
</html>
