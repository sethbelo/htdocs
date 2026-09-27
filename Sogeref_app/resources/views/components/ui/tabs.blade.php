{{-- Onglets liés à une propriété Livewire : <x-ui.tabs :tabs="[...]" model="tab" :current="$tab" /> --}}
@props(['tabs', 'current', 'model' => 'tab'])
<nav {{ $attributes->merge(['class' => 'tabs scrollbar-none']) }} role="tablist">
    @foreach ($tabs as $key => $tab)
        @php $tab = is_array($tab) ? $tab : ['label' => $tab]; @endphp
        <button type="button" role="tab" class="tab" aria-selected="{{ $current === $key ? 'true' : 'false' }}" wire:click="$set('{{ $model }}', '{{ $key }}')">
            @isset($tab['icon'])<x-ui.icon :name="$tab['icon']" class="size-[18px]" />@endisset
            {{ $tab['label'] }}
            @if (! empty($tab['count']))<span class="rounded-full bg-ink-100 px-1.5 text-[11px] font-bold text-fg-muted dark:bg-white/8">{{ $tab['count'] }}</span>@endif
        </button>
    @endforeach
</nav>
