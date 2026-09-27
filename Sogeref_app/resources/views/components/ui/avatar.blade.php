@props(['name' => '?', 'size' => 'md', 'tone' => null, 'src' => null])
@php
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') ?: '?';
    $palette = ['bg-brand-600 text-white', 'bg-lagoon-400 text-brand-900', 'bg-sun-400 text-brand-900', 'bg-brand-400 text-white', 'bg-violet-500 text-white', 'bg-emerald-500 text-white'];
    $color = $tone ?? $palette[crc32($name) % count($palette)];
    $dim = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-[11px]', 'md' => 'size-10 text-[13px]', 'lg' => 'size-14 text-[17px]', 'xl' => 'size-20 text-[24px]'][$size];
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->merge(['class' => 'avatar object-cover '.$dim]) }}>
@else
    <span {{ $attributes->merge(['class' => 'avatar '.$dim.' '.$color]) }} title="{{ $name }}">{{ $initials }}</span>
@endif
