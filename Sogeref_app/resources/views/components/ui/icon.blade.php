{{-- Icône Heroicons : <x-ui.icon name="truck" /> · variante « o » (contour) ou « s » (plein) --}}
@props(['name', 'variant' => 'o'])
<x-dynamic-component :component="'heroicon-'.$variant.'-'.$name" {{ $attributes->merge(['class' => 'size-5 shrink-0', 'aria-hidden' => 'true']) }} />
