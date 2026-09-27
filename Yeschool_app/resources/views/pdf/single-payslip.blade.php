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
            vertical-align: top;
        }

        table.salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.salary-table th, table.salary-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            font-size: 10px;
        }
        table.salary-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }

        .net-salary-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-align: right;
            color: #1e1b4b;
            margin-bottom: 20px;
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
                <div class="doc-title">Bulletin de Paie</div>
                <div class="doc-meta">
                    Code unique : <strong>{{ $uniqueCode }}</strong><br>
                    Période : <strong>{{ $payslip->month_year }}</strong><br>
                    Date de génération : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Employee Details Box -->
    <div class="details-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%;">
                    <strong>COLLABORATEUR :</strong><br>
                    <span style="font-size: 11px; font-weight: bold; color: #1e1b4b;">
                        {{ $payslip->teacher->first_name }} {{ $payslip->teacher->last_name }}
                    </span><br>
                    Email: {{ $payslip->teacher->email }}<br>
                    Tél: {{ $payslip->teacher->phone ?: 'Non renseigné' }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>DÉTAILS PAIEMENT :</strong><br>
                    Statut : <strong>{{ $payslip->status }}</strong><br>
                    Date de paiement : {{ $payslip->payment_date ? \Carbon\Carbon::parse($payslip->payment_date)->format('d/m/Y') : 'En attente' }}<br>
                    Méthode : {{ $payslip->payment_method ?: 'Virement' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Salary Details Table -->
    <table class="salary-table">
        <thead>
            <tr>
                <th style="width: 50%;">Rubrique</th>
                <th style="width: 25%; text-align: right;">Gains (USD)</th>
                <th style="width: 25%; text-align: right;">Retenues (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: bold;">Salaire de Base</td>
                <td style="text-align: right; font-family: monospace;">
                    ${{ number_format($payslip->base_salary, 2) }}
                </td>
                <td style="text-align: right; color: #94a3b8; font-style: italic;">-</td>
            </tr>
            @if($payslip->bonus > 0)
                <tr>
                    <td style="font-weight: bold; color: #047857;">Primes & Bonus</td>
                    <td style="text-align: right; font-family: monospace; color: #047857;">
                        +${{ number_format($payslip->bonus, 2) }}
                    </td>
                    <td style="text-align: right; color: #94a3b8; font-style: italic;">-</td>
                </tr>
            @endif
            @if($payslip->deductions > 0)
                <tr>
                    <td style="font-weight: bold; color: #b91c1c;">Retenues & Avances</td>
                    <td style="text-align: right; color: #94a3b8; font-style: italic;">-</td>
                    <td style="text-align: right; font-family: monospace; color: #b91c1c;">
                        -${{ number_format($payslip->deductions, 2) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Net Salary Box -->
    <div class="net-salary-box">
        SALAIRE NET À PAYER : ${{ number_format($payslip->net_salary, 2) }}
        <div style="font-size: 8px; font-weight: normal; color: #64748b; margin-top: 2px;">
            Équivalent FC : {{ number_format($payslip->net_salary * 2500, 0, ',', ' ') }} CDF
        </div>
    </div>

    <!-- Notes if exist -->
    @if($payslip->notes)
        <div style="margin-bottom: 25px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;">
            <strong style="font-size: 9px; uppercase; color: #475569; display: block; margin-bottom: 3px;">Notes :</strong>
            <p style="margin: 0; font-size: 9.5px; color: #475569; font-style: italic;">{{ $payslip->notes }}</p>
        </div>
    @endif

    <!-- Digital Signature Block with QR Code -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%;">
                <p style="font-size: 9px; color: #64748b; margin: 0; line-height: 1.4;">
                    Ce document tient lieu de bulletin de paie officiel certifié.<br>
                    ID Unique de Signature : <strong>{{ $uniqueCode }}</strong>
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
