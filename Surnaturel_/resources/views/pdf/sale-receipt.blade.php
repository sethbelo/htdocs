{{-- Ticket de caisse boutique --}}
@extends('pdf.ticket-layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))

@section('content')
    <div class="inverse title">Ticket de caisse</div>
    <div class="center bold" style="margin-top: 3pt;">{{ $sale->number }}{{ $sale->invoice ? ' · Fact. '.$sale->invoice->number : '' }}</div>
    <div class="center small">{{ $sale->sold_at->format('d/m/Y H:i') }} · {{ $sale->register->code }} · {{ $sale->cashier->name }}</div>
    <div class="sep"></div>
    <table>
        @foreach ($sale->items as $item)
            <tr><td colspan="2" class="bold">{{ $item->description }}</td></tr>
            <tr><td>{{ (float) $item->quantity }} x {{ $money($item->unit_price) }}{{ (float) $item->discount ? ' (-'.$money($item->discount).')' : '' }}</td><td class="right">{{ $money($item->total) }}</td></tr>
        @endforeach
    </table>
    <div class="rule"></div>
    <table>
        @if ((float) $sale->discount_total)<tr><td>Remises</td><td class="right">- {{ $money($sale->discount_total) }}</td></tr>@endif
        <tr><td>dont TVA</td><td class="right">{{ $money($sale->tax_total) }}</td></tr>
        <tr class="total"><td>TOTAL</td><td class="right">{{ $money($sale->total) }}</td></tr>
        @foreach ($sale->payments as $payment)
            <tr><td>{{ $payment->method->label() }}</td><td class="right">{{ $money($payment->amount) }}</td></tr>
        @endforeach
        @if ((float) $sale->change_due)<tr><td>Monnaie rendue</td><td class="right">{{ $money($sale->change_due) }}</td></tr>@endif
    </table>
    @if ($sale->client)
        <div class="sep"></div>
        <div>Client : <span class="bold">{{ $sale->client->displayName() }}</span></div>
        @if ($sale->client->loyaltyAccount)
            <div class="small">Carte {{ $sale->client->loyaltyAccount->card_number }} · +{{ $sale->loyalty_points_earned }} pts · solde {{ $sale->client->loyaltyAccount->points_balance }} pts</div>
        @endif
    @endif
    <div class="center" style="margin-top: 6pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($sale->number, 1, 30) }}" style="height: 24pt; max-width: 200pt;"></div>
    <div class="center small" style="margin-top: 3pt;">Articles ni repris ni échangés sans ce ticket.</div>
@endsection
