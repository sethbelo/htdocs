<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuaire des Collaborateurs - Yeschool</title>
    <style>
        @page {
            margin: 90px 30px 60px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.4;
        }
        header {
            position: fixed;
            top: -70px;
            left: 0px;
            right: 0px;
            height: 50px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }
        footer {
            position: fixed; 
            bottom: -40px; 
            left: 0px; 
            right: 0px;
            height: 25px;
            font-size: 8px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        /* Stats Summary */
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 20px;
        }
        .stats-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .stats-val {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .stats-lbl {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 9999px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-candidate {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-other {
            background-color: #f1f5f9;
            color: #475569;
        }
        .pagenum:before {
            content: counter(page);
        }
        .page-total:before {
            content: counter(pages);
        }
    </style>
</head>
<body>

    <header>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td>
                    <div class="header-title">Yeschool</div>
                    <div class="header-subtitle">Annuaire & Recrutement - Liste des Collaborateurs</div>
                </td>
                <td style="text-align: right; font-size: 9px; color: #64748b; vertical-align: bottom;">
                    Filtre : <span style="font-weight: bold; text-transform: uppercase;">{{ $status_filter === 'all' ? 'Tous' : $status_filter }}</span>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td>Yeschool - Fiche générée le {{ $date_generated }}</td>
                <td style="text-align: right;">Page <span class="pagenum"></span> sur <span class="page-total"></span></td>
            </tr>
        </table>
    </footer>

    <!-- Stats summary cards -->
    <table class="stats-table">
        <tr>
            <td width="25%">
                <div class="stats-card" style="border-left: 3px solid #6366f1;">
                    <div class="stats-lbl">Total Collaborateurs</div>
                    <div class="stats-val">{{ $stats['total'] }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="stats-card" style="border-left: 3px solid #10b981;">
                    <div class="stats-lbl">Actifs</div>
                    <div class="stats-val">{{ $stats['actif'] }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="stats-card" style="border-left: 3px solid #f59e0b;">
                    <div class="stats-lbl">Candidats</div>
                    <div class="stats-val">{{ $stats['candidat'] }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="stats-card" style="border-left: 3px solid #ef4444;">
                    <div class="stats-lbl">Inactifs / Anciens</div>
                    <div class="stats-val">{{ $stats['inactif'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Main List Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="20%">Collaborateur</th>
                <th width="20%">Email</th>
                <th width="15%">Téléphone</th>
                <th width="20%">Spécialité / Rôle</th>
                <th width="12%">Statut</th>
                <th width="13%">Date Recrutement</th>
            </tr>
        </thead>
        <tbody>
            @forelse($personnel as $p)
                <tr>
                    <td style="font-weight: bold;">{{ $p->first_name }} {{ $p->last_name }}</td>
                    <td>{{ $p->email }}</td>
                    <td>{{ $p->phone ?: '-' }}</td>
                    <td>{{ $p->specialty ?: '-' }}</td>
                    <td>
                        @if($p->status === 'Actif')
                            <span class="badge badge-active">Actif</span>
                        @elseif($p->status === 'Candidat')
                            <span class="badge badge-candidate">Candidat</span>
                        @elseif($p->status === 'Inactif' || $p->status === 'Ancien')
                            <span class="badge badge-inactive">Inactif</span>
                        @else
                            <span class="badge badge-other">{{ $p->status }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $p->contract_signed_at ? \Carbon\Carbon::parse($p->contract_signed_at)->format('d/m/Y') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 25px;">
                        Aucun collaborateur trouvé pour ce filtre.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
