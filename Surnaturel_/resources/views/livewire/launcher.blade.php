<div class="launcher" x-data="{ q: '', normalize: (v) => v.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '') }">
    <div class="launcher-head">
        <div>
            <p class="eyebrow">{{ now()->translatedFormat('l j F Y') }}</p>
            <h1 class="font-display text-2xl font-bold text-fg sm:text-3xl">Bonjour, {{ $firstName }}</h1>
            <p class="mt-1 text-sm text-fg-muted">Choisissez une application. Vous ne voyez que les rubriques liées à vos attributions.</p>
        </div>
        <label class="launcher-search">
            <flux:icon.magnifying-glass variant="mini" class="size-4 shrink-0 text-fg-subtle" />
            <input type="search" x-model="q" placeholder="Rechercher une application…" autofocus class="w-full bg-transparent text-sm outline-none placeholder:text-fg-subtle" x-on:keydown.enter.prevent="$el.closest('.launcher').querySelector('.launcher-app:not([hidden])')?.click()">
        </label>
    </div>

    <div class="launcher-sections">
    @foreach ($departments as $department)
        <section class="launcher-section" wire:key="dep-{{ $department['key'] }}"
            x-show="! q || [...$el.querySelectorAll('.launcher-app')].some(a => normalize(a.dataset.label).includes(normalize(q)))">
            <div class="launcher-section-head">
                <span class="size-6">{!! \App\Support\AppIcons::svg($department['icon']) !!}</span>
                <h2 class="font-display text-[15px] font-bold text-fg">{{ $department['heading'] }}</h2>
                <span class="text-xs text-fg-subtle max-sm:hidden">· {{ $department['description'] }}</span>
            </div>
            <div class="launcher-grid">
                @foreach ($department['items'] as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate class="launcher-app group" data-label="{{ $item['label'] }} {{ $department['heading'] }}"
                        x-bind:hidden="!! q && ! normalize($el.dataset.label).includes(normalize(q))" wire:key="app-{{ $item['route'] }}">
                        <span class="launcher-tile">
                            {!! \App\Support\AppIcons::svg($item['route']) !!}
                            @if (($badges[$item['route']] ?? 0) > 0)<span class="launcher-badge">{{ $badges[$item['route']] > 99 ? '99+' : $badges[$item['route']] }}</span>@endif
                        </span>
                        <span class="launcher-label">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
    </div>

    <p x-show="q && ! [...$el.querySelectorAll('.launcher-app')].some(a => ! a.hidden)" x-cloak class="py-12 text-center text-sm text-fg-subtle">Aucune application ne correspond à « <span x-text="q"></span> ».</p>
</div>
