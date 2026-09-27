{{-- État de paie récapitulatif (paysage) --}}
@extends('pdf.layout', ['docSubtitle' => $period->start_date->format('d/m/Y').' → '.$period->end_date->format('d/m/Y').' · '.erp_status('payroll', $period->status)[0]])
@push('styles')<style>@page { margin: 110px 32px 86px 32px; } .lines td, .lines th { padding: 5px 6px; font-size: 9px; }</style>@endpush

@section('content')
    <table class="lines">
        <thead><tr><th>Matricule</th><th>Nom</th><th>Fonction</th><th class="right">Jours</th><th class="right">Brut</th><th class="right">CNSS 5 %</th><th class="right">IPR</th><th class="right">Avances</th><th class="right">Net à payer</th><th class="right">Charges pat.</th><th style="width: 12%;">Émargement</th></tr></thead>
        <tbody>
            @foreach ($slips as $slip)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td>{{ $slip->employee->matricule }}</td><td class="bold">{{ $slip->employee->full_name }}</td><td>{{ $slip->employee->position?->title }}</td>
                    <td class="right">{{ qty($slip->days_worked) }}</td><td class="right">{{ number_format($slip->gross, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($slip->cnss_employee, 2, ',', ' ') }}</td><td class="right">{{ number_format($slip->ipr, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($slip->other_deductions, 2, ',', ' ') }}</td><td class="right bold navy">{{ number_format($slip->net, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($slip->employer_charges, 2, ',', ' ') }}</td><td></td>
                </tr>
            @endforeach
            <tr style="background: #104A63;">
                <td colspan="4" style="color: #fff;" class="bold">TOTAUX ({{ $slips->count() }} agents)</td>
                @foreach (['gross', 'cnss_employee', 'ipr', 'other_deductions', 'net', 'employer_charges'] as $col)
                    <td class="right bold" style="color: #fff;">{{ number_format($slips->sum($col), 2, ',', ' ') }}</td>
                @endforeach
                <td></td>
            </tr>
        </tbody>
    </table>
    <table style="margin-top: 24px;">
        <tr>
            @foreach (['Établi par (RH)', 'Vérifié par (Comptabilité)', 'Approuvé par (Direction générale)'] as $visa)
                <td style="width: 32%;"><div class="label">{{ $visa }}</div><div class="signature" style="margin-top: 4px; height: 56px;"></div></td>
                @if (! $loop->last)<td style="width: 2%;"></td>@endif
            @endforeach
        </tr>
    </table>
@endsection
