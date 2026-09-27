{{-- ═══ MODÈLE « ÉPURÉ » — noir & blanc, grande typographie ═══ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @include('pdf.partials.styles')
    <style>
        @page { margin: 46px 50px 70px 50px; }
        body { color: #111; }
        .lines th { background: #fff; color: #111; border-bottom: 1.5px solid #111; padding-left: 0; }
        .lines td { padding-left: 0; }
        .totals .grand td { background: #fff; color: #111; border-top: 1.5px solid #111; font-size: 14px; }
        .min-footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #777; }
    </style>
</head>
<body>
<div class="min-footer"><table><tr><td>{{ $company->document_footer }}</td><td class="right">{{ $document->number }} · <span class="pagenum"></span></td></tr></table></div>
<table>
    <tr>
        <td style="vertical-align: bottom;"><div style="font-size: 34px; font-weight: bold; letter-spacing: -1px;">{{ ucfirst(mb_strtolower($docTitle)) }}</div><div style="font-size: 12px;">{{ $document->number }} — {{ $document->issue_date->translatedFormat('j F Y') }}</div></td>
        <td class="right" style="vertical-align: bottom;"><img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 44px;"></td>
    </tr>
</table>
<table style="margin-top: 30px;">
    <tr>
        <td style="width: 50%; vertical-align: top;"><div class="label">De</div><strong>{{ $company->name }}</strong><br>{{ $address }}<br>{{ $company->phone }} · {{ $company->email }}</td>
        <td style="width: 50%; vertical-align: top;"><div class="label">Pour</div><strong>{{ $contact->display_name }}</strong><br>{{ $contactAddress }}<br>{{ $contact->phone }}</td>
    </tr>
</table>
<div style="margin-top: 26px;">@include('pdf.documents._lines')</div>
@include('pdf.documents._totals')
@if ($terms)<p class="small" style="margin-top: 20px;">{!! nl2br(e($terms)) !!}</p>@endif
</body>
</html>
