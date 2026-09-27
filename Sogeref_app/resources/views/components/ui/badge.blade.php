@props(['tone' => 'neutral', 'dot' => false, 'icon' => null])
<span {{ $attributes->merge(['class' => 'badge tone-'.$tone.($dot ? ' badge-dot' : '')]) }}>@if ($icon)<x-ui.icon :name="$icon" class="size-3.5" />@endif{{ $slot }}</span>
