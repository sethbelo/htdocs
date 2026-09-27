@props(['placeholder' => 'Rechercher…'])
<label {{ $attributes->only('class')->merge(['class' => 'relative block w-full sm:w-72']) }}>
    <x-ui.icon name="magnifying-glass" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-fg-subtle" />
    <input type="search" placeholder="{{ $placeholder }}" {{ $attributes->except('class')->merge(['class' => 'input pl-9']) }}>
</label>
