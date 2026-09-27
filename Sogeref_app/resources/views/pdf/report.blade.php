{{-- Rapport de gestion générique : sections avec KPI et tableaux --}}
@extends('pdf.layout', ['docSubtitle' => $report['subtitle'] ?? null])

@section('content')
    @if (! empty($report['kpis']))
        <table>
            <tr>
                @foreach ($report['kpis'] as $kpi)
                    <td class="box" style="width: {{ floor(100 / count($report['kpis'])) - 1 }}%;"><div class="label">{{ $kpi['label'] }}</div><div class="bold navy" style="font-size: 13px;">{{ $kpi['value'] }}</div></td>
                    @if (! $loop->last)<td style="width: 1%;"></td>@endif
                @endforeach
            </tr>
        </table>
    @endif
    @foreach ($report['sections'] as $section)
        <div class="section-title">{{ $section['title'] }}</div>
        <table class="lines">
            <thead><tr>@foreach ($section['columns'] as $i => $col)<th class="{{ $i > 0 && ($section['numeric'][$i] ?? false) ? 'right' : '' }}">{{ $col }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($section['rows'] as $row)
                    <tr class="{{ $loop->even ? 'even' : '' }} {{ ($row['_total'] ?? false) ? 'bold' : '' }}">
                        @foreach (array_values(array_diff_key($row, ['_total' => 1])) as $i => $cell)
                            <td class="{{ ($section['numeric'][$i] ?? false) ? 'right nowrap' : '' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($section['columns']) }}" class="center muted">Aucune donnée sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
@endsection
