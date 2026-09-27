{{-- Fiche de fabrication / dossier de construction : spécifications, liste de matériel, gamme opératoire --}}
@extends('pdf.layout', ['docSubtitle' => ($mo->salesOrder ? 'Commande '.$mo->salesOrder->number.' · ' : '').'Émise le '.now()->format('d/m/Y')])

@section('content')
    <table>
        <tr>
            <td style="width: 62%; vertical-align: top;" class="box-fill">
                <div class="label">{{ $mo->type === 'construction' ? 'Ouvrage' : 'Produit à fabriquer' }}</div>
                <div class="bold navy" style="font-size: 14px; margin: 3px 0;">{{ $mo->title }}</div>
                @if ($mo->description)<div>{{ $mo->description }}</div>@endif
                @if ($mo->product?->description)<div class="small muted" style="margin-top: 4px;"><em>{{ $mo->product->description }}</em></div>@endif
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 35%; vertical-align: top;" class="box">
                <table class="small">
                    <tr><td class="muted">Client</td><td class="bold right">{{ $mo->salesOrder?->contact?->display_name ?? 'Stock interne' }}</td></tr>
                    <tr><td class="muted">Quantité</td><td class="bold right">{{ qty($mo->quantity) }} {{ $mo->product?->unit ?? 'ouvrage' }}</td></tr>
                    <tr><td class="muted">Priorité</td><td class="right">{{ erp_status('priority', $mo->priority)[0] }}</td></tr>
                    <tr><td class="muted">Début prévu</td><td class="right">{{ $mo->planned_start?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="muted">Fin prévue</td><td class="right bold">{{ $mo->planned_end?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="muted">Responsable</td><td class="right">{{ $mo->responsible?->full_name ?? '—' }}</td></tr>
                    <tr><td class="muted">Lieu</td><td class="right">{{ $mo->warehouse?->name ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if (! empty($mo->specifications))
        <div class="section-title">Spécifications techniques</div>
        <table class="lines">
            <thead><tr><th style="width: 55%;">Élément</th><th>Valeur / exigence</th></tr></thead>
            <tbody>
                @foreach ($mo->specifications as $spec)
                    <tr class="{{ $loop->even ? 'even' : '' }}"><td class="bold">{{ $spec['label'] ?? '' }}</td><td>{{ $spec['value'] ?? '' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Liste de matériel {{ $mo->type === 'construction' ? '(métré)' : '(nomenclature)' }}</div>
    <table class="lines">
        <thead><tr><th style="width: 5%;">#</th><th style="width: 16%;">Code</th><th>Matière / fourniture</th><th class="right" style="width: 12%;">Qté prévue</th><th class="right" style="width: 12%;">Qté sortie</th><th style="width: 8%;">Unité</th><th class="right" style="width: 13%;">Coût estimé</th></tr></thead>
        <tbody>
            @forelse ($mo->materials as $m)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td><td class="small">{{ $m->product?->sku }}</td><td>{{ $m->description }}</td>
                    <td class="right bold">{{ qty($m->quantity_planned) }}</td><td class="right">{{ qty($m->quantity_consumed) }}</td><td>{{ $m->unit }}</td>
                    <td class="right">{{ number_format((float) $m->quantity_planned * (float) $m->unit_cost, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center muted">Aucune matière définie.</td></tr>
            @endforelse
            <tr><td colspan="6" class="right bold">COÛT MATIÈRES ESTIMÉ</td><td class="right bold navy">{{ money($mo->materialCost()) }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">Gamme opératoire</div>
    <table class="lines">
        <thead><tr><th style="width: 5%;">N°</th><th>Opération</th><th style="width: 18%;">Poste</th><th class="right" style="width: 11%;">Heures prév.</th><th style="width: 18%;">Opérateur</th><th style="width: 12%;">Visa</th></tr></thead>
        <tbody>
            @foreach ($mo->operations as $op)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td>{{ $loop->iteration }}</td><td class="bold">{{ $op->name }}</td><td>{{ $op->workstation }}</td>
                    <td class="right">{{ qty($op->planned_hours) }} h</td><td>{{ $op->employee?->full_name }}</td><td>{{ $op->status === 'done' ? 'Fait' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Contrôle & réception</div>
    <table>
        <tr>
            @foreach (['Chef d’atelier / conducteur', 'Contrôle qualité', 'Réception client'] as $visa)
                <td style="width: 32%; vertical-align: top;"><div class="label">{{ $visa }}</div><div class="signature" style="margin-top: 4px;"></div></td>
                @if (! $loop->last)<td style="width: 2%;"></td>@endif
            @endforeach
        </tr>
    </table>
@endsection
