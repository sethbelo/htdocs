{{-- ═══ REÇU « CLASSIQUE » — cadres administratifs, montant en toutes lettres ═══ --}}
@extends('pdf.layout', ['docTitle' => 'REÇU', 'docReference' => 'N° '.$payment->reference])

@section('content')
    <div style="border: 2px solid #104A63; padding: 4px;">
        <div style="border: 1px solid #104A63; padding: 14px 16px;">
            <table>
                <tr>
                    <td class="bold navy" style="font-size: 13px;">Reçu de M./Mme/Société : <span style="color: #1a2733;">{{ $payerName }}</span></td>
                    <td class="right" style="width: 32%;"><span class="label">Date</span><br><strong>{{ $payment->paid_at->format('d/m/Y') }}</strong></td>
                </tr>
            </table>
            <table style="margin-top: 12px;">
                <tr><td style="width: 32%;" class="muted">La somme de</td><td class="bold" style="border-bottom: 1px dotted #667c8c;">{{ $amountWords }}</td></tr>
                <tr><td class="muted" style="padding-top: 8px;">En chiffres</td><td class="bold navy" style="font-size: 16px; padding-top: 8px;">{{ money($payment->amount, $payment->currency_code) }}</td></tr>
                <tr><td class="muted" style="padding-top: 8px;">Mode de paiement</td><td style="padding-top: 8px;">{{ $methodLabel }}{{ $payment->external_reference ? ' — Réf. '.$payment->external_reference : '' }}</td></tr>
                <tr><td class="muted" style="padding-top: 8px;">Pour</td><td style="padding-top: 8px; border-bottom: 1px dotted #667c8c;">{{ $purpose }}</td></tr>
            </table>
        </div>
    </div>
    @include('pdf.receipts._allocations')
    <table style="margin-top: 22px;">
        <tr>
            <td style="width: 60%;" class="small muted">Fait à {{ data_get($company->address, 'city', 'Kinshasa') }}, le {{ $payment->paid_at->format('d/m/Y') }}.<br>Encaissé par {{ $cashier }}.</td>
            <td style="width: 40%; vertical-align: top;"><div class="label">Signature et cachet</div><div class="signature" style="margin-top: 4px;">@if ($sealPath)<img src="{{ $sealPath }}" style="height: 62px; margin: 3px;">@endif</div></td>
        </tr>
    </table>
@endsection
