{{--
    Barre de filtres des listes : recherche, période, statut (+ filtres supplémentaires dans le slot).
    Props : placeholder, statuses (valeur => libellé), period (bool), search (bool).
--}}
@props(['placeholder' => 'Rechercher…', 'statuses' => null, 'period' => true, 'search' => true, 'statusLabel' => 'Statut', 'periodLabel' => 'Période'])
<div {{ $attributes->class('panel flex flex-wrap items-end gap-2.5 p-3') }}>
    @if ($search)
        <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" :placeholder="$placeholder" size="sm" class="min-w-56 flex-1 sm:max-w-sm" clearable />
    @endif
    @if ($statuses)
        <flux:select wire:model.live="status" size="sm" class="max-w-52">
            <flux:select.option value="">{{ $statusLabel }} : tous</flux:select.option>
            @foreach ($statuses as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
    @endif
    {{ $slot }}
    @if ($period)
        <div class="flex items-center gap-1.5">
            <span class="text-xs text-fg-subtle">{{ $periodLabel }}</span>
            <flux:input type="date" wire:model.live="from" size="sm" class="max-w-38" aria-label="Du" />
            <span class="text-xs text-fg-subtle">→</span>
            <flux:input type="date" wire:model.live="to" size="sm" class="max-w-38" aria-label="Au" />
        </div>
    @endif
    @if ($this->hasActiveFilters())
        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Réinitialiser</flux:button>
    @endif
</div>
