@extends('pdf.layout')

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">BON DE LIVRAISON</div>
        <div class="bold">{{ $delivery->number }}</div>
        <div>Commande <span class="bold">{{ $delivery->order->number }}</span></div>
        <div class="muted">{{ $delivery->mode->label() }} · {{ ($delivery->scheduled_at ?? $delivery->created_at)->format('d/m/Y') }}</div>
    @endcomponent

    <table style="margin-bottom: 12pt;">
        <tr>
            <td style="width: 50%; padding-right: 6pt;"><div class="box">
                <div class="muted small">DESTINATAIRE</div>
                <div class="bold">{{ $delivery->recipient_name }}</div>
                <div>{{ $delivery->address }} {{ $delivery->city }}</div>
                <div>{{ $delivery->phone }}</div>
            </div></td>
            <td style="width: 50%; padding-left: 6pt;"><div class="box">
                <div class="muted small">LIVREUR</div>
                <div class="bold">{{ $delivery->driver?->name ?? '—' }}</div>
                <div>Frais : {{ \App\Services\Support\Money::format($delivery->fee) }}</div>
                <div>Statut : {{ $delivery->status->label() }}</div>
            </div></td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th>Article</th><th>Spécifications</th><th class="right">Quantité</th></tr></thead>
        <tbody>
            @foreach ($delivery->order->items as $item)
                <tr><td>{{ $item->description }}</td><td class="small">{{ $item->specSummary() }}</td><td class="right">{{ (float) $item->quantity }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if ($delivery->observations)<p><span class="bold">Observations :</span> {{ $delivery->observations }}</p>@endif

    <table style="margin-top: 36pt;">
        <tr>
            <td style="width: 50%;">Signature livreur<br><br><br>____________________</td>
            <td style="width: 50%;" class="right">Reçu en bon état — nom, date & signature du client<br><br><br>____________________</td>
        </tr>
    </table>
    <div class="center" style="margin-top: 16pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($delivery->order->number, 80) }}" style="width: 56pt;"></div>
@endsection
