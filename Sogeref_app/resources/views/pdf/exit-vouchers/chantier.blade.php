{{-- ═══ BON DE SORTIE « LIVRAISON CHANTIER » — transport et réception sur site ═══ --}}
@extends('pdf.layout', ['docTitle' => 'BON DE LIVRAISON CHANTIER', 'docReference' => 'Bon de sortie N° '.$voucher->number, 'docSubtitle' => 'Sortie du '.$voucher->voucher_date->format('d/m/Y')])

@section('content')
    <table>
        <tr>
            <td style="width: 56%; vertical-align: top; background: #104A63; color: #fff; border-radius: 6px; padding: 12px 14px;">
                <div style="font-size: 8.5px; letter-spacing: .8px; text-transform: uppercase; color: #FFCE31;">Chantier destinataire</div>
                <div style="font-size: 14px; font-weight: bold; margin-top: 3px;">{{ $voucher->site ? $voucher->site->code.' — '.$voucher->site->name : 'Non précisé' }}</div>
                @if ($voucher->site?->address)<div style="margin-top: 2px;">{{ is_array($voucher->site->address) ? collect($voucher->site->address)->filter()->implode(', ') : $voucher->site->address }}</div>@endif
                <div style="margin-top: 6px;">Motif : {{ $voucher->purpose }}</div>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 41%; vertical-align: top;" class="box">
                <div class="label">Départ</div><div class="bold">{{ $voucher->warehouse?->name ?? 'Magasin central' }}</div>
                <div class="label" style="margin-top: 6px;">Transport</div>
                <div>Véhicule / immatriculation : ......................</div>
                <div>Chauffeur : ......................................</div>
                <div>Heure de départ : ........ Heure d’arrivée : ........</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Matériaux et matériel livrés</div>
    <table class="lines">
        <thead><tr><th style="width: 5%;">#</th><th>Désignation</th><th style="width: 10%;">Unité</th><th class="right" style="width: 13%;">Quantité</th><th class="right" style="width: 13%;">Reçu</th><th style="width: 20%;">Réserves</th></tr></thead>
        <tbody>
            @foreach ($voucher->lines as $line)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td>{{ $line->product?->sku ? $line->product->sku.' — ' : '' }}{{ $line->description }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="right bold">{{ qty($line->quantity) }}</td>
                    <td style="border-bottom: 1px dotted #8a9dac;"></td>
                    <td style="border-bottom: 1px dotted #8a9dac;"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="box" style="margin-top: 10px; height: 50px;"><span class="label">Réserves à la réception (manquants, casse, non-conformités)</span></div>

    <div class="section-title">Signatures</div>
    @include('pdf.exit-vouchers._visas', ['roles' => [['Magasin (remis par)', $voucher->executor?->name, $voucher->executed_at], ['Transporteur', null, null], ['Chef de chantier (reçu par)', $voucher->site?->manager?->name ?? null, null]]])
@endsection
