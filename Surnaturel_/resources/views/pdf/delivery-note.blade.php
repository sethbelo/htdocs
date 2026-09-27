{{-- Bon de livraison --}}
@extends('pdf.layout', [
    'docTitle' => 'Bon de livraison',
    'docReference' => $delivery->number,
    'docSubtitle' => 'Commande '.$delivery->order->number.' · '.$delivery->mode->label().' · '.($delivery->scheduled_at ?? $delivery->created_at)->format('d/m/Y'),
])

@section('content')
    <table>
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6pt;"><div class="box">
                <div class="label">Destinataire</div>
                <div class="value" style="font-size: 10pt;">{{ $delivery->recipient_name }}</div>
                <div>{{ collect([$delivery->address, $delivery->city])->filter()->implode(', ') }}</div>
                <div>{{ $delivery->phone }}</div>
                <div class="small muted">Client : {{ $delivery->order->client->displayName() }}</div>
            </div></td>
            <td style="width: 50%; vertical-align: top; padding-left: 6pt;"><div class="box-fill">
                <table class="kv">
                    <tr><td class="k">Livreur</td><td class="bold">{{ $delivery->driver?->name ?? '—' }}</td></tr>
                    <tr><td class="k">Statut</td><td>@include('pdf.partials.status', ['status' => $delivery->status])</td></tr>
                    <tr><td class="k">Frais</td><td>{{ \App\Services\Support\Money::format($delivery->fee) }}</td></tr>
                    <tr><td class="k">Solde commande</td><td class="bold">{{ \App\Services\Support\Money::format($delivery->order->balanceDue()) }}</td></tr>
                </table>
            </div></td>
        </tr>
    </table>

    <div class="section-title">Articles livrés</div>
    <table class="lines">
        <thead><tr><th style="width: 4%;">#</th><th>Article</th><th>Spécifications</th><th class="right" style="width: 12%;">Quantité</th><th class="center" style="width: 10%;">Contrôle</th></tr></thead>
        <tbody>
            @foreach ($delivery->order->items as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}"><td class="muted">{{ $loop->iteration }}</td><td class="bold">{{ $item->description }}</td><td class="small">{{ $item->specSummary() }}</td><td class="right">{{ (float) $item->quantity }}</td><td class="center">[&nbsp;&nbsp;&nbsp;]</td></tr>
            @endforeach
        </tbody>
    </table>
    @if ($delivery->observations)<div class="section-title">Observations</div><p>{{ $delivery->observations }}</p>@endif
    <p class="small muted">Le client reconnaît avoir reçu les articles ci-dessus en bon état et conformes au BAT validé. Toute réserve doit être notée sur ce bon.</p>

    @include('pdf.partials.signatures', ['type' => 'delivery_note', 'names' => [0 => [$delivery->driver?->name, $delivery->delivered_at ?? null], 1 => [$delivery->recipient_name, null]]])
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($delivery->order->number, 80) }}" style="width: 50pt;"></div>
@endsection
