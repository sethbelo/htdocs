{{-- Registre du courrier entrant / sortant (Réception) --}}
@extends('pdf.layout')

@section('content')
    @php $channels = ['physique' => 'Dépôt', 'email' => 'E-mail', 'coursier' => 'Coursier']; @endphp
    <table class="lines">
        <thead><tr><th style="width: 9%;">Référence</th><th style="width: 7%;">Date</th><th style="width: 5%;">Sens</th><th style="width: 16%;">Correspondant</th><th>Objet</th><th style="width: 14%;">Service</th><th style="width: 12%;">Transmis à</th><th style="width: 8%;">Statut</th><th style="width: 9%;">Visa</th></tr></thead>
        <tbody>
            @forelse ($records as $r)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="bold nowrap">{{ $r->reference }}</td>
                    <td class="nowrap">{{ $r->record_date->format('d/m/Y') }}</td>
                    <td>{{ $r->direction === 'in' ? 'Entrée' : 'Sortie' }}</td>
                    <td>{{ $r->correspondent }}<div class="muted small">{{ $channels[$r->channel] ?? $r->channel }}</div></td>
                    <td>{{ $r->subject }}</td>
                    <td>{{ $r->department?->name }}</td>
                    <td>{{ $r->handler?->name }}</td>
                    <td><span class="badge">{{ erp_status('mail', $r->status)[0] }}</span></td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="9" class="center muted">Aucun courrier pour ces critères.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted small" style="margin-top: 8px;">{{ $records->count() }} courrier(s) · {{ $records->where('direction', 'in')->count() }} entrant(s) · {{ $records->where('direction', 'out')->count() }} sortant(s)</p>
@endsection
