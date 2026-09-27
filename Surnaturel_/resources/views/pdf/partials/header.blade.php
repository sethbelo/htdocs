{{-- En-tête : logo, titre et référence du document, filet CMJN --}}
<div class="pdf-header">
    <table>
        <tr>
            <td style="width: 48%; vertical-align: middle;">
                @if ($company['logo'])<img src="{{ $company['logo'] }}" style="height: {{ ($format ?? 'a4') === 'a5' ? '36pt' : '44pt' }};">@else<span class="bold" style="font-size: 16pt;">{{ $company['name'] }}</span>@endif
            </td>
            <td style="vertical-align: middle; text-align: right;">
                <div style="font-size: {{ ($format ?? 'a4') === 'a5' ? '13pt' : '16pt' }}; font-weight: bold; color: #16161D; letter-spacing: .4pt; text-transform: uppercase;">{{ $docTitle ?? '' }}</div>
                @if (filled($docReference ?? null))<div style="font-size: {{ ($format ?? 'a4') === 'a5' ? '9pt' : '10pt' }}; font-weight: bold; color: #E6007E; margin-top: 1pt;">{{ $docReference }}</div>@endif
                @if (filled($docSubtitle ?? null))<div class="muted" style="margin-top: 1pt;">{!! $docSubtitle !!}</div>@endif
            </td>
        </tr>
    </table>
    <table class="brand-rule" style="margin-top: 7pt;"><tr><td style="width: 64%; background: #16161D;"></td><td style="width: 14%; background: #00A6E6;"></td><td style="width: 14%; background: #E6007E;"></td><td style="width: 8%; background: #FFE500;"></td></tr></table>
</div>
