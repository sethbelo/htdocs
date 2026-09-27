{{-- Bon de transfert inter-entrepôts --}}
@extends('pdf.layout', [
    'docTitle' => 'Bon de transfert',
    'docReference' => 'N° '.$transfer->number,
    'docSubtitle' => 'Demandé le '.$transfer->requested_at->format('d/m/Y à H:i').' par '.$transfer->requester->name,
    'watermark' => $transfer->status->value === 'cancelled' ? 'ANNULÉ' : null,
])

@section('content')
    <table>
        <tr>
            <td style="width: 46%; vertical-align: top;"><div class="box-fill">
                <div class="label">Entrepôt source</div>
                <div class="value" style="font-size: 10pt;">{{ $transfer->from->name }}</div>
                <table class="kv" style="margin-top: 3pt;">
                    <tr><td class="k">Expédié par</td><td>{{ $transfer->shipper?->name ?? '—' }}</td></tr>
                    <tr><td class="k">Le</td><td>{{ $transfer->shipped_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                    <tr><td class="k">Convoyeur</td><td>{{ $transfer->carrier_name ?? '—' }}</td></tr>
                </table>
            </div></td>
            <td style="width: 8%; text-align: center; vertical-align: middle; font-size: 18pt; color: #E6007E;">&raquo;</td>
            <td style="width: 46%; vertical-align: top;"><div class="box">
                <div class="label">Entrepôt destination</div>
                <div class="value" style="font-size: 10pt;">{{ $transfer->to->name }}</div>
                <table class="kv" style="margin-top: 3pt;">
                    <tr><td class="k">Réceptionné par</td><td>{{ $transfer->receiver?->name ?? '—' }}</td></tr>
                    <tr><td class="k">Le</td><td>{{ $transfer->received_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                    <tr><td class="k">Statut</td><td>@include('pdf.partials.status', ['status' => $transfer->status])</td></tr>
                </table>
            </div></td>
        </tr>
    </table>

    <div class="section-title">Articles transférés</div>
    <table class="lines">
        <thead><tr><th style="width: 4%;">#</th><th>Article</th><th class="right" style="width: 13%;">Demandé</th><th class="right" style="width: 13%;">Expédié</th><th class="right" style="width: 13%;">Reçu</th><th class="right" style="width: 11%;">Écart</th></tr></thead>
        <tbody>
            @foreach ($transfer->items as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td class="bold">{{ $item->product->name }} <span class="muted small">{{ $item->product->unit?->symbol }}</span></td>
                    <td class="right">{{ (float) $item->quantity_requested }}</td>
                    <td class="right">{{ $item->quantity_shipped !== null ? (float) $item->quantity_shipped : '' }}</td>
                    <td class="right">{{ $item->quantity_received !== null ? (float) $item->quantity_received : '' }}</td>
                    <td class="right {{ $item->discrepancy() ? 'magenta bold' : '' }}">{{ $item->discrepancy() ?: '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($transfer->notes)<p class="small"><span class="bold">Demande :</span> {{ $transfer->notes }}</p>@endif
    @if ($transfer->reception_notes)<p class="small"><span class="bold">Réception :</span> {{ $transfer->reception_notes }}</p>@endif

    @include('pdf.partials.signatures', ['type' => 'stock_transfer', 'names' => [0 => [$transfer->shipper?->name, $transfer->shipped_at], 1 => [$transfer->carrier_name, null], 2 => [$transfer->receiver?->name, $transfer->received_at]]])
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($transfer->number, 1, 28) }}" style="height: 22pt;"></div>
@endsection
