{{-- Facture au format ticket thermique 80 mm --}}
@extends('pdf.ticket-layout')

@php
    $money = fn ($v) => \App\Services\Support\Money::format($v);
    $reference = $invoice->order?->number ?? $invoice->sale?->number;
@endphp

@section('content')
    <div class="center title">{{ $invoice->type->label() }}</div>
    <div class="center bold" style="font-size: 9pt;">{{ $invoice->number }}</div>
    @if ($reference)<div class="center">Réf. {{ $reference }}</div>@endif
    <div class="center small">{{ $invoice->issued_at->format('d/m/Y H:i') }}</div>
    <div class="sep"></div>
    @if ($invoice->client)<div>Client : <span class="bold">{{ $invoice->client->displayName() }}</span></div><div class="sep"></div>@endif
    <table>
        @foreach ($invoice->items as $item)
            <tr><td colspan="2" class="bold">{{ $item->description }}</td></tr>
            <tr><td>{{ (float) $item->quantity }} x {{ $money($item->unit_price) }}</td><td class="right">{{ $money($item->total) }}</td></tr>
        @endforeach
    </table>
    <div class="sep"></div>
    <table>
        <tr><td>Total HT</td><td class="right">{{ $money((float) $invoice->total - (float) $invoice->tax_total) }}</td></tr>
        <tr><td>TVA</td><td class="right">{{ $money($invoice->tax_total) }}</td></tr>
        <tr class="total"><td>TOTAL TTC</td><td class="right">{{ $money($invoice->total) }}</td></tr>
        @foreach ($invoice->payments as $payment)
            <tr><td class="small">{{ $payment->method->label() }} {{ $payment->paid_at->format('d/m H:i') }}</td><td class="right small">{{ $money($payment->amount) }}</td></tr>
        @endforeach
        <tr><td>Payé</td><td class="right">{{ $money($invoice->amount_paid) }}</td></tr>
        <tr><td class="bold">Reste dû</td><td class="right bold">{{ $money($invoice->balanceDue()) }}</td></tr>
    </table>
    <div class="center" style="margin-top: 6pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($reference ?? $invoice->number, 90) }}" style="width: 62pt; height: 62pt;"></div>
@endsection
