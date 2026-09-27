<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
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
            font-size: 18px;
            color: #4f46e5;
            font-weight: bold;
            margin: 0;
            text-align: right;
            text-transform: uppercase;
        }
        .doc-meta {
            font-size: 10px;
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
            table-layout: fixed;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 9px;
            font-size: 10px;
            text-align: left;
            word-wrap: break-word;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
        }

        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: bottom;
        }
    </style>
</head>
<body>

    <!-- School Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 50px; margin-bottom: 8px; display: block;">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">
                    {{ $schoolAddress }}<br>
                    Tél: {{ $schoolPhone }} | Email: {{ $schoolEmail }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="doc-title">Fiche de Paie</div>
                <div class="doc-meta">
                    Code unique : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Employee Details Box -->
    <div class="details-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%;">
                    <strong>COLLABORATEUR :</strong><br>
                    <span style="font-size: 12px; font-weight: bold; color: #1e1b4b;">
                        {{ $teacher->first_name }} {{ $teacher->last_name }}
                    </span><br>
                    Email: {{ $teacher->email }} | Tél: {{ $teacher->phone ?: 'Non renseigné' }}<br>
                    Statut actuel : {{ $teacher->status }}
                </td>
                <td style="width: 40%; text-align: right;">
                    <strong>PÉRIODE DU RAPPORT :</strong><br>
                    <span style="font-weight: bold; color: #4f46e5;">{{ $periodLabel }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Statistics -->
    <table class="summary-container">
        <tr>
            <td style="width: 25%; padding-right: 5px;">
                <div class="summary-card">
                    <div class="card-title">Salaires de Base</div>
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
                <div class="summary-card" style="background-color: #f5f3ff; border-color: #ddd6fe;">
                    <div class="card-title" style="color: #5b21b6;">Total Net Payé</div>
                    <div class="card-value" style="color: #6d28d9;">${{ number_format($totalNet, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Table of Issued Payslips in Period -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Date Paiement</th>
                <th style="width: 15%;">Mois / Année</th>
                <th style="width: 18%; text-align: right;">Base (USD)</th>
                <th style="width: 15%; text-align: right;">Prime (+)</th>
                <th style="width: 15%; text-align: right;">Retenue (-)</th>
                <th style="width: 19%; text-align: right;">Salaire Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payslips as $ps)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($ps->payment_date)->format('d/m/Y') }}</td>
                    <td style="font-weight: bold; color: #475569;">{{ $ps->month_year }}</td>
                    <td style="text-align: right; font-family: monospace;">${{ number_format($ps->base_salary, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; color: #047857;">
                        ${{ number_format($ps->bonus, 2) }}
                    </td>
                    <td style="text-align: right; font-family: monospace; color: #b91c1c;">
                        ${{ number_format($ps->deductions, 2) }}
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold; color: #6d28d9;">
                        ${{ number_format($ps->net_salary, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8; font-style: italic;">
                        Aucun bulletin de paie payé trouvé pour ce collaborateur sur la période sélectionnée.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Digital Signature Block with QR Code -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%;">
                <p style="font-size: 9px; color: #64748b; margin: 0; line-height: 1.4;">
                    Relevé officiel détaillé de paie émis et certifié conforme.<br>
                    Code de Signature Unique : <strong>{{ $uniqueCode }}</strong>
                </p>
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="display: inline-block; text-align: center; width: 100px;">
                    @if($qrCodeBase64)
                        <img src="{{ $qrCodeBase64 }}" style="width: 75px; height: 75px; display: block; margin: 0 auto 4px auto;" alt="QR Code Signature">
                    @else
                        <div style="width: 75px; height: 75px; border: 1px dashed #cbd5e1; display: block; margin: 0 auto 4px auto; line-height: 75px; text-align: center; font-size: 8px; color: #94a3b8;">
                            [ QR CODE ]
                        </div>
                    @endif
                    <span style="font-size: 7px; color: #94a3b8; font-family: monospace; letter-spacing: 0.5px; display: block; text-align: center;">SIGNATURE NUMÉRIQUE</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $schoolName }} - Tél: {{ $schoolPhone }} | Email: {{ $schoolEmail }} - {{ $footer }}
    </div>

</body>
</html>
