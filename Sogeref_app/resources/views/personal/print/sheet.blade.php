@extends('personal.print.layout', ['title' => $sheet->title, 'heading' => 'TABLEUR', 'reference' => \Illuminate\Support\Str::limit($sheet->title, 60)])

@section('content')
    <h1 class="mb-4 font-display text-[22px] font-semibold text-ink-900">{{ $sheet->title }}</h1>
    @if (empty($grid))
        <p class="text-ink-500">Tableur vide.</p>
    @else
        <div class="overflow-x-auto">
            <table class="sheet w-full">
                <thead><tr><th></th>@foreach ($grid[0] as $c => $_)<th>{{ \App\Support\SheetFormula::column($c) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($grid as $r => $row)
                        <tr>
                            <th>{{ $r + 1 }}</th>
                            @foreach ($row as $value)
                                <td @class(['is-number' => is_float($value) || is_int($value), 'is-error' => is_string($value) && str_starts_with($value, '#')]) style="min-width: 70px; max-width: none; white-space: normal;">{{ is_float($value) || is_int($value) ? number_format($value, fmod((float) $value, 1.0) == 0.0 ? 0 : 2, ',', ' ') : $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
