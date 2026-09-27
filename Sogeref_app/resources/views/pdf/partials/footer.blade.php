{{-- Pied de page légal SOGEREF + pagination --}}
@php $company = $company ?? company(); @endphp
<div class="pdf-footer">
    <table class="brand-rule"><tr><td style="width: 5%; background: #F7313A;"></td><td style="width: 10%; background: #3ACECE;"></td><td style="width: 15%; background: #FFCE31;"></td><td style="width: 70%; background: #104A63;"></td></tr></table>
    <table style="margin-top: 6px;">
        <tr>
            <td style="width: 82%; vertical-align: top;">
                <strong style="color: #104A63;">{{ $company->name }}</strong> — {{ data_get($company->settings, 'slogan', 'Sculptons vos rêves, bâtissons la réalité') }}<br>
                {{ $company->document_footer }}
                @if (data_get($company->settings, 'bank_details'))<br>Banques : {{ data_get($company->settings, 'bank_details') }}@endif
            </td>
            <td style="text-align: right; vertical-align: top;" class="nowrap">
                Page <span class="pagenum"></span><br>
                <span style="font-size: 7.5px;">Édité le {{ now()->format('d/m/Y à H:i') }}</span>
            </td>
        </tr>
    </table>
</div>
