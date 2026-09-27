{{-- Attestation d'affiliation (soins médicaux / assurance vie) --}}
@php($employee = $affiliation->employee)
@extends('pdf.layout', [
    'docTitle' => 'Attestation d’affiliation',
    'docReference' => 'N° '.$affiliation->member_number,
    'docSubtitle' => $affiliation->policy->type->label().' · '.$affiliation->policy->provider,
    'watermark' => $affiliation->isActive() ? null : mb_strtoupper($affiliation->status->label()),
])

@section('content')
    <p style="margin-top: 6pt; font-size: 10pt; line-height: 1.7;">
        Nous soussignés, <strong>{{ $company['legal_name'] ?: $company['name'] }}</strong>, attestons que la personne désignée ci-dessous
        est affiliée au contrat <strong>{{ $affiliation->policy->name }}</strong>{{ $affiliation->policy->policy_number ? ' (police n° '.$affiliation->policy->policy_number.')' : '' }}
        souscrit auprès de <strong>{{ $affiliation->policy->provider }}</strong>, au titre de la garantie « {{ $affiliation->policy->type->label() }} ».
    </p>

    <table style="margin-top: 8pt;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8pt;"><div class="box-fill">
                <div class="label">Membre couvert</div>
                <div class="value" style="font-size: 12pt;">{{ $affiliation->memberName() }}</div>
                <table class="kv" style="margin-top: 3pt;">
                    <tr><td class="k">Qualité</td><td>{{ $affiliation->memberRelationship() }}</td></tr>
                    @if ($affiliation->dependent?->birth_date)<tr><td class="k">Né(e) le</td><td>{{ $affiliation->dependent->birth_date->format('d/m/Y') }}</td></tr>@elseif ($employee->birth_date && ! $affiliation->dependent)<tr><td class="k">Né(e) le</td><td>{{ $employee->birth_date->format('d/m/Y') }}</td></tr>@endif
                    <tr><td class="k">N° de membre</td><td class="bold magenta">{{ $affiliation->member_number }}</td></tr>
                </table>
            </div></td>
            <td style="width: 50%; vertical-align: top;"><div class="box">
                <div class="label">Agent titulaire</div>
                <div class="value" style="font-size: 11pt;">{{ $employee->fullName() }}</div>
                <table class="kv" style="margin-top: 3pt;">
                    <tr><td class="k">Matricule</td><td>{{ $employee->matricule }}</td></tr>
                    <tr><td class="k">Fonction</td><td>{{ $employee->job_title ?? '-' }}</td></tr>
                    <tr><td class="k">Service</td><td>{{ $employee->department?->name ?? '-' }}</td></tr>
                </table>
            </div></td>
        </tr>
    </table>

    <div class="section-title">Couverture</div>
    <table class="lines">
        <tbody>
            <tr><td class="bold" style="width: 30%;">Statut</td><td>@include('pdf.partials.status', ['status' => $affiliation->status])</td></tr>
            <tr class="even"><td class="bold">Période</td><td>Depuis le {{ $affiliation->starts_on->format('d/m/Y') }}{{ $affiliation->ends_on ? ' jusqu’au '.$affiliation->ends_on->format('d/m/Y') : ' (durée indéterminée)' }}</td></tr>
            @if ($affiliation->policy->coverage)<tr><td class="bold">Garanties</td><td>{!! nl2br(e($affiliation->policy->coverage)) !!}</td></tr>@endif
            @if ($affiliation->policy->coverage_limit)<tr class="even"><td class="bold">Plafond</td><td>{{ \App\Services\Support\Money::format($affiliation->policy->coverage_limit) }}</td></tr>@endif
            @if ($affiliation->beneficiary_name)<tr><td class="bold">Bénéficiaire désigné</td><td>{{ $affiliation->beneficiary_name }} ({{ $affiliation->beneficiary_relationship }}) · {{ (float) $affiliation->beneficiary_share }} %</td></tr>@endif
            <tr class="even"><td class="bold">Assureur</td><td>{{ $affiliation->policy->provider }}{{ $affiliation->policy->contact_phone ? ' · '.$affiliation->policy->contact_phone : '' }}</td></tr>
        </tbody>
    </table>

    <p style="margin-top: 12pt;">La présente attestation est délivrée pour servir et valoir ce que de droit.</p>
    <table><tr><td></td><td class="right" style="width: 45%;">Fait à {{ $company['city'] ?? 'Kinshasa' }}, le {{ now()->translatedFormat('j F Y') }}</td></tr></table>

    @include('pdf.partials.signatures', ['type' => 'affiliation', 'names' => [0 => [$employee->fullName(), null]]])
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($affiliation->member_number, 80) }}" style="width: 48pt;"></div>
@endsection
