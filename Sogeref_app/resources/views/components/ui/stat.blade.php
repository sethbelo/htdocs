{{-- Indicateur clé : libellé, valeur, tendance / aide, icône et ton --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'brand', 'href' => null, 'trend' => null])
@php
    $iconTone = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300',
        'sun' => 'bg-sun-100 text-sun-700 dark:bg-sun-400/15 dark:text-sun-300',
        'lagoon' => 'bg-lagoon-50 text-lagoon-700 dark:bg-lagoon-400/12 dark:text-lagoon-300',
        'flame' => 'bg-flame-50 text-flame-600 dark:bg-flame-400/12 dark:text-flame-300',
        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300',
    ][$tone] ?? '';
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->merge(['class' => 'kpi block'.($href ? ' transition hover:border-brand-200 hover:shadow-md' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="kpi-label truncate">{{ $label }}</p>
            <p class="kpi-value">{{ $value }}</p>
        </div>
        @if ($icon)<span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $iconTone }}"><x-ui.icon :name="$icon" /></span>@endif
    </div>
    @if ($hint || $trend !== null)
        <p class="mt-2 flex items-center gap-1.5 text-[12.5px] text-fg-subtle">
            @if ($trend !== null)
                <span @class(['inline-flex items-center gap-0.5 font-semibold', 'text-emerald-600' => $trend >= 0, 'text-flame-600' => $trend < 0])>
                    <x-ui.icon :name="$trend >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="size-4" />{{ number_format(abs($trend), 0) }} %
                </span>
            @endif
            {{ $hint }}
        </p>
    @endif
</{{ $tag }}>
