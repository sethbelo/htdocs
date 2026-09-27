<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 0;
        }
        .school-info {
            font-size: 9px;
            color: #64748b;
            line-height: 1.4;
            margin-top: 4px;
        }
        .doc-title {
            font-size: 16px;
            color: #4f46e5;
            font-weight: bold;
            margin: 0;
            text-align: right;
            text-transform: uppercase;
        }
        .doc-meta {
            font-size: 9px;
            color: #64748b;
            margin-top: 5px;
            text-align: right;
            line-height: 1.4;
        }
        
        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .details-box td {
            border: none;
            font-size: 10px;
            color: #475569;
            vertical-align: middle;
        }

        /* Summary Cards */
        .summary-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .summary-card {
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 6px;
            background-color: #f8fafc;
        }
        .card-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }
        .card-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e1b4b;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 9px;
            font-size: 9px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8px;
            color: #94a3b8;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 50px;
        }
    </style>
</head>
<body>

    <!-- School Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 45px; margin-bottom: 8px; display: block;">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">
                    {{ $schoolAddress }}<br>
                    Tél: {{ $schoolPhone }} | Email: {{ $schoolEmail }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="doc-title">Rapport de Paie Global</div>
                <div class="doc-meta">
                    Code unique : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Period details -->
    <div class="details-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%;">
                    <strong>TYPE DU RAPPORT :</strong><br>
                    <span style="font-size: 11px; font-weight: bold; color: #1e1b4b;">Masse Salariale de l'Établissement</span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>PÉRIODE DE RÉFÉRENCE :</strong><br>
                    <span style="font-size: 11px; font-weight: bold; color: #4f46e5;">{{ $periodLabel }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Statistics / Masse Salariale -->
    <table class="summary-container">
        <tr>
            <td style="width: 25%; padding-right: 5px;">
                <div class="summary-card">
                    <div class="card-title">Base Cumulée</div>
                    <div class="card-value">${{ number_format($totalBase, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 5px;">
                <div class="summary-card" style="background-color: #ecfdf5; border-color: #a7f3d0;">
                    <div class="card-title" style="color: #065f46;">Total Primes (+)</div>
                    <div class="card-value" style="color: #047857;">${{ number_format($totalBonus, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 5px;">
                <div class="summary-card" style="background-color: #fef2f2; border-color: #fca5a5;">
                    <div class="card-title" style="color: #991b1b;">Total Retenues (-)</div>
                    <div class="card-value" style="color: #b91c1c;">${{ number_format($totalDeductions, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 5px;">
                <div class="summary-card" style="background-color: #e0e7ff; border-color: #c7d2fe;">
                    <div class="card-title" style="color: #3730a3;">Masse Salariale Net</div>
                    <div class="card-value" style="color: #4f46e5;">${{ number_format($totalNet, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Detailed Table -->
    <h3 style="font-size: 10px; text-transform: uppercase; color: #1e1b4b; margin-bottom: 10px;">Détails des Rémunérations</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Collaborateur</th>
                <th style="width: 15%;">Spécialité</th>
                <th style="width: 10%;">Mois</th>
                <th style="width: 12.5%; text-align: right;">Base ($)</th>
                <th style="width: 12.5%; text-align: right;">Primes ($)</th>
                <th style="width: 12.5%; text-align: right;">Retenues ($)</th>
                <th style="width: 12.5%; text-align: right;">Net ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payslips as $ps)
                <tr>
                    <td style="font-weight: bold; color: #1e1b4b;">{{ $ps->teacher->first_name }} {{ $ps->teacher->last_name }}</td>
                    <td>{{ $ps->teacher->specialty ?: 'Enseignant' }}</td>
                    <td>{{ $ps->month_year }}</td>
                    <td style="text-align: right;">{{ number_format($ps->base_salary, 2) }}</td>
                    <td style="text-align: right; color: #047857;">{{ number_format($ps->bonus, 2) }}</td>
                    <td style="text-align: right; color: #b91c1c;">-{{ number_format($ps->deductions, 2) }}</td>
                    <td style="text-align: right; font-weight: bold; color: #4f46e5;">{{ number_format($ps->net_salary, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">Aucun bulletin payé émis dans cette période.</td>
                </tr>
            @endforelse
        </tbody>
        @if($payslips->count() > 0)
            <tfoot>
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Masse Salariale:</td>
                    <td style="text-align: right; border-top: 2px solid #cbd5e1;">${{ number_format($totalBase, 2) }}</td>
                    <td style="text-align: right; border-top: 2px solid #cbd5e1; color: #047857;">${{ number_format($totalBonus, 2) }}</td>
                    <td style="text-align: right; border-top: 2px solid #cbd5e1; color: #b91c1c;">-${{ number_format($totalDeductions, 2) }}</td>
                    <td style="text-align: right; border-top: 2px solid #cbd5e1; color: #4f46e5;">${{ number_format($totalNet, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Signature Section -->
    <table class="signature-section" style="border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: left; vertical-align: top;">
                <div class="signature-title">Préparé par le service RH</div>
                <div style="font-size: 9px; margin-top: 40px; color: #64748b;">Signature & Date</div>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <div class="signature-title">Approuvé par la Direction</div>
                <div style="font-size: 9px; margin-top: 40px; color: #64748b;">Signature, Sceau & Date</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        {{ $footer }} | Code unique : {{ $uniqueCode }} | Page 1/1
    </div>

</body>
</html>
