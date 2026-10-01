{{-- ═══ BON DE SORTIE « ÉPURÉ » — noir et blanc, sans prix ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de sortie {{ $voucher->number }}</title>
    <style>
        @page { margin: 46px 50px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #111; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #555; }
        .lines th { border-bottom: 1.5px solid #111; text-align: left; font-size: 8.5px; text-transform: uppercase; padding: 5px 4px; }
        .lines td { border-bottom: .5px solid #bbb; padding: 5px 4px; }
        .right { text-align: right; } .bold { font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td><div style="font-size: 15px; font-weight: bold; letter-spacing: 2px;">{{ mb_strtoupper($company->name) }}</div><div class="label">{{ $company->phone }} · {{ $company->email }}</div></td>
            <td class="right"><div style="font-size: 19px; font-weight: bold; letter-spacing: 2px;">BON DE SORTIE</div><div>N° {{ $voucher->number }} · {{ $voucher->voucher_date->format('d/m/Y') }}</div></td>
        </tr>
    </table>
    <hr style="border: 0; border-top: 1.5px solid #111; margin: 14px 0;">
    <table>
        <tr><td class="label" style="width: 24%;">Bénéficiaire</td><td class="bold">{{ $voucher->beneficiaryName() }}</td><td class="label" style="width: 16%;">Magasin</td><td>{{ $voucher->warehouse?->name ?? '—' }}</td></tr>
        <tr><td class="label" style="padding-top: 5px;">Motif</td><td colspan="3" style="padding-top: 5px;">{{ $voucher->purpose }}{{ $voucher->site ? ' — chantier '.$voucher->site->code : '' }}</td></tr>
    </table>
    <table class="lines" style="margin-top: 16px;">
        <thead><tr><th style="width: 6%;">#</th><th>Désignation</th><th style="width: 12%;">Unité</th><th class="right" style="width: 14%;">Quantité</th></tr></thead>
        <tbody>
            @foreach ($voucher->lines as $line)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $line->description }}</td><td>{{ $line->unit }}</td><td class="right bold">{{ qty($line->quantity) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table style="margin-top: 40px;">
        <tr>
            @foreach (['Approuvé par' => $voucher->approver?->name, 'Remis par' => $voucher->executor?->name, 'Reçu par' => null] as $role => $name)
                <td style="width: 31%;"><div class="label">{{ $role }}</div><div class="bold" style="min-height: 14px;">{{ $name }}</div><div style="border-bottom: .5px solid #111; height: 40px;"></div></td>
                @if (! $loop->last)<td style="width: 3.5%;"></td>@endif
            @endforeach
        </tr>
    </table>
</body>
</html>
