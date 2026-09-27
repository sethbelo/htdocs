{{-- Bon de prise en charge médicale --}}
@php($affiliation = $voucher->affiliation)
@extends('pdf.layout', [
    'docTitle' => 'Bon de prise en charge',
    'docReference' => 'N° '.$voucher->number,
    'docSubtitle' => 'Émis le '.$voucher->issued_on->format('d/m/Y').' · valable jusqu’au '.$voucher->valid_until->format('d/m/Y'),
    'watermark' => $voucher->valid_until->isPast() ? 'EXPIRÉ' : null,
])

@section('content')
    <table>
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 8pt;"><div class="box-fill">
                <div class="label">Patient</div>
                <div class="value" style="font-size: 12pt;">{{ $affiliation->memberName() }}</div>
                <div>{{ $affiliation->memberRelationship() }}{{ $affiliation->dependent ? ' de '.$affiliation->employee->fullName() : '' }}</div>
                <table class="kv" style="margin-top: 3pt;">
                    <tr><td class="k">Agent titulaire</td><td class="bold">{{ $affiliation->employee->fullName() }} ({{ $affiliation->employee->matricule }})</td></tr>
                    <tr><td class="k">N° de membre</td><td class="bold magenta">{{ $affiliation->member_number }}</td></tr>
                    <tr><td class="k">Contrat</td><td>{{ $affiliation->policy->name }} · {{ $affiliation->policy->provider }}</td></tr>
                </table>
            </div></td>
            <td style="width: 45%; vertical-align: top;"><div class="box">
                <div class="label">Établissement de soins</div>
                <div class="value" style="font-size: 11pt;">{{ $voucher->care_provider }}</div>
                <div class="label" style="margin-top: 6pt;">Nature des soins</div>
                <div class="bold">{{ $voucher->care_type->label() }}</div>
                <div class="label" style="margin-top: 6pt;">Montant estimé</div>
                <div class="bold ink" style="font-size: 13pt;">{{ \App\Services\Support\Money::format($voucher->estimated_amount) }}</div>
            </div></td>
        </tr>
    </table>

    @if ($voucher->description)<div class="section-title">Motif / observations</div><p>{!! nl2br(e($voucher->description)) !!}</p>@endif

    <div class="box-accent" style="margin-top: 10pt;">
        {{ $company['legal_name'] ?: $company['name'] }} garantit la prise en charge des soins ci-dessus dans la limite des garanties du contrat
        ({{ (float) $affiliation->policy->employer_share }} % à charge de l’employeur{{ $affiliation->policy->coverage_limit ? ', plafond '.\App\Services\Support\Money::format($affiliation->policy->coverage_limit) : '' }}).
        La facture est à adresser au service des ressources humaines en rappelant la référence {{ $voucher->number }}.
    </div>

    <div class="section-title">Visas & signatures</div>
    <table>
        <tr>
            @foreach ([['Émis par (RH)', $voucher->issuer?->name, $voucher->issued_on], ['Le patient / l’agent', null, null], ['Cachet de l’établissement de soins', null, null]] as [$role, $name, $date])
                <td style="width: 32%; vertical-align: top;"><div class="label">{{ $role }}</div><div class="small bold" style="min-height: 10pt;">{{ $name }}</div><div class="small muted" style="min-height: 10pt;">{{ $date?->format('d/m/Y') }}</div><div class="signature"></div></td>
                @if (! $loop->last)<td style="width: 2%;"></td>@endif
            @endforeach
        </tr>
    </table>
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($voucher->number, 1, 28) }}" style="height: 22pt;"></div>
@endsection
