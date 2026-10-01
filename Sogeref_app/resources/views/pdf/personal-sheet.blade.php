{{-- Tableur personnel sur papier à en-tête SOGEREF (valeurs calculées) --}}
@extends('pdf.layout')

@section('content')
    <div class="section-title" style="margin-top: 0;">{{ $sheet->title }}</div>
    @if (empty($grid))
        <p class="muted center" style="margin-top: 30px;">Tableur vide.</p>
    @else
        <table class="lines" style="font-size: 9.5px;">
            <thead>
                <tr>
                    <th style="width: 22px;"></th>
                    @foreach ($grid[0] as $c => $_)<th class="center">{{ \App\Support\SheetFormula::column($c) }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($grid as $r => $row)
                    <tr class="{{ $loop->even ? 'even' : '' }}">
                        <td class="muted center small">{{ $r + 1 }}</td>
                        @foreach ($row as $value)
                            <td class="{{ is_float($value) || is_int($value) ? 'right nowrap' : '' }}">{{ is_float($value) || is_int($value) ? number_format($value, fmod((float) $value, 1.0) == 0.0 ? 0 : 2, ',', ' ') : $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
