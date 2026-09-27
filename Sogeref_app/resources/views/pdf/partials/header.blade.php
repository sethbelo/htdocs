{{-- En-tête SOGEREF : logo, coordonnées et titre du document --}}
@php $company = $company ?? company(); @endphp
<div class="pdf-header">
    <table>
        <tr>
            <td style="width: 46%; vertical-align: top;">
                <img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 54px;">
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div style="font-size: 19px; font-weight: bold; color: #104A63; letter-spacing: .5px;">{{ $docTitle ?? '' }}</div>
                @isset($docReference)<div style="font-size: 11px; font-weight: bold; color: #F7313A; margin-top: 2px;">{{ $docReference }}</div>@endisset
                @isset($docSubtitle)<div class="muted" style="margin-top: 2px;">{!! $docSubtitle !!}</div>@endisset
            </td>
        </tr>
    </table>
    <table class="brand-rule" style="margin-top: 8px;"><tr><td style="width: 70%; background: #104A63;"></td><td style="width: 15%; background: #FFCE31;"></td><td style="width: 10%; background: #3ACECE;"></td><td style="width: 5%; background: #F7313A;"></td></tr></table>
</div>
