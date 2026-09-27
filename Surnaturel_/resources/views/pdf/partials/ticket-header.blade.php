<div class="center">
    @if ($company['logo'])<img src="{{ $company['logo'] }}" style="width: 150pt;"><br>@else<div class="bold" style="font-size: 11pt;">{{ $company['name'] }}</div>@endif
    <div class="small" style="margin-top: 3pt;">{{ $company['address'] }}{{ $company['city'] ? ', '.$company['city'] : '' }}</div>
    <div class="small">Tél. {{ $company['phone'] }}{{ $company['email'] ? ' · '.$company['email'] : '' }}</div>
    <div class="small muted">RCCM {{ $company['rccm'] }} · ID.NAT {{ $company['id_nat'] }} · NIF {{ $company['nif'] }}</div>
</div>
<div class="rule"></div>
