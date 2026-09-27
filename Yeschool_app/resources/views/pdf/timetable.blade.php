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
        table.timetable-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        table.timetable-table th, table.timetable-table td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.timetable-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center;
        }
        .event-card {
            margin-bottom: 5px;
            padding: 6px;
            border-radius: 6px;
            font-size: 9px;
            border-left: 3px solid #6366f1;
            background-color: #f5f3ff;
        }
        .event-title {
            font-weight: bold;
            color: #1e1b4b;
        }
        .event-meta {
            font-size: 8px;
            color: #64748b;
            margin-top: 3px;
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
                <div class="doc-title">{{ $title }}</div>
                <div class="doc-meta">
                    Code de sécurité : <strong>{{ $uniqueCode }}</strong><br>
                    Date d'édition : {{ date('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    @if($viewMode === 'weekly_grid')
        <!-- Weekly Grid Table -->
        <table class="timetable-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Heures</th>
                    @foreach($days as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $time)
                    <tr>
                        <td style="font-weight: bold; background-color: #f8fafc; text-align: center; font-size: 10px; vertical-align: middle;">
                            {{ $time }}
                        </td>
                        @foreach($days as $day)
                            <td>
                                @if(isset($schedules[$day]))
                                    @php
                                        $currentHour = (int)substr($time, 0, 2);
                                        $eventsInSlot = collect($schedules[$day])->filter(function($event) use ($currentHour) {
                                            $startH = (int)substr($event['start_time'], 0, 2);
                                            $endH = (int)substr($event['end_time'], 0, 2);
                                            return $currentHour >= $startH && $currentHour < $endH;
                                        });
                                    @endphp
                                    @foreach($eventsInSlot as $event)
                                        <div class="event-card" style="border-left-color: {{ $event['subject']['color'] ?? '#6366f1' }}; background-color: {{ ($event['subject']['color'] ?? '#6366f1') }}15;">
                                            <div class="event-title">{{ $event['subject']['name'] }}</div>
                                            <div class="event-meta">
                                                {{ substr($event['start_time'], 0, 5) }} - {{ substr($event['end_time'], 0, 5) }}<br>
                                                @if($event['teacher'])
                                                    Prof: {{ $event['teacher']['first_name'] }} {{ $event['teacher']['last_name'] }}<br>
                                                @endif
                                                @if($event['room'])
                                                    Salle: {{ $event['room'] }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Gantt view: Rows by Classes or Teachers -->
        <table class="timetable-table">
            <thead>
                <tr>
                    <th style="width: 150px;">{{ $ganttType === 'classes' ? 'Classes' : 'Enseignants' }}</th>
                    <th>Cours du {{ $activeDayName }}</th>
                </tr>
            </thead>
            <tbody>
                @if($ganttType === 'classes')
                    @foreach($schoolClasses as $c)
                        @php
                            $rowSchedules = collect($schedules)->where('school_class_id', $c['id']);
                        @endphp
                        <tr>
                            <td style="font-weight: bold; background-color: #f8fafc; font-size: 10px; vertical-align: middle;">
                                {{ $c['name'] }} ({{ $c['level'] }})
                            </td>
                            <td>
                                @if($rowSchedules->isEmpty())
                                    <span style="color: #cbd5e1; font-style: italic;">Aucun cours prévu</span>
                                @else
                                    @foreach($rowSchedules as $sched)
                                        <div class="event-card" style="display: inline-block; width: 45%; margin-right: 10px; margin-bottom: 5px; vertical-align: top; border-left-color: {{ $sched['subject']['color'] ?? '#6366f1' }}; background-color: {{ ($sched['subject']['color'] ?? '#6366f1') }}15;">
                                            <div class="event-title">{{ $sched['subject']['name'] }}</div>
                                            <div class="event-meta">
                                                Heure: {{ substr($sched['start_time'], 0, 5) }} - {{ substr($sched['end_time'], 0, 5) }}<br>
                                                @if($sched['teacher'])
                                                    Prof: {{ $sched['teacher']['first_name'] }} {{ $sched['teacher']['last_name'] }}<br>
                                                @endif
                                                @if($sched['room'])
                                                    Salle: {{ $sched['room'] }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    @foreach($teachers as $t)
                        @php
                            $rowSchedules = collect($schedules)->where('teacher_id', $t['id']);
                        @endphp
                        <tr>
                            <td style="font-weight: bold; background-color: #f8fafc; font-size: 10px; vertical-align: middle;">
                                {{ $t['first_name'] }} {{ $t['last_name'] }}
                            </td>
                            <td>
                                @if($rowSchedules->isEmpty())
                                    <span style="color: #cbd5e1; font-style: italic;">Aucun cours prévu</span>
                                @else
                                    @foreach($rowSchedules as $sched)
                                        <div class="event-card" style="display: inline-block; width: 45%; margin-right: 10px; margin-bottom: 5px; vertical-align: top; border-left-color: {{ $sched['subject']['color'] ?? '#6366f1' }}; background-color: {{ ($sched['subject']['color'] ?? '#6366f1') }}15;">
                                            <div class="event-title">{{ $sched['subject']['name'] }}</div>
                                            <div class="event-meta">
                                                Heure: {{ substr($sched['start_time'], 0, 5) }} - {{ substr($sched['end_time'], 0, 5) }}<br>
                                                Classe: {{ $sched['school_class']['name'] ?? 'N/A' }}<br>
                                                @if($sched['room'])
                                                    Salle: {{ $sched['room'] }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    @endif

    <!-- Digital Signature Block with QR Code -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%;">
                <p style="font-size: 9px; color: #64748b; margin: 0; line-height: 1.4;">
                    Document certifié conforme et signé numériquement par la direction de l'établissement.<br>
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
