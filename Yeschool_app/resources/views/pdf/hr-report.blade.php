<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 0 0 5px 0;
        }
        .header-meta {
            font-size: 11px;
            color: #64748b;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .meta-table td {
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e1b4b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .grid td {
            width: 25%;
            padding: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
        }
        .card-val {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            margin-top: 5px;
        }
        .card-lbl {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .stats-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            border: 1px solid #e2e8f0;
        }
        .stats-table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
        }
        .report-body {
            font-size: 13px;
            color: #334155;
            margin-top: 20px;
        }
        .report-body h2 {
            font-size: 14px;
            color: #1e1b4b;
            margin-top: 25px;
            margin-bottom: 10px;
            border-left: 3px solid #4f46e5;
            padding-left: 8px;
        }
        .report-body p {
            margin-bottom: 15px;
            text-align: justify;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1 class="header-title">{{ $title }}</h1>
        <div class="header-meta">Rapport d'activité analytique généré par le portail RH de Yeschool</div>
    </div>

    <!-- Metadata Grid -->
    <table class="meta-table">
        <tr>
            <td style="width: 25%;"><strong>Auteur :</strong></td>
            <td style="width: 25%;">{{ $author }}</td>
            <td style="width: 25%;"><strong>Généré le :</strong></td>
            <td style="width: 25%;">{{ $generated_at }}</td>
        </tr>
        <tr>
            <td><strong>Effectif Global :</strong></td>
            <td>{{ $stats['total_employees'] }} collaborateurs</td>
            <td><strong>Taux de Présence :</strong></td>
            <td>{{ $stats['presence_rate'] }} %</td>
        </tr>
    </table>

    <!-- Key Metrics Section -->
    <div class="section-title">Indicateurs de Masse Salariale & Climat</div>
    <table class="grid" cellpadding="0" cellspacing="10">
        <tr>
            <td>
                <div class="card-lbl">Masse Salariale</div>
                <div class="card-val">{{ number_format($stats['total_salary'], 2, ',', ' ') }} $</div>
            </td>
            <td>
                <div class="card-lbl">Salaire Moyen</div>
                <div class="card-val">{{ number_format($stats['avg_salary'], 2, ',', ' ') }} $</div>
            </td>
            <td>
                <div class="card-lbl">Congés Approuvés</div>
                <div class="card-val">{{ $stats['leaves_approved'] }} / {{ $stats['leaves_total'] }}</div>
            </td>
            <td>
                <div class="card-lbl">Conflits Actifs</div>
                <div class="card-val" style="color: {{ $stats['conflicts_critical'] > 0 ? '#ef4444' : '#4f46e5' }}">{{ $stats['conflicts_active'] }} ({{ $stats['conflicts_critical'] }} crit.)</div>
            </td>
        </tr>
    </table>

    <!-- Specialty Breakdown Table -->
    <div class="section-title">Répartition par Spécialité / Fonction</div>
    <table class="stats-table">
        <thead>
            <tr>
                <th>Fonction / Rôle</th>
                <th style="width: 120px; text-align: right;">Nombre d'employés</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stats['specialties'] as $spec)
                <tr>
                    <td>{{ $spec['specialty'] }}</td>
                    <td style="text-align: right;">{{ $spec['count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Aucune donnée disponible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="page-break-before: always;"></div>

    <!-- Custom Written Bilan Section -->
    <div class="section-title">Analyse & Bilan de la Période</div>
    <div class="report-body">
        {!! $content !!}
    </div>

    <!-- Footer -->
    <div class="footer">
        Document confidentiel à usage interne uniquement - Généré automatiquement par Yeschool.
    </div>

</body>
</html>
