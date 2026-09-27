@props([
    'variant' => null,
    'icon' => null,
    'heading' => null,
])

<div
    x-data="{ visible: true }"
    x-show="visible"
    x-transition.opacity.duration.180ms
    {{ $attributes->class('') }}
>
    <flux:callout :variant="$variant" :icon="$icon" :heading="$heading">
        <x-slot:controls>
            <flux:button
                type="button"
                x-on:click="visible = false"
                variant="subtle"
                icon="x-mark"
                size="sm"
                square
                aria-label="Fermer l’alerte"
            />
        </x-slot:controls>
        {{ $slot }}
    </flux:callout>
</div>
