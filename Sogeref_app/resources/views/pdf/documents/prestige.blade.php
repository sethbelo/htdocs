{{-- ═══ MODÈLE « PRESTIGE » — bandeau jaune SOGEREF, cartouche client encadré, cachet ═══ --}}
@extends('pdf.layout', ['docTitle' => $docTitle, 'docReference' => $docReference, 'docSubtitle' => 'Émis le '.$document->issue_date->format('d/m/Y')])

@push('styles')
    <style>
        .prestige-band { background: #FFCE31; color: #104A63; padding: 10px 14px; border-radius: 4px; }
        .prestige-band .big { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
        .prestige-card { border: 2px solid #104A63; border-radius: 8px; padding: 12px 14px; }
        .prestige-card .label { color: #104A63; }
        .lines th { background: #FFCE31; color: #104A63; }
        .totals .grand td { background: #104A63; color: #FFCE31; }
    </style>
@endpush

@section('content')
    {{-- 1. Bandeau : titre, numéro, dates --}}
    <table class="prestige-band">
        <tr>
            <td style="width: 50%;"><span class="big">{{ $docTitle }}</span><br><span class="small">{{ $docReference }}</span></td>
            <td class="right small">
                Date : <strong>{{ $document->issue_date->format('d/m/Y') }}</strong><br>
                {{ $type === 'quote' ? 'Validité' : 'Échéance' }} : <strong>{{ ($type === 'quote' ? $document->valid_until : $document->due_date)?->format('d/m/Y') ?? '—' }}</strong>
                @if ($order)<br>Dossier : <strong>{{ $order->number }}</strong>@endif
            </td>
        </tr>
    </table>

    {{-- 2. Cartouche client et émetteur --}}
    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 55%; vertical-align: top;" class="prestige-card">
                <div class="label">{{ $partyLabel }} · {{ $contact->reference }}</div>
                <div class="bold" style="font-size: 13px; margin: 3px 0;">{{ $contact->display_name }}</div>
                @if ($contactAddress)<div>{{ $contactAddress }}</div>@endif
                <div>{{ collect([$contact->phone ? 'Tél. '.$contact->phone : null, $contact->email])->filter()->implode(' · ') }}</div>
                @if ($contact->tax_number)<div>N° Impôt : {{ $contact->tax_number }}</div>@endif
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 42%; vertical-align: top;" class="small">
                <div class="label">Émetteur</div>
                <div class="bold navy">{{ $company->name }}</div>
                <div>RCCM {{ $company->registration_number }} · ID.NAT {{ data_get($company->settings, 'id_nat') }}</div>
                <div>N° Impôt {{ $company->tax_number }}</div>
                <div>{{ $address }}</div>
                <div>{{ $company->phone }} · {{ $company->email }}</div>
            </td>
        </tr>
    </table>

    @if ($order?->title)
        <p style="margin: 14px 0 0;"><span class="label">Objet :</span> <strong>{{ $order->title }}</strong></p>
    @endif

    <p class="right muted small" style="margin: 10px 0 4px;">Montants exprimés en {{ $currencyLabel }}</p>
    @include('pdf.documents._lines', ['style' => 'modern'])
    @include('pdf.documents._totals')
    @include('pdf.documents._footer-blocks')
@endsection
