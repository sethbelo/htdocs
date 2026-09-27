<div class="center">
    @if ($company['logo'])
        <img src="{{ $company['logo'] }}" style="max-height: 38pt; max-width: 120pt;"><br>
    @endif
    <div class="bold" style="font-size: 10.5pt;">{{ $company['name'] }}</div>
    <div class="small">{{ $company['address'] }}{{ $company['city'] ? ', '.$company['city'] : '' }}</div>
    <div class="small">Tél. {{ $company['phone'] }}</div>
    <div class="small muted">RCCM {{ $company['rccm'] }} · ID.NAT {{ $company['id_nat'] }} · NIF {{ $company['nif'] }}</div>
</div>
<div class="dashed"></div>
