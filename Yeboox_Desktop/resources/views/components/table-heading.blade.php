@props(['icon'])

<span {{ $attributes->class('inline-flex items-center gap-2 whitespace-nowrap') }}>
    <flux:icon :name="$icon" class="size-3.5 shrink-0 opacity-70" aria-hidden="true" />
    <span>{{ $slot }}</span>
</span>
