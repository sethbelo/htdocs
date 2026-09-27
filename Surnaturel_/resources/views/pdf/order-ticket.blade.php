{{-- Ticket de prise de commande (à présenter en caisse) --}}
@extends('pdf.ticket-layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))

@section('content')
    <div class="inverse title">Bon de commande</div>
    <div class="center bold" style="font-size: 12pt; letter-spacing: .5pt; margin-top: 3pt;">{{ $order->number }}</div>
    <div class="center small">{{ $order->ordered_at->format('d/m/Y H:i') }}</div>
    <div class="sep"></div>
    <table>
        <tr><td>Client</td><td class="right bold">{{ $order->client->displayName() }}</td></tr>
        @if ($order->client->phone)<tr><td>Téléphone</td><td class="right">{{ $order->client->phone }}</td></tr>@endif
        <tr><td>Commercial</td><td class="right">{{ $order->salesRep?->name }}</td></tr>
        <tr><td>Siège</td><td class="right">{{ $order->site->name }}</td></tr>
        @if ($order->due_at)<tr><td>Délai</td><td class="right bold">{{ $order->due_at->format('d/m/Y H:i') }}</td></tr>@endif
        <tr><td>Priorité</td><td class="right">{{ $order->priority->label() }}</td></tr>
    </table>
    <div class="sep"></div>
    <table>
        @foreach ($order->items as $item)
            <tr><td colspan="2" class="bold">{{ $item->description }}</td></tr>
            @if ($item->specSummary())<tr><td colspan="2" class="small">{{ $item->specSummary() }}</td></tr>@endif
            <tr><td>{{ (float) $item->quantity }} x {{ $money($item->unit_price) }}</td><td class="right">{{ $money($item->total) }}</td></tr>
        @endforeach
    </table>
    <div class="sep"></div>
    <table>
        <tr><td>Total TTC</td><td class="right bold">{{ $money($order->total) }}</td></tr>
        @if ((float) $order->amount_paid > 0)<tr><td>Déjà payé</td><td class="right">{{ $money($order->amount_paid) }}</td></tr>@endif
        @if ((float) $order->deposit_required < (float) $order->total)
            <tr><td>Acompte minimum</td><td class="right">{{ $money(max(0, (float) $order->deposit_required - (float) $order->amount_paid)) }}</td></tr>
        @endif
    </table>
    <div class="box">
        <div class="small">MONTANT À ENCAISSER</div>
        <div class="bold" style="font-size: 13pt;">{{ $money($order->balanceDue()) }}</div>
    </div>
    <div class="center" style="margin-top: 7pt;">
        <img src="{{ \App\Services\Support\Codes::qrDataUri($order->number, 110) }}" style="width: 72pt; height: 72pt;"><br>
        <img src="{{ \App\Services\Support\Codes::barcodeDataUri($order->number, 1, 34) }}" style="height: 28pt; max-width: 200pt; margin-top: 4pt;"><br>
        <span class="small">{{ $order->number }}</span>
    </div>
    <div class="center small bold" style="margin-top: 4pt;">Présentez ce ticket à la caisse.</div>
@endsection
