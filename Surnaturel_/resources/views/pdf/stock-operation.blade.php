{{-- Bon d'entrée / de sortie magasin --}}
@php($money = fn ($v) => \App\Services\Support\Money::format($v))
@extends('pdf.layout', [
    'docTitle' => $operation->typeLabel(),
    'docReference' => 'N° '.$operation->number,
    'docSubtitle' => $operation->operated_at->format('d/m/Y à H:i').' · '.$operation->reason->label(),
])

@section('content')
    <table>
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6pt;"><div class="box-fill">
                <div class="label">Entrepôt</div>
                <div class="value" style="font-size: 10pt;">{{ $operation->warehouse->name }}</div>
                <table class="kv" style="margin-top: 3pt;">
                    <tr><td class="k">Siège</td><td>{{ $operation->warehouse->site->name }}</td></tr>
                    <tr><td class="k">{{ $operation->isEntry() ? 'Provenance' : 'Destination' }}</td><td>{{ $operation->counterpartyLabel() }}</td></tr>
                    @if ($operation->reference)<tr><td class="k">Référence</td><td>{{ $operation->reference }}</td></tr>@endif
                </table>
            </div></td>
            <td style="width: 50%; vertical-align: top; padding-left: 6pt;"><div class="box">
                <div class="label">{{ $operation->isEntry() ? 'Livré par' : 'Retiré par' }}</div>
                <div class="value" style="font-size: 10pt;">{{ $operation->performed_by_name }}</div>
                <div class="small">{{ $operation->performed_by_phone }}</div>
                <div class="label" style="margin-top: 5pt;">Enregistré par</div>
                <div>{{ $operation->recorder->name }} · {{ $operation->created_at->format('d/m/Y H:i') }}</div>
            </div></td>
        </tr>
    </table>

    <div class="section-title">Articles {{ $operation->isEntry() ? 'réceptionnés' : 'sortis' }}</div>
    <table class="lines">
        <thead><tr><th style="width: 4%;">#</th><th>Article</th><th class="right" style="width: 13%;">Quantité</th><th class="right" style="width: 13%;">Stock après</th><th class="right" style="width: 15%;">Coût unitaire</th><th class="right" style="width: 15%;">Valeur</th></tr></thead>
        <tbody>
            @foreach ($operation->movements as $movement)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td><span class="bold">{{ $movement->product->name }}</span> <span class="muted small">{{ $movement->product->sku }}</span></td>
                    <td class="right nowrap">{{ abs((float) $movement->quantity) }} {{ $movement->product->unit?->symbol }}</td>
                    <td class="right">{{ (float) $movement->balance_after }}</td>
                    <td class="right nowrap">{{ $money($movement->unit_cost) }}</td>
                    <td class="right nowrap">{{ $money(abs((float) $movement->quantity) * (float) $movement->unit_cost) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="5" class="right">VALEUR TOTALE</td><td class="right nowrap">{{ $money($operation->total_value) }}</td></tr>
        </tbody>
    </table>
    @if ($operation->notes)<div class="section-title">Observations</div><p>{!! nl2br(e($operation->notes)) !!}</p>@endif

    @include('pdf.partials.signatures', ['type' => 'stock_operation', 'names' => [0 => [$operation->recorder->name, $operation->operated_at], 1 => [$operation->performed_by_name, null]]])
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($operation->number, 1, 28) }}" style="height: 22pt;"></div>
@endsection
