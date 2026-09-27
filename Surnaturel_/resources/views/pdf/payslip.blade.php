@extends('pdf.layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">BULLETIN DE PAIE</div>
        <div class="bold">{{ $payslip->number }}</div>
        <div>Période : {{ $payslip->period->starts_on->format('d/m/Y') }} au {{ $payslip->period->ends_on->format('d/m/Y') }}</div>
    @endcomponent

    <div class="box" style="margin-bottom: 12pt;">
        <table>
            <tr><td class="muted" style="width: 25%;">Employé</td><td class="bold">{{ $payslip->employee->fullName() }}</td><td class="muted">Matricule</td><td>{{ $payslip->employee->matricule }}</td></tr>
            <tr><td class="muted">Fonction</td><td>{{ $payslip->employee->job_title }}</td><td class="muted">Département</td><td>{{ $payslip->employee->department?->name }}</td></tr>
            <tr><td class="muted">Statut</td><td>{{ $payslip->employee->employment_status->label() }}</td><td class="muted">Jours travaillés</td><td>{{ (float) $payslip->days_worked }} · HS : {{ (float) $payslip->overtime_hours }} h</td></tr>
        </table>
    </div>

    <table class="items">
        <thead><tr><th>Rubrique</th><th class="right">Gains</th><th class="right">Retenues</th></tr></thead>
        <tbody>
            @foreach ($payslip->lines as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="right">{{ $line['direction'] === 'earning' ? $money($line['amount']) : '' }}</td>
                    <td class="right">{{ $line['direction'] === 'deduction' ? $money($line['amount']) : '' }}</td>
                </tr>
            @endforeach
            <tr class="bold"><td>Totaux</td><td class="right">{{ $money($payslip->gross_earnings) }}</td><td class="right">{{ $money($payslip->total_deductions) }}</td></tr>
        </tbody>
    </table>

    <table style="margin-top: 12pt;"><tr><td style="width: 55%;"></td><td>
        <div class="box" style="background: #eef2ff;"><table><tr><td class="bold" style="font-size: 12pt;">NET À PAYER</td><td class="right bold" style="font-size: 13pt;">{{ $money($payslip->net_pay) }}</td></tr></table></div>
    </td></tr></table>

    <table style="margin-top: 36pt;"><tr><td>Signature de l’employé<br><br>________________</td><td class="right">Pour l’employeur<br><br>________________</td></tr></table>
    <div class="footer">Document confidentiel — à conserver sans limitation de durée.</div>
@endsection
