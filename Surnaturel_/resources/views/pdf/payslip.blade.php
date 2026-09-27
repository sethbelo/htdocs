{{-- Fiche de paie — visa de la Direction générale obligatoire avant diffusion --}}
@php
    $money = fn ($v) => \App\Services\Support\Money::format($v);
    $employee = $payslip->employee;
    $period = $payslip->period;
    $approved = $payslip->dg_status === 'approved';
@endphp
@extends('pdf.layout', [
    'docTitle' => 'Fiche de paie',
    'docReference' => $payslip->number,
    'docSubtitle' => 'Période du '.$period->starts_on->format('d/m/Y').' au '.$period->ends_on->format('d/m/Y'),
    'watermark' => $approved ? null : 'PROJET',
])

@section('content')
    <table class="kv box" style="border-collapse: separate;">
        <tr>
            <td style="width: 50%; padding: 5pt 8pt;"><div class="label">Nom et prénom</div><div class="value" style="font-size: 10.5pt;">{{ $employee->fullName() }}</div></td>
            <td style="padding: 5pt 8pt;"><div class="label">Matricule</div><div class="value" style="font-size: 10.5pt;">{{ $employee->matricule }}</div></td>
        </tr>
        <tr>
            <td style="padding: 5pt 8pt;"><div class="label">Département / service</div><div class="value">{{ $employee->department?->name ?? '-' }}</div></td>
            <td style="padding: 5pt 8pt;"><div class="label">Fonction</div><div class="value">{{ $employee->job_title ?? '-' }}</div></td>
        </tr>
        <tr>
            <td style="padding: 5pt 8pt;"><div class="label">Statut · date d’embauche</div><div class="value">{{ $employee->employment_status->label() }} · {{ $employee->hired_at?->format('d/m/Y') ?? '-' }}</div></td>
            <td style="padding: 5pt 8pt;"><div class="label">Jours prestés · heures sup.</div><div class="value">{{ (float) $payslip->days_worked }} j · {{ (float) $payslip->overtime_hours }} h</div></td>
        </tr>
    </table>

    <div class="section-title">Rubriques</div>
    <table class="lines">
        <thead><tr><th>Désignation / rubrique</th><th class="right" style="width: 22%;">Gains</th><th class="right" style="width: 22%;">Retenues</th></tr></thead>
        <tbody>
            @foreach ($payslip->lines as $line)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td>{{ $line['label'] }}</td>
                    <td class="right nowrap" style="color: #15803d;">{{ $line['direction'] === 'earning' ? $money($line['amount']) : '' }}</td>
                    <td class="right nowrap" style="color: #b91c1c;">{{ $line['direction'] === 'deduction' ? $money($line['amount']) : '' }}</td>
                </tr>
            @endforeach
            <tr class="total"><td>TOTAUX</td><td class="right nowrap">{{ $money($payslip->gross_earnings) }}</td><td class="right nowrap">{{ $money($payslip->total_deductions) }}</td></tr>
        </tbody>
    </table>

    <table style="margin-top: 12pt; page-break-inside: avoid;">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 12pt;">
                <div class="box-accent">
                    <div class="label">Net à payer en toutes lettres</div>
                    <div class="bold" style="margin-top: 2pt;">{{ \App\Services\Support\Money::inWords($payslip->net_pay) }}</div>
                </div>
                <div style="margin-top: 8pt;">
                    @if ($approved)
                        <span class="stamp">Visa DG · {{ $payslip->dgApprover?->name }} · {{ $payslip->dg_approved_at?->format('d/m/Y') }}</span>
                    @else
                        <span class="badge badge-yellow">En attente du visa de la Direction générale</span>
                    @endif
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <table class="totals">
                    <tr><td>Total des gains</td><td class="right">{{ $money($payslip->gross_earnings) }}</td></tr>
                    <tr><td>Total des retenues</td><td class="right">{{ $money($payslip->total_deductions) }}</td></tr>
                    <tr class="grand"><td>NET À PAYER</td><td class="right">{{ $money($payslip->net_pay) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @include('pdf.partials.signatures', ['type' => 'payslip', 'names' => [0 => [$employee->fullName(), null], 2 => [$approved ? $payslip->dgApprover?->name : null, $approved ? $payslip->dg_approved_at : null]]])
    <p class="small muted center" style="margin-top: 8pt;">Document confidentiel — à conserver sans limitation de durée.</p>
@endsection
