{{-- Bloc destinataire (client) --}}
<div class="box">
    <div class="label">{{ $label ?? 'Client' }}</div>
    <div class="value" style="font-size: 10pt; margin: 1pt 0 2pt;">{{ $client->company_name ?: $client->name }}</div>
    @if ($client->company_name)<div>{{ $client->name }}</div>@endif
    @if ($client->address || $client->city)<div class="small">{{ collect([$client->address, $client->city])->filter()->implode(', ') }}</div>@endif
    <div class="small">{{ collect([$client->phone, $client->email])->filter()->implode(' · ') }}</div>
    <div class="small muted">Client n° {{ $client->number }}{{ $client->tax_id ? ' · NIF '.$client->tax_id : '' }}</div>
</div>
