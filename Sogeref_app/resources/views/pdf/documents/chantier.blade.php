{{-- ═══ MODÈLE « BTP / CHANTIER » — bloc projet, visas et signatures ═══ --}}
@php $site = $order?->site; @endphp
@extends('pdf.layout', ['docTitle' => $docTitle, 'docReference' => $docReference, 'docSubtitle' => 'Travaux de bâtiment & génie civil'])

@section('content')
    <table>
        <tr>
            <td style="width: 60%; vertical-align: top;" class="box-fill">
                <div class="label">Objet des travaux</div>
                <div class="bold navy" style="font-size: 13px; margin: 3px 0;">{{ $order?->title ?? $document->items->first()?->description }}</div>
                @if ($order?->description)<div>{{ $order->description }}</div>@endif
                <table style="margin-top: 8px;" class="small">
                    <tr><td class="muted" style="width: 35%;">Chantier</td><td class="bold">{{ $site?->code ?? '—' }} {{ $site ? '· '.$site->name : '' }}</td></tr>
                    <tr><td class="muted">Localisation</td><td>{{ $site?->address ?? $contact->city }}{{ $site?->city ? ', '.$site->city : '' }}</td></tr>
                    <tr><td class="muted">Délai prévisionnel</td><td>{{ $order?->delivery_date ? 'Fin des travaux au '.$order->delivery_date->format('d/m/Y') : 'À préciser' }}</td></tr>
                </table>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 37%; vertical-align: top;" class="box">
                <div class="label">Maître d’ouvrage</div>
                <div class="bold" style="font-size: 12px; margin-top: 3px;">{{ $contact->display_name }}</div>
                <div>{{ $contactAddress }}</div>
                <div>{{ $contact->phone }}</div>
                <div class="label" style="margin-top: 8px;">Date · Échéance</div>
                <div>{{ $document->issue_date->format('d/m/Y') }} · {{ ($document->due_date ?? $document->valid_until)?->format('d/m/Y') ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Détail estimatif</div>
    @include('pdf.documents._lines')
    @include('pdf.documents._totals')

    <div class="section-title">Visas</div>
    <table>
        <tr>
            @foreach (['Le maître d’ouvrage', 'Le conducteur de travaux', 'La Direction générale'] as $visa)
                <td style="width: 32%; vertical-align: top;"><div class="label">{{ $visa }}</div><div class="signature" style="margin-top: 4px;"></div></td>
                @if (! $loop->last)<td style="width: 2%;"></td>@endif
            @endforeach
        </tr>
    </table>
    @if ($terms)<p class="small muted" style="margin-top: 12px;">{!! nl2br(e($terms)) !!}</p>@endif
@endsection
