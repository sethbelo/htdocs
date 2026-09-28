{{--
    Marque typographique de la barre latérale.
    variant « full » : « SÚRNATUREL » + « PRINT » ; « mono » : monogramme « SP ».
--}}
@props(['variant' => 'full'])
@php
    $words = preg_split('/\s+/', trim((string) \App\Models\Setting::get('company.name', 'Surnaturel Print')));
    $main = mb_strtoupper($words[0] ?? 'Surnaturel');
    $sub = mb_strtoupper(implode(' ', array_slice($words, 1)) ?: 'Print');
    $main = str_starts_with($main, 'SUR') ? 'SÚR<em>'.e(mb_substr($main, 3)).'</em>' : e($main);
    $mono = mb_substr($words[0] ?? 'S', 0, 1).mb_substr($words[1] ?? 'P', 0, 1);
@endphp
@if ($variant === 'mono')
    <span {{ $attributes->class('brand-mono') }} aria-hidden="true">{{ mb_strtoupper(mb_substr($mono, 0, 1)) }}<em>{{ mb_strtoupper(mb_substr($mono, 1, 1)) }}</em></span>
@else
    <span {{ $attributes->class('brand-type') }}>
        <span class="brand-type-main">{!! $main !!}</span>
        <span class="brand-type-sub"><i class="bg-cyan2-400"></i><i class="bg-safran-400"></i><i class="bg-carmin-600"></i>{{ $sub }}</span>
    </span>
@endif
