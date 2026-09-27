<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $establishment->name }}</title>
    <style>
        @page { margin: {{ $template === 'compact' ? '18px' : '28px' }}; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: {{ $template === 'compact' ? '8px' : '9px' }}; }
        .header { margin-bottom: 18px; padding: {{ $template === 'compact' ? '10px' : '16px' }}; }
        .modern .header { color: white; border-radius: 10px; background: #1d4ed8; }
        .classic .header { border: 2px solid #27272a; text-align: center; }
        .compact .header { border-bottom: 2px solid #2563eb; padding-inline: 0; }
        .logo { float: right; max-width: 72px; max-height: 48px; }
        h1 { margin: 0 0 4px; font-size: {{ $template === 'compact' ? '16px' : '20px' }}; }
        .meta { opacity: .78; }
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        th { padding: {{ $template === 'compact' ? '5px 4px' : '8px 6px' }}; color: white; background: {{ $template === 'classic' ? '#27272a' : '#2563eb' }}; font-size: 7.5px; text-align: left; text-transform: uppercase; }
        td { padding: {{ $template === 'compact' ? '4px' : '7px 6px' }}; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        .classic td { border: 1px solid #d4d4d8; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .footer { margin-top: 14px; color: #71717a; font-size: 7.5px; text-align: right; }
    </style>
</head>
<body class="{{ $template }}">
    <div class="header">
        @if($logoData)<img class="logo" src="{{ $logoData }}" alt="Logo">@endif
        <h1>{{ $title }}</h1>
        <div>{{ $establishment->name }}</div>
        <div class="meta">Édité le {{ now()->format('d/m/Y à H:i') }} · Modèle {{ ucfirst($template) }} · {{ count($rows) }} ligne(s)</div>
    </div>
    <table>
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headers) }}">Aucune donnée à exporter.</td></tr>@endforelse
        </tbody>
    </table>
    <div class="footer">{{ $establishment->document_footer ?: config('app.name') }}</div>
</body>
</html>
