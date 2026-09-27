<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 40px 30px;
        }
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
            margin-bottom: 20px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .school-name {
            font-size: 15px;
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
            font-size: 15px;
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
        
        /* Summary Box */
        .summary-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-card {
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 8px;
        }
        .card-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 4px;
        }
        .card-value {
            font-size: 15px;
            font-weight: bold;
            color: #1e293b;
        }
        .card-subvalue {
            font-size: 9px;
            font-weight: 600;
            margin-top: 1px;
        }

        .filter-badge-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 9px;
            color: #475569;
            line-height: 1.4;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 15px 0 8px 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 3px;
            text-transform: uppercase;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: fixed;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            font-size: 9px;
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
            bottom: -20px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            font-size: 8px;
            color: #94a3b8;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
                    <img src="{{ $logoBase64 }}" style="max-height: 45px; margin-bottom: 6px; display: block;">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">
                    {{ $schoolAddress }}<br>
                    Tél: {{ $schoolPhone }} | Email: {{ $schoolEmail }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="doc-title">Rapport de Caisse</div>
                <div class="doc-meta">
                    Code unique : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Period & Active Filters Box -->
    <div class="filter-badge-box">
        <strong>Période du Rapport :</strong> {{ $periodLabel }}<br>
        <strong>Filtres appliqués :</strong> 
        Classe : <span>{{ $selectedClass }}</span> | 
        Type de Frais : <span>{{ $selectedFee }}</span> | 
        Méthode : <span>{{ $selectedMethod }}</span>
    </div>

    <!-- Summary Statistics Grid -->
    <table class="summary-container">
        <tr>
            <td style="width: 25%; padding-right: 5px;">
                <div class="summary-card" style="background-color: #f0fdf4; border-color: #bbf7d0;">
                    <div class="card-title" style="color: #166534;">Caisse Jour</div>
                    <div class="card-value" style="color: #15803d;">${{ number_format($totalJour, 2) }}</div>
                    <div class="card-subvalue" style="color: #166534;">{{ number_format($totalJour * 2500, 0, ',', ' ') }} FC</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 3px;">
                <div class="summary-card" style="background-color: #f0f9ff; border-color: #bae6fd;">
                    <div class="card-title" style="color: #075985;">Caisse Mois</div>
                    <div class="card-value" style="color: #0284c7;">${{ number_format($totalMois, 2) }}</div>
                    <div class="card-subvalue" style="color: #075985;">{{ number_format($totalMois * 2500, 0, ',', ' ') }} FC</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 3px;">
                <div class="summary-card" style="background-color: #faf5ff; border-color: #e9d5ff;">
                    <div class="card-title" style="color: #6b21a8;">Caisse Année</div>
                    <div class="card-value" style="color: #7e22ce;">${{ number_format($totalAnnee, 2) }}</div>
                    <div class="card-subvalue" style="color: #6b21a8;">{{ number_format($totalAnnee * 2500, 0, ',', ' ') }} FC</div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 5px;">
                <div class="summary-card" style="background-color: #fdf2f8; border-color: #fbcfe8;">
                    <div class="card-title" style="color: #9d174d;">Total Sélectionné</div>
                    <div class="card-value" style="color: #be185d;">${{ number_format($totalUSD, 2) }}</div>
                    <div class="card-subvalue" style="color: #9d174d;">{{ number_format($totalUSD * 2500, 0, ',', ' ') }} FC</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- List of Transactions -->
    <div class="section-title">Transactions d'Encaissement Détaillées ({{ count($payments) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 25%;">Élève</th>
                <th style="width: 15%;">Classe</th>
                <th style="width: 18%;">Type de Frais</th>
                <th style="width: 10%;">Méthode</th>
                <th style="width: 10%;">Référence</th>
                <th style="width: 10%; text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ isset($p['payment_date']) ? \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') : '' }}</td>
                    <td style="font-weight: bold;">
                        {{ $p['student']['first_name'] ?? '' }} {{ $p['student']['last_name'] ?? '' }}
                        <div style="font-size: 7px; color: #64748b; font-family: monospace; font-weight: normal; margin-top: 1px;">
                            Matricule: {{ $p['student']['matricule'] ?? 'N/A' }}
                        </div>
                    </td>
                    <td>{{ $p['student']['school_class']['name'] ?? 'N/A' }}</td>
                    <td style="color: #4f46e5; font-weight: 600;">{{ $p['fee_type']['name'] ?? 'N/A' }}</td>
                    <td>{{ $p['payment_method'] ?? 'Cash' }}</td>
                    <td style="font-family: monospace; font-size: 8px; color: #64748b;">{{ $p['reference'] }}</td>
                    <td style="text-align: right; font-weight: bold; color: #0f172a;">
                        ${{ number_format($p['amount'], 2) }}
                        <div style="font-size: 7px; color: #64748b; font-weight: normal; margin-top: 1px;">
                            {{ number_format($p['amount'] * 2500, 0, ',', ' ') }} FC
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 15px; color: #94a3b8; font-style: italic;">
                        Aucune transaction d'encaissement ne correspond aux critères de filtre.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Digital Signature Block with QR Code -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%;">
                <p style="font-size: 8px; color: #64748b; margin: 0; line-height: 1.4;">
                    Rapport de Caisse officiel Yeschool certifié conforme par le caissier responsable.<br>
                    Identifiant de sécurité : <strong>{{ $uniqueCode }}</strong>
                </p>
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="display: inline-block; text-align: center; width: 90px;">
                    @if($qrCodeBase64)
                        <img src="{{ $qrCodeBase64 }}" style="width: 70px; height: 70px; display: block; margin: 0 auto 3px auto;" alt="QR Code Signature">
                    @else
                        <div style="width: 70px; height: 70px; border: 1px dashed #cbd5e1; display: block; margin: 0 auto 3px auto; line-height: 70px; text-align: center; font-size: 8px; color: #94a3b8;">
                            [ QR CODE ]
                        </div>
                    @endif
                    <span style="font-size: 6px; color: #94a3b8; font-family: monospace; letter-spacing: 0.5px; display: block; text-align: center;">SIGNATURE DE SÉCURITÉ</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        {{ $footer }}
    </div>

</body>
</html>
