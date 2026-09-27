{{-- Bouton / lien : variant = primary | accent | secondary | ghost | danger | soft | success --}}
@props(['variant' => 'primary', 'size' => null, 'icon' => null, 'iconRight' => null, 'href' => null, 'type' => 'button', 'navigate' => true, 'square' => false])
@php
    $classes = collect(['btn', 'btn-'.$variant, $size ? 'btn-'.$size : null, $square ? 'btn-icon' : null])->filter()->implode(' ');
    $iconSize = $size === 'sm' ? 'size-4' : 'size-[18px]';
    $target = $attributes->wire('click')->value();
    $target = $target ? preg_replace('/\(.*$/', '', $target) : null;
@endphp
@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-ui.icon :name="$icon" :class="$iconSize" />@endif{{ $slot }}@if ($iconRight)<x-ui.icon :name="$iconRight" :class="$iconSize" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if ($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif>
        @if ($icon && $target)
            <span wire:loading.remove wire:target="{{ $target }}" class="contents"><x-ui.icon :name="$icon" :class="$iconSize" /></span>
            <span wire:loading wire:target="{{ $target }}" class="contents"><x-ui.icon name="arrow-path" :class="$iconSize.' animate-spin'" /></span>
        @elseif ($icon)
            <x-ui.icon :name="$icon" :class="$iconSize" />
        @endif{{ $slot }}@if ($iconRight)<x-ui.icon :name="$iconRight" :class="$iconSize" />@endif
    </button>
@endif
