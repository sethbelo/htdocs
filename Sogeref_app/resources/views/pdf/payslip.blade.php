{{-- Fiche(s) de paie — une page par employé, présentation fidèle au modèle SOGEREF --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @include('pdf.partials.styles')
    <style>
        @page { margin: 36px 42px 60px 42px; }
        .page-break { page-break-after: always; }
        .info td { border-bottom: 1px solid #e3e9ee; padding: 9px 12px; }
        .rubriques th { background: #f4f7f9; color: #1a2733; font-size: 10px; text-transform: uppercase; padding: 10px 12px; border-bottom: 2px solid #cfd9e1; text-align: left; }
        .rubriques td { padding: 9px 12px; border-bottom: 1px solid #e3e9ee; }
        .gain { color: #15803d; }
        .ret { color: #dc2626; }
        .foot { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 8px; color: #8a9dac; }
    </style>
</head>
<body>
<div class="foot">Document généré par SOGEREF ERP — Ce document fait office de bulletin de paie conformément au code du travail en vigueur.</div>
@foreach ($slips as $slip)
    @php
        $employee = $slip->employee;
        $period = $slip->period;
    @endphp
    <div class="{{ $loop->last ? '' : 'page-break' }}">
        <table>
            <tr>
                <td style="width: 24%; vertical-align: top;"><img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 52px;"></td>
                <td style="vertical-align: top; padding-left: 10px;">
                    <div style="font-size: 15px; font-weight: bold; color: #104A63;">{{ strtoupper($company->name) }}</div>
                    <div class="small">{{ data_get($company->address, 'city', 'Kinshasa') }}, RDC - Tél: {{ $company->phone }}</div>
                    <div class="small">RCCM: {{ $company->registration_number }} | ID.NAT: {{ data_get($company->settings, 'id_nat') }}</div>
                </td>
                <td style="vertical-align: top; text-align: right;">
                    <div style="font-size: 18px; font-weight: bold;">FICHE DE PAIE</div>
                    <div class="bold" style="margin-top: 10px;">{{ $period->end_date->translatedFormat('F Y') }}</div>
                </td>
            </tr>
        </table>
        <div style="border-top: 2.5px solid #1a2733; margin: 12px 0 16px;"></div>

        <table class="info" style="border: 1px solid #d6dfe6;">
            <tr><td style="width: 50%;"><div class="label">Nom et prénom</div><div class="bold" style="font-size: 12px;">{{ $employee->full_name }}</div></td><td><div class="label">Matricule</div><div class="bold" style="font-size: 12px;">{{ $employee->matricule }}</div></td></tr>
            <tr><td><div class="label">Département / Service</div><div class="bold">{{ $employee->department?->name ?? '-' }}</div></td><td><div class="label">Fonction / Grade</div><div class="bold">{{ $employee->position?->title ?? '-' }}</div></td></tr>
            <tr><td><div class="label">Période de paie</div><div class="bold">{{ $period->start_date->format('d') }} au {{ $period->end_date->translatedFormat('d M Y') }}</div></td><td><div class="label">Date d’embauche · N° CNSS</div><div class="bold">{{ $employee->hire_date?->format('d/m/Y') ?? '-' }} · {{ $employee->cnss_number ?? '-' }}</div></td></tr>
            @if ($employee->isDaily())
                <tr><td><div class="label">Jours prestés</div><div class="bold">{{ qty($slip->days_worked) }} jour(s) · taux {{ money($employee->daily_rate) }}/j</div></td><td><div class="label">Chantier</div><div class="bold">{{ $period->site?->name ?? '-' }}</div></td></tr>
            @endif
        </table>

        <table class="rubriques" style="margin-top: 22px;">
            <thead><tr><th>Désignation / rubrique</th><th class="right" style="width: 22%;">Gains</th><th class="right" style="width: 22%;">Retenues</th></tr></thead>
            <tbody>
                @foreach (collect($slip->lines)->whereIn('type', ['gain']) as $line)
                    <tr><td>{{ $line['label'] }}</td><td class="right gain">{{ number_format($line['amount'], 2, '.', ',') }}</td><td></td></tr>
                @endforeach
                <tr style="background: #f8fafb;"><td class="bold">TOTAL BRUT IMPOSABLE</td><td class="right bold">{{ number_format((float) $slip->taxable + (float) $slip->cnss_employee, 2, '.', ',') }}</td><td></td></tr>
                @foreach (collect($slip->lines)->whereIn('type', ['allowance']) as $line)
                    <tr><td>{{ $line['label'] }} <span class="muted small">(non imposable)</span></td><td class="right gain">{{ number_format($line['amount'], 2, '.', ',') }}</td><td></td></tr>
                @endforeach
                @foreach (collect($slip->lines)->where('type', 'deduction') as $line)
                    <tr><td>{{ $line['label'] }}</td><td></td><td class="right ret">{{ number_format($line['amount'], 2, '.', ',') }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top: 34px;">
            <tr>
                <td style="width: 55%; vertical-align: bottom; padding-right: 30px;">
                    <table><tr><td class="center">Signature de l’Agent</td><td class="center">Pour la Direction</td></tr></table>
                    <div style="border-top: 1px solid #d6dfe6; margin-top: 60px;"></div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <div style="border: 1px solid #cfd9e1; background: #f8fafb; padding: 16px 18px;">
                        <table>
                            <tr><td class="small">TOTAL DES GAINS</td><td class="right" style="font-size: 12px;">{{ number_format($slip->gross, 2, '.', ',') }}</td></tr>
                            <tr><td class="small" style="padding-bottom: 6px; border-bottom: 2px solid #1a2733;">TOTAL DES RETENUES</td><td class="right" style="font-size: 12px; padding-bottom: 6px; border-bottom: 2px solid #1a2733;">{{ number_format((float) $slip->cnss_employee + (float) $slip->ipr + (float) $slip->other_deductions, 2, '.', ',') }}</td></tr>
                            <tr><td class="right" style="padding-top: 12px;">NET À PAYER :</td><td class="right" style="padding-top: 10px; font-size: 17px; font-weight: bold;">{{ number_format($slip->net, 2, '.', ',') }} {{ $employee->currency_code }}</td></tr>
                        </table>
                    </div>
                    <p class="small muted" style="margin-top: 6px;">Charges patronales (CNSS, INPP, ONEM) : {{ money($slip->employer_charges) }} · Taux IPR : 1 USD = {{ number_format($period->exchange_rate, 0, ',', ' ') }} CDF</p>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
