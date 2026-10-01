{{-- ═══ REÇU « ÉPURÉ » — noir et blanc, économe en encre ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $payment->reference }}</title>
    <style>
        @page { margin: 48px 52px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #111; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #555; }
        .lines th { border-bottom: 1.5px solid #111; text-align: left; font-size: 8.5px; text-transform: uppercase; padding: 5px 4px; }
        .lines td { border-bottom: .5px solid #bbb; padding: 5px 4px; }
        .right { text-align: right; } .bold { font-weight: bold; }
        .money { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td><div style="font-size: 15px; font-weight: bold; letter-spacing: 2px;">{{ mb_strtoupper($company->name) }}</div><div class="label">{{ collect($company->address ?? [])->filter()->implode(', ') }} · {{ $company->phone }}</div></td>
            <td class="right"><div style="font-size: 22px; font-weight: bold; letter-spacing: 3px;">REÇU</div><div>N° {{ $payment->reference }} · {{ $payment->paid_at->format('d/m/Y') }}</div></td>
        </tr>
    </table>
    <hr style="border: 0; border-top: 1.5px solid #111; margin: 16px 0;">
    <table>
        <tr><td style="width: 30%;" class="label">Reçu de</td><td class="bold">{{ $payerName }}</td></tr>
        <tr><td class="label" style="padding-top: 6px;">Montant</td><td class="bold" style="font-size: 18px; padding-top: 6px;">{{ money($payment->amount, $payment->currency_code) }}</td></tr>
        <tr><td class="label" style="padding-top: 6px;">En lettres</td><td style="padding-top: 6px;">{{ $amountWords }}</td></tr>
        <tr><td class="label" style="padding-top: 6px;">Règlement</td><td style="padding-top: 6px;">{{ $methodLabel }}{{ $payment->external_reference ? ' — '.$payment->external_reference : '' }}</td></tr>
    </table>
    @if ($payment->allocations->isNotEmpty())
        <table class="lines" style="margin-top: 18px;">
            <thead><tr><th>Facture</th><th class="right">Réglé</th><th class="right">Reste dû</th></tr></thead>
            <tbody>
                @foreach ($payment->allocations as $allocation)
                    <tr><td>{{ $allocation->document?->number }}</td><td class="right money">{{ money($allocation->amount, $payment->currency_code) }}</td><td class="right money">{{ money($allocation->document?->balance_due ?? 0, $payment->currency_code) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <table style="margin-top: 40px;">
        <tr>
            <td style="width: 50%;"><div class="label">Le client</div><div style="border-bottom: .5px solid #111; height: 46px;"></div></td>
            <td style="width: 8%;"></td>
            <td><div class="label">Pour {{ $company->name }} ({{ $cashier }})</div><div style="border-bottom: .5px solid #111; height: 46px;"></div></td>
        </tr>
    </table>
</body>
</html>
