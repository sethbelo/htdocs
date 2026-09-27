<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de Préparation - {{ $noteTitle }}</title>
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
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .meta-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-box td {
            border: none;
            font-size: 10px;
            color: #475569;
            padding: 4px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #1e293b;
            width: 120px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #4f46e5;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .content-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 20px;
            border-radius: 12px;
            min-height: 350px;
            font-size: 11px;
            line-height: 1.6;
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
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td>
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 55px; margin-bottom: 8px;">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">{{ $schoolAddress }}</div>
            </td>
            <td>
                <div class="doc-title">Fiche de Préparation</div>
                <div class="doc-meta">
                    <strong>Réf:</strong> NOTE-{{ $noteId }}<br>
                    <strong>Généré le:</strong> {{ date('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Metadata Section -->
    <div class="meta-box">
        <table>
            <tr>
                <td class="meta-label">Enseignant :</td>
                <td>M. {{ $teacherName }}</td>
                <td class="meta-label">Classe :</td>
                <td>{{ $className }}</td>
            </tr>
            <tr>
                <td class="meta-label">Matière :</td>
                <td>{{ $subjectName }}</td>
                <td class="meta-label">Date de la leçon :</td>
                <td>{{ \Carbon\Carbon::parse($lessonDate)->translatedFormat('d M Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Heures de cours :</td>
                <td>{{ $lessonHours }}</td>
                <td class="meta-label">Sujet du cours :</td>
                <td><strong>{{ $lessonTitle }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Preparation Note Body -->
    <div class="section-title">Contenu & Planification Pédagogique</div>
    <div class="content-box">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 13px; font-weight: bold;">
            {{ $noteTitle }}
        </h3>
        <div style="margin-top: 12px;">
            {!! $noteContent !!}
        </div>
    </div>

    <!-- Footer Section -->
    <div class="footer">
        {{ $footer }}
    </div>

</body>
</html>
