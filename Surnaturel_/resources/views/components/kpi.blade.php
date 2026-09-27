@props(['label', 'value', 'icon' => null, 'color' => 'indigo', 'hint' => null, 'href' => null, 'trend' => null])
@php
    $iconTone = [
        'indigo' => 'bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-cyan2-300',
        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300',
        'amber' => 'bg-safran-100 text-safran-800 dark:bg-safran-400/15 dark:text-safran-300',
        'red' => 'bg-carmin-50 text-carmin-600 dark:bg-carmin-400/12 dark:text-carmin-300',
        'purple' => 'bg-violet-50 text-violet-700 dark:bg-violet-400/12 dark:text-violet-300',
        'sky' => 'bg-cyan2-50 text-cyan2-700 dark:bg-cyan2-400/12 dark:text-cyan2-300',
        'zinc' => 'bg-zinc-100 text-fg-muted dark:bg-white/8',
    ][$color] ?? 'bg-zinc-100 text-fg-muted dark:bg-white/8';
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class('kpi-card block p-4 transition sm:p-5'.($href ? ' hover:border-brand-200 hover:shadow-md' : '')) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="kpi-label truncate">{{ $label }}</p>
            <p class="kpi-value truncate">{{ $value }}</p>
        </div>
        @if ($icon)
            <span @class(['hidden shrink-0 rounded-xl p-2.5 sm:grid sm:place-items-center', $iconTone])>
                <flux:icon :icon="$icon" variant="mini" class="size-5" />
            </span>
        @endif
    </div>
    @if ($hint || $trend !== null)
        <p class="mt-2 flex items-center gap-1.5 text-[12px] text-fg-subtle">
            @if ($trend !== null)
                <span @class(['inline-flex items-center gap-0.5 font-semibold', 'text-emerald-600' => $trend >= 0, 'text-carmin-600' => $trend < 0])>
                    <flux:icon :icon="$trend >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" variant="mini" class="size-3.5" />{{ number_format(abs($trend), 0) }} %
                </span>
            @endif
            <span class="truncate">{{ $hint }}</span>
        </p>
    @endif
</{{ $tag }}>
