{{-- Badge visiteur SOGEREF (carte 86 × 54 mm) --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Badge {{ $visitor->badge_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #1a2733; }
        table { width: 100%; border-collapse: collapse; }
        .top td { background: #104A63; padding: 7px 10px; vertical-align: middle; }
        .rule td { height: 3px; padding: 0; }
        .label { font-size: 6px; color: #667c8c; text-transform: uppercase; letter-spacing: .7px; }
        .name { font-size: 13px; font-weight: bold; color: #104A63; line-height: 1.15; }
        .muted { color: #667c8c; }
    </style>
</head>
<body>
    <table class="top"><tr>
        <td style="width: 60%;"><img src="{{ public_path('brand/logo-horizontal-white.png') }}" style="height: 20px;"></td>
        <td style="text-align: right; color: #FFCE31; font-size: 11px; font-weight: bold; letter-spacing: 1.5px;">VISITEUR</td>
    </tr></table>
    <table class="rule"><tr><td style="width: 70%; background: #104A63;"></td><td style="width: 15%; background: #FFCE31;"></td><td style="width: 10%; background: #3ACECE;"></td><td style="width: 5%; background: #F7313A;"></td></tr></table>
    <table style="margin-top: 6px;"><tr>
        <td style="padding: 0 10px; vertical-align: top;">
            <div class="name">{{ \Illuminate\Support\Str::limit($visitor->full_name, 34) }}</div>
            @if ($visitor->organization)<div style="font-size: 8px; margin-top: 1px;">{{ \Illuminate\Support\Str::limit($visitor->organization, 40) }}</div>@endif
            <div class="label" style="margin-top: 5px;">Personne visitée</div>
            <div style="font-size: 8px; font-weight: bold;">{{ $visitor->host?->name ?? '—' }}</div>
            <div class="label" style="margin-top: 3px;">Entrée</div>
            <div style="font-size: 8px;">{{ $visitor->checked_in_at->format('d/m/Y à H:i') }}</div>
        </td>
        <td style="width: 66px; padding-right: 10px; vertical-align: top; text-align: center;">
            <div style="border: 2px solid #FFCE31; border-radius: 6px; padding: 6px 0;">
                <div class="label">Badge</div>
                <div style="font-size: 15px; font-weight: bold; color: #F7313A;">{{ $visitor->badge_number ?: '—' }}</div>
            </div>
        </td>
    </tr></table>
    <div class="muted" style="position: absolute; bottom: 5px; left: 10px; right: 10px; font-size: 5.5px;">{{ $company?->name ?? 'SOGEREF' }} · Badge à porter de façon visible et à restituer à la réception en sortant.</div>
</body>
</html>
