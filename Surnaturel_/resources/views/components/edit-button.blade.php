{{-- Bouton « Modifier » des listes : gris, discret, identique dans toute l'application (lien si href est fourni). --}}
@props(['label' => 'Modifier', 'icon' => 'pencil-square', 'compact' => false, 'href' => null])
@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class(['btn-edit', 'btn-edit-compact' => $compact]) }} title="{{ $label }}">
        <flux:icon :icon="$icon" variant="micro" class="size-3.5" />
        @unless ($compact)<span>{{ $label }}</span>@endunless
    </a>
@else
    <button type="button" {{ $attributes->class(['btn-edit', 'btn-edit-compact' => $compact]) }} title="{{ $label }}">
        <flux:icon :icon="$icon" variant="micro" class="size-3.5" />
        @unless ($compact)<span>{{ $label }}</span>@endunless
    </button>
@endif
