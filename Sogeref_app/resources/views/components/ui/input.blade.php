{{-- Champ texte lié à Livewire : <x-ui.input label="Nom" wire:model="name" /> --}}
@props(['label' => null, 'hint' => null, 'required' => false, 'prefix' => null, 'suffix' => null])
@php $model = $attributes->wire('model')->value(); @endphp
<x-ui.field :label="$label" :for="$model" :hint="$hint" :required="$required" :class="$attributes->get('class')">
    <div class="relative">
        @if ($prefix)<span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[13px] text-fg-subtle">{{ $prefix }}</span>@endif
        <input id="{{ $model }}" {{ $attributes->except('class')->merge(['type' => 'text'])->class(['input', 'input-error' => $model && $errors->has($model), 'pl-9' => $prefix, 'pr-12' => $suffix]) }}>
        @if ($suffix)<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[12.5px] font-medium text-fg-subtle">{{ $suffix }}</span>@endif
    </div>
</x-ui.field>
