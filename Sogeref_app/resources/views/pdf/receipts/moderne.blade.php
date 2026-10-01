{{-- ═══ REÇU « MODERNE » — bandeau bleu SOGEREF, montant mis en valeur ═══ --}}
@extends('pdf.layout', ['docTitle' => 'REÇU DE PAIEMENT', 'docReference' => 'N° '.$payment->reference, 'docSubtitle' => 'Le '.$payment->paid_at->format('d/m/Y à H:i')])

@section('content')
    <table>
        <tr>
            <td style="width: 58%; vertical-align: top;" class="box-fill">
                <div class="label">Reçu de</div>
                <div class="bold navy" style="font-size: 14px; margin: 3px 0;">{{ $payerName }}</div>
                @if ($payment->contact?->reference)<div class="small muted">Code client {{ $payment->contact->reference }}</div>@endif
                @if ($payment->contact?->phone)<div class="small">Tél. {{ $payment->contact->phone }}</div>@endif
                <div class="label" style="margin-top: 10px;">Mode de règlement</div>
                <div class="bold">{{ $methodLabel }}{{ $payment->external_reference ? ' · Réf. '.$payment->external_reference : '' }}</div>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 39%; vertical-align: top; background: #104A63; color: #fff; border-radius: 6px; padding: 12px 14px;">
                <div style="font-size: 8.5px; letter-spacing: .8px; text-transform: uppercase; color: #FFCE31;">Montant reçu</div>
                <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">{{ money($payment->amount, $payment->currency_code) }}</div>
            </td>
        </tr>
    </table>
    <p style="margin: 12px 0 0;"><span class="label">Arrêté à la somme de :</span> <strong>{{ $amountWords }}</strong></p>
    @include('pdf.receipts._allocations')
    @if ($payment->notes)<p class="small" style="margin-top: 10px;"><span class="label">Observations :</span> {{ $payment->notes }}</p>@endif
    <table style="margin-top: 22px;">
        <tr>
            <td style="width: 48%; vertical-align: top;"><div class="label">Le client</div><div class="signature" style="margin-top: 4px;"></div></td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="label">Pour {{ $company->name }} — {{ $cashier }}</div>
                <div class="signature" style="margin-top: 4px;">@if ($sealPath)<img src="{{ $sealPath }}" style="height: 62px; margin: 3px;">@endif</div>
            </td>
        </tr>
    </table>
@endsection
