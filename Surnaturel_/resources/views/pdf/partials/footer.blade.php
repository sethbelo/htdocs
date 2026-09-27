{{-- Pied de page légal + pagination --}}
<div class="pdf-footer">
    <table class="brand-rule"><tr><td style="width: 8%; background: #FFE500;"></td><td style="width: 14%; background: #E6007E;"></td><td style="width: 14%; background: #00A6E6;"></td><td style="width: 64%; background: #16161D;"></td></tr></table>
    <table style="margin-top: 5pt;">
        <tr>
            <td style="width: 80%; vertical-align: top;">
                <strong style="color: #16161D;">{{ $company['legal_name'] ?: $company['name'] }}</strong>@if ($company['slogan']) — <span style="color: #E6007E;">{{ $company['slogan'] }}</span>@endif<br>
                {{ collect([$company['address'], $company['city'], $company['country']])->filter()->implode(', ') }} · Tél. {{ $company['phone'] }} · {{ $company['email'] }}{{ $company['website'] ? ' · '.$company['website'] : '' }}<br>
                RCCM {{ $company['rccm'] }} · ID.NAT {{ $company['id_nat'] }} · NIF {{ $company['nif'] }}@if ($company['bank_details']) · Banques : {{ $company['bank_details'] }}@endif
            </td>
            <td style="text-align: right; vertical-align: top;" class="nowrap">
                &nbsp;<br>
                <span style="font-size: 6.3pt;">Édité le {{ now()->format('d/m/Y à H:i') }}</span>
                @auth<br><span style="font-size: 6.3pt;">par {{ auth()->user()->name }}</span>@endauth
            </td>
        </tr>
    </table>
</div>
