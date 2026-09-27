@extends('pdf.layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))

@section('content')
    @include('pdf.partials.ticket-header')
    <div class="center bold">TICKET DE CAISSE</div>
    <div class="center">{{ $sale->number }}{{ $sale->invoice ? ' · Facture '.$sale->invoice->number : '' }}</div>
    <div class="center small">{{ $sale->sold_at->format('d/m/Y H:i') }} · {{ $sale->register->code }} · {{ $sale->cashier->name }}</div>
    <div class="dashed"></div>
    <table class="ticket">
        @foreach ($sale->items as $item)
            <tr><td colspan="2">{{ $item->description }}</td></tr>
            <tr><td class="muted">{{ (float) $item->quantity }} × {{ $money($item->unit_price) }}{{ (float) $item->discount ? ' (−'.$money($item->discount).')' : '' }}</td><td class="right">{{ $money($item->total) }}</td></tr>
        @endforeach
    </table>
    <div class="dashed"></div>
    <table class="ticket">
        @if ((float) $sale->discount_total)<tr><td>Remises</td><td class="right">− {{ $money($sale->discount_total) }}</td></tr>@endif
        <tr><td>dont TVA</td><td class="right">{{ $money($sale->tax_total) }}</td></tr>
        <tr><td class="bold" style="font-size: 10pt;">TOTAL</td><td class="right bold" style="font-size: 11pt;">{{ $money($sale->total) }}</td></tr>
        @foreach ($sale->payments as $payment)
            <tr><td>{{ $payment->method->label() }}</td><td class="right">{{ $money($payment->amount) }}</td></tr>
        @endforeach
        @if ((float) $sale->change_due)<tr><td>Monnaie rendue</td><td class="right">{{ $money($sale->change_due) }}</td></tr>@endif
    </table>
    @if ($sale->client)
        <div class="dashed"></div>
        <div>Client : {{ $sale->client->displayName() }}</div>
        @if ($sale->client->loyaltyAccount)
            <div class="small">Carte {{ $sale->client->loyaltyAccount->card_number }} · +{{ $sale->loyalty_points_earned }} pts · solde {{ $sale->client->loyaltyAccount->points_balance }} pts</div>
        @endif
    @endif
    <div class="center" style="margin-top: 6pt;">
        <img src="{{ \App\Services\Support\Codes::barcodeDataUri($sale->number, 1, 30) }}" style="height: 26pt; max-width: 200pt;">
    </div>
    <div class="dashed"></div>
    <div class="center small">{{ $company['footer'] }}<br>Les articles vendus ne sont ni repris ni échangés sans ce ticket.</div>
@endsection
