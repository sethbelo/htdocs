@extends('pdf.layout')

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 14pt;" class="bold">{{ $title }}</div>
        <div>Du {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} au {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</div>
        <div class="muted">{{ $site }} · édité le {{ now()->format('d/m/Y H:i') }}</div>
    @endcomponent
    <table class="items small">
        <thead><tr>@foreach ($data['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($data['rows'] as $row)
                <tr>@foreach ($row as $cell)<td class="{{ is_float($cell) || is_int($cell) ? 'right' : '' }}">{{ is_float($cell) ? number_format($cell, 2, ',', ' ') : $cell }}</td>@endforeach</tr>
            @endforeach
            @if ($data['totals'])
                <tr class="bold">@foreach ($data['headings'] as $i => $h)<td class="right">{{ $i === 0 ? 'Totaux' : (isset($data['totals'][$i]) ? number_format($data['totals'][$i], 2, ',', ' ') : '') }}</td>@endforeach</tr>
            @endif
        </tbody>
    </table>
@endsection
