{{-- ═══ REÇU « TICKET 80 mm » — imprimante thermique de caisse ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $payment->reference }}</title>
    <style>
        @page { margin: 10px 10px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #000; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; } .right { text-align: right; } .bold { font-weight: bold; }
        .rule { border-top: 1px dashed #000; margin: 6px 0; }
    </style>
</head>
<body>
    <div class="center bold" style="font-size: 12px;">{{ mb_strtoupper($company->name) }}</div>
    <div class="center">{{ collect($company->address ?? [])->filter()->implode(', ') }}</div>
    <div class="center">{{ $company->phone }}</div>
    <div class="rule"></div>
    <div class="center bold" style="font-size: 11px;">REÇU DE PAIEMENT</div>
    <div class="center">N° {{ $payment->reference }}</div>
    <div class="center">{{ $payment->paid_at->format('d/m/Y H:i') }}</div>
    <div class="rule"></div>
    <div>Client : <span class="bold">{{ $payerName }}</span></div>
    <div>Mode : {{ $methodLabel }}</div>
    @foreach ($payment->allocations as $allocation)
        <table><tr><td>{{ $allocation->document?->number }}</td><td class="right">{{ money($allocation->amount, $payment->currency_code) }}</td></tr></table>
    @endforeach
    <div class="rule"></div>
    <table><tr><td class="bold" style="font-size: 11px;">TOTAL REÇU</td><td class="right bold" style="font-size: 11px;">{{ money($payment->amount, $payment->currency_code) }}</td></tr></table>
    <div style="margin-top: 3px; font-size: 8px;">{{ $amountWords }}</div>
    <div class="rule"></div>
    <div>Caissier : {{ $cashier }}</div>
    <div class="center" style="margin-top: 8px;">Merci de votre confiance !</div>
</body>
</html>
