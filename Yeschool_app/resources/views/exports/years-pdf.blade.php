<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        
        /* Premium Header Styling */
        .school-header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .school-logo-cell {
            display: table-cell;
            width: 70px;
            vertical-align: middle;
        }
        .school-logo-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: #4f46e5;
            color: #ffffff;
            display: block;
            text-align: center;
            line-height: 60px;
            font-size: 24px;
            font-weight: 800;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1);
        }
        .school-logo-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .school-info-cell {
            display: table-cell;
            padding-left: 20px;
            vertical-align: middle;
        }
        .school-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        .school-meta {
            font-size: 11px;
            color: #64748b;
            margin: 0;
        }
        .school-contact-cell {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #64748b;
        }
        
        /* Modern Title Ribbon */
        .title-ribbon {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.15);
        }
        .title-ribbon h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .title-ribbon p {
            margin: 2px 0 0 0;
            font-size: 10px;
            opacity: 0.9;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 10px;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .year-name {
            font-size: 11px;
            font-weight: 700;
            color: #4f46e5;
        }
        .status-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #ecfdf5;
            color: #065f46;
        }
        .status-inactive {
            background-color: #f1f5f9;
            color: #475569;
        }

        /* Print Footer details */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
        }
    </style>
</head>
<body>

    <!-- School Information Block -->
    <div class="school-header">
        <div class="school-logo-cell">
            @if($school && $school->logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($school->logo) }}" class="school-logo-img">
            @else
                <div class="school-logo-placeholder">
                    {{ substr($school ? $school->name : 'YS', 0, 2) }}
                </div>
            @endif
        </div>
        <div class="school-info-cell">
            <h1 class="school-name">{{ $school ? $school->name : 'Yeschool Établissement' }}</h1>
            <p class="school-meta">{{ $school ? $school->address : 'Adresse non spécifiée' }}</p>
        </div>
        <div class="school-contact-cell">
            <strong>Tél:</strong> {{ $school ? $school->phone : 'N/A' }}<br>
            <strong>Email:</strong> {{ $school ? $school->email : 'N/A' }}<br>
            <strong>Date:</strong> {{ date('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Title Ribbon -->
    <div class="title-ribbon">
        <h2>{{ $title }}</h2>
        <p>Total d'années scolaires configurées : {{ $years->count() }}</p>
    </div>

    <!-- Years List Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Année Scolaire</th>
                <th style="width: 20%;">Date de Début</th>
                <th style="width: 20%;">Date de Fin</th>
                <th style="width: 15%; text-align: center;">Élèves Inscrits</th>
                <th style="width: 15%; text-align: center;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($years as $y)
                <tr>
                    <td class="year-name">{{ $y->name }}</td>
                    <td>{{ $y->start_date ? $y->start_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $y->end_date ? $y->end_date->format('d/m/Y') : '-' }}</td>
                    <td style="text-align: center;"><strong>{{ $y->students_count }}</strong></td>
                    <td style="text-align: center;">
                        @if($y->is_active)
                            <span class="status-badge status-active">Active (Actuelle)</span>
                        @else
                            <span class="status-badge status-inactive">Inactive</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-left">Document officiel généré par Yeschool</div>
        <div class="footer-right">Page 1 de 1</div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
