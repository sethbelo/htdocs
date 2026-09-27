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
            font-size: 16px;
            color: #059669;
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
        
        /* Summary Box */
        .summary-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .summary-card {
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 8px;
        }
        .card-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 5px;
        }
        .card-value {
            font-size: 18px;
            font-weight: bold;
        }
        .card-subvalue {
            font-size: 10px;
            font-weight: 600;
            margin-top: 2px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 20px 0 10px 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
            text-transform: uppercase;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            font-size: 10px;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
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
                <div class="doc-title">Rapport Comptable OHADA</div>
                <div class="doc-meta">
                    Code unique : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Period Label Box -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; font-size: 10px; color: #475569;">
        <strong>Période du Rapport :</strong> {{ $periodLabel }}
    </div>

    <!-- Summary Statistics -->
    <table class="summary-container">
        <tr>
            <td style="width: 33%; padding-right: 10px;">
                <div class="summary-card" style="background-color: #ecfdf5; border-color: #a7f3d0;">
                    <div class="card-title" style="color: #065f46;">Classe 7 - Produits</div>
                    <div class="card-value" style="color: #047857;">${{ number_format($totalRevenus, 2) }}</div>
                    <div class="card-subvalue" style="color: #059669;">{{ number_format($totalRevenus * 2500, 0, ',', ' ') }} FC</div>
                </div>
            </td>
            <td style="width: 33%; padding: 0 5px;">
                <div class="summary-card" style="background-color: #fef2f2; border-color: #fca5a5;">
                    <div class="card-title" style="color: #991b1b;">Classe 6 - Charges</div>
                    <div class="card-value" style="color: #b91c1c;">${{ number_format($totalCharges, 2) }}</div>
                    <div class="card-subvalue" style="color: #dc2626;">{{ number_format($totalCharges * 2500, 0, ',', ' ') }} FC</div>
                </div>
            </td>
            <td style="width: 33%; padding-left: 10px;">
                <div class="summary-card" style="background-color: #f8fafc; border-color: #cbd5e1;">
                    <div class="card-title">Résultat Net</div>
                    <div class="card-value" style="color: {{ $resultat >= 0 ? '#059669' : '#dc2626' }};">
                        {{ $resultat >= 0 ? '+' : '' }}${{ number_format($resultat, 2) }}
                    </div>
                    <div class="card-subvalue" style="color: {{ $resultat >= 0 ? '#10b981' : '#f43f5e' }};">
                        {{ $resultat >= 0 ? '+' : '' }}{{ number_format($resultat * 2500, 0, ',', ' ') }} FC
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Class 7: Produits (Revenus) -->
    <div class="section-title">Tableau des Produits (Classe 7)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 30%;">Élève / Source</th>
                <th style="width: 25%;">Type de Frais</th>
                <th style="width: 15%; text-align: center;">Compte OHADA</th>
                <th style="width: 15%; text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revenus as $rev)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($rev['payment_date'])->format('d/m/Y') }}</td>
                    <td style="font-weight: bold;">
                        @if($rev['student'])
                            {{ $rev['student']['first_name'] }} {{ $rev['student']['last_name'] }}
                        @else
                            {{ $rev['beneficiary_name'] ?? 'N/A' }}
                        @endif
                    </td>
                    <td>{{ $rev['fee_type']['name'] ?? $rev['description'] ?? 'Autre produit' }}</td>
                    <td style="text-align: center; font-family: monospace; color: #4b5563;">
                        {{ $rev['ohada_account_code'] ?: '706' }}
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #047857;">
                        ${{ number_format($rev['amount'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px; color: #94a3b8; font-style: italic;">
                        Aucun produit enregistré.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Class 6: Charges (Dépenses) -->
    <div class="section-title">Tableau des Charges (Classe 6)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 30%;">Bénéficiaire</th>
                <th style="width: 25%;">Motif</th>
                <th style="width: 15%; text-align: center;">Compte OHADA</th>
                <th style="width: 15%; text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($charges as $chg)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($chg['payment_date'])->format('d/m/Y') }}</td>
                    <td style="font-weight: bold;">
                        @if($chg['teacher'])
                            {{ $chg['teacher']['first_name'] }} {{ $chg['teacher']['last_name'] }} (Enseignant)
                        @else
                            {{ $chg['beneficiary_name'] ?? 'N/A' }}
                        @endif
                    </td>
                    <td>{{ $chg['motif'] ?? $chg['description'] ?? 'Autre charge' }}</td>
                    <td style="text-align: center; font-family: monospace; color: #4b5563;">
                        {{ $chg['ohada_account_code'] ?: '601' }}
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #b91c1c;">
                        ${{ number_format($chg['amount'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px; color: #94a3b8; font-style: italic;">
                        Aucune charge enregistrée.
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
                    Rapport certifié et clôturé sous le Système Comptable OHADA.<br>
                    ID Unique : <strong>{{ $uniqueCode }}</strong>
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
