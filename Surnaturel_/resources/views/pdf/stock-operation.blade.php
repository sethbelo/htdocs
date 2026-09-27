@extends('pdf.layout')

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">{{ mb_strtoupper($operation->typeLabel()) }}</div>
        <div class="bold">N° {{ $operation->number }}</div>
        <div class="muted">{{ $operation->operated_at->format('d/m/Y à H:i') }}</div>
        <div style="margin-top: 4pt;"><span class="badge">{{ $operation->reason->label() }}</span></div>
    @endcomponent

    <table style="margin-bottom: 12pt;">
        <tr>
            <td style="width: 50%; padding-right: 6pt;"><div class="box">
                <div class="muted small">ENTREPÔT</div>
                <div class="bold">{{ $operation->warehouse->name }}</div>
                <div class="small">{{ $operation->warehouse->site->name }}</div>
                <div class="small">{{ $operation->isEntry() ? 'Provenance' : 'Destination' }} : {{ $operation->counterpartyLabel() }}</div>
                @if ($operation->reference)<div class="small">Référence : {{ $operation->reference }}</div>@endif
            </div></td>
            <td style="width: 50%; padding-left: 6pt;"><div class="box">
                <div class="muted small">{{ $operation->isEntry() ? 'LIVRÉ PAR' : 'RETIRÉ PAR' }}</div>
                <div class="bold">{{ $operation->performed_by_name }}</div>
                <div class="small">{{ $operation->performed_by_phone }}</div>
                <div class="muted small" style="margin-top: 4pt;">ENREGISTRÉ PAR</div>
                <div>{{ $operation->recorder->name }}</div>
            </div></td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th>Article</th><th class="right">Quantité</th><th class="right">Stock après</th><th class="right">Coût unitaire</th><th class="right">Valeur</th></tr></thead>
        <tbody>
            @foreach ($operation->movements as $movement)
                <tr>
                    <td>{{ $movement->product->name }} <span class="muted small">{{ $movement->product->sku }}</span></td>
                    <td class="right">{{ abs((float) $movement->quantity) }} {{ $movement->product->unit?->symbol }}</td>
                    <td class="right">{{ (float) $movement->balance_after }}</td>
                    <td class="right">{{ \App\Services\Support\Money::format($movement->unit_cost) }}</td>
                    <td class="right">{{ \App\Services\Support\Money::format(abs((float) $movement->quantity) * (float) $movement->unit_cost) }}</td>
                </tr>
            @endforeach
            <tr class="bold"><td colspan="4">Valeur totale</td><td class="right">{{ \App\Services\Support\Money::format($operation->total_value) }}</td></tr>
        </tbody>
    </table>
    @if ($operation->notes)<p><span class="bold">Observations :</span> {{ $operation->notes }}</p>@endif

    <table style="margin-top: 36pt;">
        <tr>
            <td style="width: 33%;">Magasinier<br><span class="small muted">{{ $operation->recorder->name }}</span><br><br>____________________</td>
            <td style="width: 33%;" class="center">{{ $operation->isEntry() ? 'Livreur' : 'Bénéficiaire' }}<br><span class="small muted">{{ $operation->performed_by_name }}</span><br><br>____________________</td>
            <td style="width: 33%;" class="right">Responsable entrepôt<br><br><br>____________________</td>
        </tr>
    </table>
    <div class="center" style="margin-top: 14pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($operation->number, 1, 30) }}" style="height: 26pt;"></div>
@endsection
