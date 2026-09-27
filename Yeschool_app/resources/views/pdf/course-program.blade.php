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
            color: #6366f1;
            font-weight: bold;
            margin: 0;
            text-align: right;
        }
        .doc-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 5px;
            text-align: right;
            line-height: 1.4;
        }
        .program-details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .program-details-box td {
            border: none;
            font-size: 10px;
            color: #475569;
        }
        table.program-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        table.program-table th, table.program-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.program-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .color-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
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
            <td style="width: 65%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 50px; margin-bottom: 8px; display: block;">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">
                    {{ $schoolAddress }}<br>
                    Tél: {{ $schoolPhone }} | Email: {{ $schoolEmail }}
                </div>
            </td>
            <td style="width: 35%;">
                <div class="doc-title">Programme de Cours</div>
                <div class="doc-meta">
                    Code unique : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Context Details Box -->
    <div class="program-details-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%;"><strong>Classe :</strong> {{ $className }}</td>
                <td style="width: 50%; text-align: right;"><strong>Période :</strong> {{ $term }}</td>
            </tr>
        </table>
    </div>

    <!-- Courses Program Table -->
    <table class="program-table">
        <thead>
            <tr>
                <th style="width: 35%;">Matière</th>
                <th style="width: 35%;">Titulaire de Cours</th>
                <th style="width: 15%; text-align: center;">Cotation Max</th>
                <th style="width: 15%; text-align: center;">Coefficient</th>
            </tr>
        </thead>
        <tbody>
            @forelse($courses as $crs)
                <tr>
                    <td style="font-weight: bold; color: #1e1b4b;">
                        <span class="color-dot" style="background-color: {{ $crs['subject']['color'] ?? '#6366f1' }};"></span>
                        {{ $crs['subject']['name'] ?? 'N/A' }}
                    </td>
                    <td>
                        @if($crs['teacher'])
                            {{ $crs['teacher']['first_name'] }} {{ $crs['teacher']['last_name'] }}
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Aucun titulaire</span>
                        @endif
                    </td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold; color: #334155;">
                        {{ $crs['max_score'] }} pts
                    </td>
                    <td style="text-align: center; font-family: monospace; font-weight: bold; color: #334155;">
                        ×{{ $crs['coefficient'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8; font-style: italic;">
                        Aucun cours n'est encore programmé pour cette classe.
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
                    Document officiel certifié conforme et signé numériquement par la direction de l'établissement.<br>
                    ID de transaction : <strong>{{ $uniqueCode }}</strong>
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
