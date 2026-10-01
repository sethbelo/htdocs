<div class="launcher" x-data="{ q: '', normalize: (v) => (v || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '') }">
    <div class="launcher-head">
        <div>
            <p class="text-[12px] font-semibold tracking-[.18em] text-lagoon-600 uppercase dark:text-lagoon-300">{{ now()->translatedFormat('l j F Y') }}</p>
            <h1 class="mt-1 font-display text-2xl font-bold text-fg sm:text-3xl">Bonjour, {{ $firstName }}</h1>
            <p class="mt-1 text-[14px] text-fg-muted">Choisissez une application. Vous ne voyez que les rubriques liées à vos attributions.</p>
        </div>
        <label class="launcher-search">
            <x-ui.icon name="magnifying-glass" class="size-4 shrink-0 text-fg-subtle" />
            <input type="search" x-model="q" placeholder="Rechercher une application…" autofocus aria-label="Rechercher une application"
                   class="w-full border-0 bg-transparent text-[14px] outline-none placeholder:text-fg-subtle"
                   x-on:keydown.enter.prevent="$el.closest('.launcher').querySelector('.launcher-app:not([hidden])')?.click()">
        </label>
    </div>

    <div class="launcher-sections">
        @foreach ($apps as $app)
            <section class="launcher-section" wire:key="app-{{ $app['key'] }}"
                     x-show="! q || [...$el.querySelectorAll('.launcher-app')].some(a => normalize(a.dataset.label).includes(normalize(q)))">
                <div class="launcher-section-head">
                    <span class="size-6 shrink-0">{!! \App\Support\AppIcons::svg($app['key']) !!}</span>
                    <h2 class="font-display text-[15px] font-bold text-fg">{{ $app['label'] }}</h2>
                    @if ($app['description'])<span class="truncate text-[12px] text-fg-subtle max-sm:hidden">· {{ $app['description'] }}</span>@endif
                </div>
                <div class="launcher-grid">
                    @foreach ($app['items'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="launcher-app group" data-label="{{ $item['label'] }} {{ $app['label'] }}"
                           x-bind:hidden="!! q && ! normalize($el.dataset.label).includes(normalize(q))" wire:key="item-{{ $item['route'] }}">
                            <span class="launcher-tile">
                                {!! \App\Support\AppIcons::svg($item['key']) !!}
                                @if ($loop->first || ($app['items'][$loop->index - 1]['module'] ?? null) !== $item['module'])
                                    @if (($count = $badges[$item['module']] ?? 0) > 0)<span class="launcher-badge">{{ $count > 99 ? '99+' : $count }}</span>@endif
                                @endif
                            </span>
                            <span class="launcher-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <p x-show="q && ! [...$el.querySelectorAll('.launcher-app')].some(a => ! a.hidden)" x-cloak class="py-12 text-center text-[13px] text-fg-subtle">
        Aucune application ne correspond à « <span x-text="q"></span> ».
    </p>
</div>
