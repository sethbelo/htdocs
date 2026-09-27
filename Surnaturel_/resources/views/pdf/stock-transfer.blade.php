@extends('pdf.layout')

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">BON DE TRANSFERT</div>
        <div class="bold">N° {{ $transfer->number }}</div>
        <div class="muted">Demandé le {{ $transfer->requested_at->format('d/m/Y H:i') }}</div>
        <div style="margin-top: 4pt;"><span class="badge">{{ $transfer->status->label() }}</span></div>
    @endcomponent

    <table style="margin-bottom: 12pt;">
        <tr>
            <td style="width: 50%; padding-right: 6pt;"><div class="box"><div class="muted small">ENTREPÔT SOURCE</div><div class="bold">{{ $transfer->from->name }}</div>
                <div class="small">Expédié par {{ $transfer->shipper?->name ?? '—' }} {{ $transfer->shipped_at?->format('d/m/Y H:i') }}</div>
                <div class="small">Convoyeur : {{ $transfer->carrier_name ?? '—' }}</div></div></td>
            <td style="width: 50%; padding-left: 6pt;"><div class="box"><div class="muted small">ENTREPÔT DESTINATION</div><div class="bold">{{ $transfer->to->name }}</div>
                <div class="small">Réceptionné par {{ $transfer->receiver?->name ?? '—' }} {{ $transfer->received_at?->format('d/m/Y H:i') }}</div>
                <div class="small">Demandé par {{ $transfer->requester->name }}</div></div></td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th>Article</th><th class="right">Demandé</th><th class="right">Expédié</th><th class="right">Reçu</th><th class="right">Écart</th></tr></thead>
        <tbody>
            @foreach ($transfer->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="right">{{ (float) $item->quantity_requested }}</td>
                    <td class="right">{{ $item->quantity_shipped !== null ? (float) $item->quantity_shipped : '' }}</td>
                    <td class="right">{{ $item->quantity_received !== null ? (float) $item->quantity_received : '' }}</td>
                    <td class="right">{{ $item->discrepancy() ?: '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($transfer->notes)<p class="small"><span class="bold">Demande :</span> {{ $transfer->notes }}</p>@endif
    @if ($transfer->reception_notes)<p class="small"><span class="bold">Réception :</span> {{ $transfer->reception_notes }}</p>@endif

    <table style="margin-top: 36pt;">
        <tr>
            <td style="width: 33%;">Expéditeur<br><br><br>____________________</td>
            <td style="width: 33%;" class="center">Convoyeur<br><span class="small muted">{{ $transfer->carrier_name }}</span><br><br>____________________</td>
            <td style="width: 33%;" class="right">Réceptionnaire<br><br><br>____________________</td>
        </tr>
    </table>
    <div class="center" style="margin-top: 14pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($transfer->number, 1, 30) }}" style="height: 26pt;"></div>
@endsection
