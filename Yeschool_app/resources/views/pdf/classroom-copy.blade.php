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
            font-size: 14px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 0;
        }
        .school-info {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.4;
            margin-top: 4px;
        }
        .doc-title {
            font-size: 14px;
            color: #6366f1;
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

        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            width: 100%;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            border: none;
            padding: 2px 0;
            font-size: 9.5px;
            color: #475569;
        }
        .meta-label {
            font-weight: bold;
            color: #1e293b;
        }

        .score-badge {
            font-size: 18px;
            font-weight: 900;
            color: #4f46e5;
            background-color: #e0e7ff;
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid #c7d2fe;
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

        .question-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
            background-color: #ffffff;
        }
        .question-header {
            font-weight: bold;
            font-size: 10px;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .question-type-tag {
            font-size: 7.5px;
            text-transform: uppercase;
            font-weight: bold;
            color: #4f46e5;
            background-color: #f0fdf4;
            padding: 1px 4px;
            border-radius: 3px;
            margin-left: 5px;
        }
        .option-list {
            margin-top: 5px;
            padding-left: 10px;
        }
        .option-item {
            font-size: 9px;
            padding: 2px 0;
            color: #475569;
        }
        .option-correct {
            font-weight: bold;
            color: #16a34a;
        }
        .answer-box {
            margin-top: 8px;
            padding: 6px 10px;
            background-color: #f1f5f9;
            border-radius: 6px;
            font-size: 9px;
            color: #334155;
            border-left: 3px solid #cbd5e1;
        }

        .feedback-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 9.5px;
            color: #78350f;
        }

        .parent-box {
            border: 2px dashed #cbd5e1;
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #faf5ff;
            margin-top: 20px;
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
    </style>
</head>
<body>

    <!-- School Header -->
    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 45px; margin-bottom: 6px; display: block;">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">
                    {{ $schoolAddress }}<br>
                    Tél: {{ $schoolPhone }} | Email: {{ $schoolEmail }}
                </div>
            </td>
            <td style="width: 35%;">
                <div class="doc-title">Copie d'Évaluation</div>
                <div class="doc-meta">
                    Code de sécurité : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Meta Details Box -->
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width: 15%;" class="meta-label">Élève :</td>
                <td style="width: 50%;">{{ $student['first_name'] }} {{ $student['last_name'] }} (Matricule: {{ $student['matricule'] ?? 'N/A' }})</td>
                <td style="width: 35%; text-align: right;" rowspan="4">
                    <div style="text-align: center; width: 100%;">
                        <span style="font-size: 8px; font-weight: bold; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 4px;">Note Obtenue</span>
                        <div class="score-badge">
                            {{ $submission['score'] !== null ? $submission['score'] : 'A corriger' }} / {{ $assessment['max_score'] }}
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="meta-label">Classe :</td>
                <td>{{ $student['school_class']['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Cours :</td>
                <td>{{ $subject['name'] }}</td>
            </tr>
            <tr>
                <td class="meta-label">Enseignant :</td>
                <td>{{ $teacher['first_name'] ?? '' }} {{ $teacher['last_name'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Type & Titre :</td>
                <td colspan="2"><span style="font-weight: bold; color: #4f46e5;">{{ $assessment['type'] }}</span> - {{ $assessment['title'] }}</td>
            </tr>
        </table>
    </div>

    <!-- Feedback Box -->
    @if(!empty($submission['teacher_comments']))
        <div class="feedback-box">
            <strong>Commentaire de l'enseignant :</strong><br>
            {{ $submission['teacher_comments'] }}
        </div>
    @endif

    <!-- Questions Details -->
    <div class="section-title">Contenu du Devoir & Réponses de l'élève</div>
    
    @forelse($assessment['questions'] ?: [] as $index => $q)
        @php
            // Find student's answer for this question
            $studentAns = '';
            foreach ($submission['answers'] ?: [] as $ans) {
                if ($ans['question_id'] === $q['id']) {
                    $studentAns = $ans['student_answer'];
                    break;
                }
            }
        @endphp
        
        <div class="question-card">
            <div class="question-header">
                Question {{ $index + 1 }} : {{ $q['question'] }}
                <span class="question-type-tag">
                    @if($q['type'] === 'mcq') Choix Multiple
                    @elseif($q['type'] === 'text') Question de texte
                    @else Dessin/Schéma
                    @endif
                </span>
            </div>

            <!-- If MCQ options -->
            @if($q['type'] === 'mcq' && !empty($q['options']))
                <div class="option-list">
                    @foreach($q['options'] as $opt)
                        @php
                            $isCorrect = $opt === $q['correct_option'];
                            $isSelected = $opt === $studentAns;
                        @endphp
                        <div class="option-item @if($isCorrect) option-correct @endif">
                            [ @if($isSelected) X @else &nbsp; @endif ] {{ $opt }}
                            @if($isCorrect) <span style="font-size: 7.5px; color: #16a34a; font-weight: bold;">(Bonne réponse)</span> @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Image/Drawing Reference -->
            @if(!empty($q['image_url']))
                <div style="margin: 8px 0; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px; display: inline-block;">
                    <span style="font-size: 8px; color: #64748b; display: block; margin-bottom: 2px;">Image / Dessin de référence :</span>
                    <img src="{{ $q['image_url'] }}" style="max-height: 80px; max-width: 250px;">
                </div>
            @endif

            <!-- Answer Box -->
            <div class="answer-box">
                <strong>Réponse de l'élève :</strong> 
                @if(empty($studentAns))
                    <span style="color: #94a3b8; font-style: italic;">Pas de réponse saisie.</span>
                @else
                    {{ $studentAns }}
                @endif
            </div>
        </div>
    @empty
        <div style="padding: 10px; text-align: center; color: #94a3b8; font-style: italic;">
            Aucune question n'a été ajoutée à ce devoir.
        </div>
    @endforelse

    <!-- Parent Signature Box (Smart/Editable layout) -->
    <div class="parent-box">
        <strong style="color: #6b21a8; font-size: 11px;">Avis & Signature du Parent (Éditable / Signature) :</strong><br><br>
        <div style="font-size: 8.5px; color: #64748b; margin-bottom: 8px;">Veuillez remplir cet encadré pour signature et commentaires des parents.</div>
        <div style="border-bottom: 1px solid #94a3b8; height: 35px; margin-bottom: 6px;"></div>
        <span style="font-size: 8px; color: #94a3b8; font-style: italic;">Date de signature : ____ / ____ / ________</span>
    </div>

    <!-- Security Signature Block with QR Code -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%;">
                <p style="font-size: 8px; color: #64748b; margin: 0; line-height: 1.4;">
                    Ce document est une copie d'évaluation officielle certifiée par Yeschool Classroom.<br>
                    ID Unique de validation : <strong>{{ $uniqueCode }}</strong>
                </p>
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="display: inline-block; text-align: center; width: 90px;">
                    @if($qrCodeBase64)
                        <img src="{{ $qrCodeBase64 }}" style="width: 65px; height: 65px; display: block; margin: 0 auto 3px auto;" alt="QR Code Validation">
                    @else
                        <div style="width: 65px; height: 65px; border: 1px dashed #cbd5e1; display: block; margin: 0 auto 3px auto; line-height: 65px; text-align: center; font-size: 8px; color: #94a3b8;">
                            [ QR CODE ]
                        </div>
                    @endif
                    <span style="font-size: 6px; color: #94a3b8; font-family: monospace; letter-spacing: 0.5px; display: block; text-align: center;">VÉRIFICATION CLASSRoom</span>
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
