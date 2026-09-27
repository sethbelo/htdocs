<table style="margin-bottom: 16pt;">
    <tr>
        <td style="width: 55%; vertical-align: top;">
            @if ($company['logo'])
                <img src="{{ $company['logo'] }}" style="max-height: 50pt; max-width: 160pt; margin-bottom: 4pt;"><br>
            @endif
            <div class="bold" style="font-size: 13pt;">{{ $company['name'] }}</div>
            @if ($company['legal_name'])<div>{{ $company['legal_name'] }}</div>@endif
            <div class="muted small">{{ $company['address'] }}{{ $company['city'] ? ', '.$company['city'] : '' }}</div>
            <div class="muted small">{{ $company['phone'] }} · {{ $company['email'] }}{{ $company['website'] ? ' · '.$company['website'] : '' }}</div>
            <div class="muted small">RCCM : {{ $company['rccm'] }} · ID.NAT : {{ $company['id_nat'] }} · NIF : {{ $company['nif'] }}</div>
        </td>
        <td style="width: 45%; vertical-align: top;" class="right">
            {{ $slot ?? '' }}
            @yield('document-title')
        </td>
    </tr>
</table>
