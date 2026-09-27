@extends('pdf.layout')

@section('content')
    @include('pdf.partials.ticket-header')

    <div class="center bold" style="font-size: 11pt;">BON DE COMMANDE</div>
    <div class="center bold" style="font-size: 13pt; letter-spacing: 0.5pt;">{{ $order->number }}</div>
    <div class="center small">{{ $order->ordered_at->format('d/m/Y H:i') }}</div>
    <div class="dashed"></div>

    <table class="ticket">
        <tr><td class="muted">Client</td><td class="right bold">{{ $order->client->displayName() }}</td></tr>
        @if ($order->client->phone)<tr><td class="muted">Téléphone</td><td class="right">{{ $order->client->phone }}</td></tr>@endif
        <tr><td class="muted">Commercial</td><td class="right">{{ $order->salesRep?->name }}</td></tr>
        <tr><td class="muted">Siège</td><td class="right">{{ $order->site->name }}</td></tr>
        @if ($order->due_at)<tr><td class="muted">Délai</td><td class="right bold">{{ $order->due_at->format('d/m/Y H:i') }}</td></tr>@endif
        <tr><td class="muted">Priorité</td><td class="right">{{ $order->priority->label() }}</td></tr>
    </table>
    <div class="dashed"></div>

    <table class="ticket">
        @foreach ($order->items as $item)
            <tr><td colspan="2" class="bold">{{ $item->description }}</td></tr>
            @if ($item->specSummary())<tr><td colspan="2" class="small muted">{{ $item->specSummary() }}</td></tr>@endif
            <tr>
                <td>{{ (float) $item->quantity }} × {{ \App\Services\Support\Money::format($item->unit_price) }}</td>
                <td class="right">{{ \App\Services\Support\Money::format($item->total) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="dashed"></div>

    <table class="ticket">
        <tr><td>Total TTC</td><td class="right bold">{{ \App\Services\Support\Money::format($order->total) }}</td></tr>
        @if ((float) $order->amount_paid > 0)<tr><td>Déjà payé</td><td class="right">{{ \App\Services\Support\Money::format($order->amount_paid) }}</td></tr>@endif
        @if ((float) $order->deposit_required < (float) $order->total)
            <tr><td>Acompte minimum</td><td class="right">{{ \App\Services\Support\Money::format(max(0, (float) $order->deposit_required - (float) $order->amount_paid)) }}</td></tr>
        @endif
    </table>
    <div style="border: 1.2pt solid #18181b; padding: 5pt; margin-top: 5pt;" class="center">
        <div class="small">MONTANT À ENCAISSER</div>
        <div class="bold" style="font-size: 14pt;">{{ \App\Services\Support\Money::format($order->balanceDue()) }}</div>
    </div>

    <div class="center" style="margin-top: 8pt;">
        <img src="{{ \App\Services\Support\Codes::qrDataUri($order->number, 110) }}" style="width: 78pt; height: 78pt;"><br>
        <img src="{{ \App\Services\Support\Codes::barcodeDataUri($order->number, 1, 34) }}" style="height: 30pt; max-width: 200pt; margin-top: 4pt;"><br>
        <span class="small">{{ $order->number }}</span>
    </div>
    <div class="dashed"></div>
    <div class="center small">Présentez ce ticket à la caisse.<br>{{ $company['footer'] }}</div>
@endsection
