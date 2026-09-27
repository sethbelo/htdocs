{{-- ═══ MODÈLE « MODERNE » — bandeau SOGEREF, filet jaune, tableau aéré ═══ --}}
@extends('pdf.layout', ['docTitle' => $docTitle, 'docReference' => $docReference, 'docSubtitle' => 'Émis le '.$document->issue_date->format('d/m/Y')])

@section('content')
    {{-- 1. Parties --}}
    <table>
        <tr>
            <td style="width: 48%; vertical-align: top;" class="box-fill">
                <div class="label">Émetteur</div>
                <div class="bold navy" style="font-size: 12px; margin-top: 3px;">{{ $company->name }}</div>
                <div>RCCM : {{ $company->registration_number }} · ID.NAT : {{ data_get($company->settings, 'id_nat') }}</div>
                <div>N° Impôt : {{ $company->tax_number }}</div>
                <div>{{ $address }}</div>
                <div>Tél. : {{ $company->phone }} · {{ $company->email }}</div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;" class="box">
                <div class="label">{{ $partyLabel }} · {{ $contact->reference }}</div>
                <div class="bold" style="font-size: 12px; margin-top: 3px;">{{ $contact->display_name }}</div>
                @if ($contactAddress)<div>{{ $contactAddress }}</div>@endif
                @if ($contact->phone)<div>Tél. : {{ $contact->phone }}</div>@endif
                @if ($contact->email)<div>{{ $contact->email }}</div>@endif
                @if ($contact->tax_number)<div>N° Impôt : {{ $contact->tax_number }}</div>@endif
            </td>
        </tr>
    </table>

    {{-- 2. Références --}}
    <table style="margin-top: 12px;">
        <tr>
            <td class="box" style="width: 25%;"><div class="label">Date</div><div class="bold">{{ $document->issue_date->format('d/m/Y') }}</div></td>
            <td style="width: 1%;"></td>
            <td class="box" style="width: 25%;"><div class="label">{{ $type === 'quote' ? 'Valable jusqu’au' : 'Échéance' }}</div><div class="bold">{{ ($type === 'quote' ? $document->valid_until : $document->due_date)?->format('d/m/Y') ?? '—' }}</div></td>
            <td style="width: 1%;"></td>
            <td class="box" style="width: 24%;"><div class="label">Code {{ strtolower($partyLabel) }}</div><div class="bold">{{ $contact->reference }}</div></td>
            <td style="width: 1%;"></td>
            <td class="box" style="width: 24%;"><div class="label">Dossier</div><div class="bold">{{ $order?->number ?? '—' }}</div></td>
        </tr>
    </table>

    @if ($order?->title)
        <p style="margin: 14px 0 0;"><span class="label">Objet :</span> <strong>{{ $order->title }}</strong></p>
    @endif

    {{-- 3. Lignes --}}
    <p class="right muted small" style="margin: 10px 0 4px;">Montants exprimés en {{ $currencyLabel }}</p>
    @include('pdf.documents._lines', ['style' => 'modern'])

    {{-- 4. Totaux & montant en lettres --}}
    @include('pdf.documents._totals')

    {{-- 5. Conditions, notes & signatures --}}
    @include('pdf.documents._footer-blocks')
@endsection
