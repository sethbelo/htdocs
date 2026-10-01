{{-- ═══ BON DE SORTIE « DUPLICATA » — exemplaires magasin et demandeur sur une page ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de sortie {{ $voucher->number }}</title>
    @include('pdf.partials.styles')
    <style>
        @page { margin: 30px 38px; }
        .copy { border: 1.5px solid #104A63; border-radius: 8px; padding: 10px 14px; height: 345px; overflow: hidden; }
        .copy-tag { background: #3ACECE; color: #104A63; font-size: 8px; font-weight: bold; letter-spacing: 1px; padding: 2px 8px; border-radius: 8px; }
        .cut { border-top: 1.5px dashed #8a9dac; margin: 16px 0; }
        .lines th, .lines td { padding: 4px 6px; }
    </style>
</head>
<body>
    @php $lines = $voucher->lines->take(8); @endphp
    @foreach (['EXEMPLAIRE MAGASIN', 'EXEMPLAIRE DEMANDEUR'] as $copy)
        <div class="copy">
            <table>
                <tr>
                    <td style="width: 50%;"><img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 34px;"></td>
                    <td class="right"><span class="copy-tag">{{ $copy }}</span><br><strong class="navy" style="font-size: 13px;">BON DE SORTIE N° {{ $voucher->number }}</strong><br><span class="muted small">{{ $voucher->voucher_date->format('d/m/Y') }} · {{ $voucher->warehouse?->name ?? 'Magasin' }}</span></td>
                </tr>
            </table>
            <p class="small" style="margin: 6px 0;"><strong>{{ $voucher->beneficiaryName() }}</strong> — {{ $voucher->purpose }}{{ $voucher->site ? ' · chantier '.$voucher->site->code : '' }}</p>
            <table class="lines small">
                <thead><tr><th>Désignation</th><th style="width: 12%;">Unité</th><th class="right" style="width: 14%;">Qté</th></tr></thead>
                <tbody>
                    @foreach ($lines as $line)<tr><td>{{ $line->description }}</td><td>{{ $line->unit }}</td><td class="right bold">{{ qty($line->quantity) }}</td></tr>@endforeach
                    @if ($voucher->lines->count() > $lines->count())<tr><td colspan="3" class="muted">+ {{ $voucher->lines->count() - $lines->count() }} autre(s) ligne(s) — voir bon standard</td></tr>@endif
                </tbody>
            </table>
            <table style="margin-top: 8px;">
                <tr>
                    @foreach (['Approuvé' => $voucher->approver?->name, 'Remis' => $voucher->executor?->name, 'Reçu' => null] as $role => $name)
                        <td style="width: 32%;"><div class="label">{{ $role }} {{ $name ? '· '.$name : '' }}</div><div class="signature" style="height: 40px; margin-top: 2px;"></div></td>
                        @if (! $loop->last)<td style="width: 2%;"></td>@endif
                    @endforeach
                </tr>
            </table>
        </div>
        @if ($loop->first)<div class="cut"></div>@endif
    @endforeach
</body>
</html>
