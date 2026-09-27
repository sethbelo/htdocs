<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Présence et d'Absence - Yeschool</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #475569;
            width: 120px;
        }
        .meta-value {
            color: #0f172a;
        }
        /* Stats Cards */
        .stats-container {
            margin-bottom: 25px;
            width: 100%;
        }
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        .stats-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .stats-val {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
        }
        .stats-lbl {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-present {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-leave {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-absent {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .notes-text {
            color: #64748b;
            font-style: italic;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="header-title">Yeschool</div>
                    <div class="header-subtitle">Rapport d'Activité et Présence du Personnel</div>
                </td>
                <td style="text-align: right; font-size: 10px; color: #64748b;">
                    Généré le {{ date('d/m/Y à H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Période :</td>
            <td class="meta-value">{{ $period }}</td>
            <td class="meta-label" style="text-align: right; padding-right: 15px;">Filtre Enseignant :</td>
            <td class="meta-value">{{ $teacher_name }}</td>
        </tr>
    </table>

    <div class="stats-container">
        <table class="stats-table">
            <tr>
                <td width="25%">
                    <div class="stats-card" style="border-left: 4px solid #10b981;">
                        <div class="stats-lbl">Présences (Jours)</div>
                        <div class="stats-val">{{ $stats['present'] }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="stats-card" style="border-left: 4px solid #3b82f6;">
                        <div class="stats-lbl">Absences Justifiées</div>
                        <div class="stats-val">{{ $stats['absent_justified'] }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="stats-card" style="border-left: 4px solid #ef4444;">
                        <div class="stats-lbl">Absences Non Justifiées</div>
                        <div class="stats-val">{{ $stats['absent_unjustified'] }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="stats-card" style="border-left: 4px solid #6366f1;">
                        <div class="stats-lbl">Total Heures Travaillées</div>
                        <div class="stats-val">{{ number_format($stats['total_hours'], 1) }} h</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="20%">Collaborateur</th>
                <th width="15%">Statut</th>
                <th width="10%">Arrivée</th>
                <th width="10%">Départ</th>
                <th width="25%">Motif / Justification d'absence</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $rec)
                <tr>
                    <td style="font-weight: bold; text-transform: capitalize;">{{ $rec['date'] }}</td>
                    <td>{{ $rec['teacher_name'] }}</td>
                    <td>
                        @if($rec['status'] === 'Présent')
                            <span class="badge badge-present">Présent</span>
                        @elseif(strpos($rec['status'], 'Absent (Justifié') !== false || strpos($rec['status'], 'Absent (') !== false)
                            <span class="badge badge-leave">Justifié</span>
                        @else
                            <span class="badge badge-absent">Non Justifié</span>
                        @endif
                    </td>
                    <td style="font-family: monospace;">{{ $rec['check_in'] }}</td>
                    <td style="font-family: monospace;">{{ $rec['check_out'] }}</td>
                    <td class="notes-text">{{ $rec['notes'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">
                        Aucun relevé d'activité ou d'absence trouvé sur cette période.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
