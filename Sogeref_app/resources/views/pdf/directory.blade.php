{{-- Annuaire des contacts (Relations clients) --}}
@extends('pdf.layout')

@section('content')
    @php $groups = $contacts->groupBy(fn ($c) => preg_match('/^[A-Z]$/', $l = mb_strtoupper(mb_substr($c->display_name, 0, 1))) ? $l : '#'); @endphp
    @forelse ($groups as $letter => $list)
        <div class="section-title">{{ $letter }}</div>
        <table class="lines">
            <thead><tr><th style="width: 24%;">Nom / Raison sociale</th><th style="width: 15%;">Interlocuteur</th><th style="width: 10%;">Type</th><th style="width: 13%;">Téléphone</th>@if ($hasWhatsapp)<th style="width: 12%;">WhatsApp</th>@endif<th>E-mail</th><th style="width: 11%;">Ville</th></tr></thead>
            <tbody>
                @foreach ($list as $c)
                    <tr class="{{ $loop->even ? 'even' : '' }}">
                        <td class="bold">{{ $c->display_name }}</td>
                        <td>{{ $c->company_name ? trim($c->first_name.' '.$c->last_name) : '' }}</td>
                        <td>{{ $c->type->label() }}</td>
                        <td class="nowrap">{{ $c->phone }}</td>
                        @if ($hasWhatsapp)<td class="nowrap">{{ $c->whatsapp }}</td>@endif
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->city }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="center muted">Aucun contact.</p>
    @endforelse
@endsection
