{{-- Rapport de gestion : indicateurs + sections tabulaires (ou tableau simple headings/rows) --}}
@extends('pdf.layout', [
    'docTitle' => $title,
    'docReference' => $reference ?? null,
    'docSubtitle' => ($from ?? null) ? 'Du '.\Illuminate\Support\Carbon::parse($from)->format('d/m/Y').' au '.\Illuminate\Support\Carbon::parse($to)->format('d/m/Y').(isset($site) ? ' · '.$site : '') : ($site ?? null),
])

@php
    $fmt = fn ($cell) => is_float($cell) ? number_format($cell, 2, ',', ' ') : $cell;
    $sections = $sections ?? [['title' => $sectionTitle ?? 'Détail', 'headings' => $data['headings'], 'rows' => $data['rows'], 'totals' => $data['totals'] ?? null]];
@endphp

@section('content')
    @if (! empty($kpis))
        <table style="margin-bottom: 4pt;">
            <tr>
                @foreach ($kpis as $kpi)
                    <td class="kpi" style="width: {{ floor(100 / count($kpis)) - 1 }}%;"><div class="label">{{ $kpi['label'] }}</div><div class="value">{{ $kpi['value'] }}</div></td>
                    @if (! $loop->last)<td style="width: 1%;"></td>@endif
                @endforeach
            </tr>
        </table>
    @endif

    @foreach ($sections as $section)
        <div class="section-title">{{ $section['title'] }}</div>
        <table class="lines">
            @php($numericColumns = collect(array_keys($section['headings']))->filter(fn ($i) => collect($section['rows'])->contains(fn ($row) => is_int(array_values((array) $row)[$i] ?? null) || is_float(array_values((array) $row)[$i] ?? null)))->all())
            <thead><tr>@foreach ($section['headings'] as $i => $heading)<th class="{{ in_array($i, $numericColumns, true) ? 'right' : '' }}">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($section['rows'] as $row)
                    <tr class="{{ $loop->even ? 'even' : '' }}">@foreach ($row as $cell)<td class="{{ is_float($cell) || is_int($cell) ? 'right nowrap' : '' }}">{{ $fmt($cell) }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($section['headings']) }}" class="center muted">Aucune donnée sur la période.</td></tr>
                @endforelse
                @if (! empty($section['totals']))
                    <tr class="total">@foreach ($section['headings'] as $i => $h)<td class="right nowrap">{{ $i === 0 ? 'TOTAUX' : (isset($section['totals'][$i]) ? $fmt((float) $section['totals'][$i]) : '') }}</td>@endforeach</tr>
                @endif
            </tbody>
        </table>
    @endforeach

    @isset($signatures)
        @include('pdf.partials.signatures', ['type' => $signatures])
    @endisset
@endsection
