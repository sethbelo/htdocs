@props(['value' => 0, 'tone' => 'brand', 'label' => null])
@php
    $value = max(0, min(100, (int) $value));
    $bar = ['brand' => 'bg-brand-600 dark:bg-lagoon-400', 'sun' => 'bg-sun-400', 'lagoon' => 'bg-lagoon-400', 'flame' => 'bg-flame-500', 'emerald' => 'bg-emerald-500'][$tone] ?? 'bg-brand-600';
@endphp
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @if ($label !== null)
        <div class="mb-1 flex justify-between text-[12px]"><span class="text-fg-subtle">{{ $label }}</span><span class="font-semibold tabular-nums text-fg">{{ $value }} %</span></div>
    @endif
    <div class="h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-white/8"><div class="h-full rounded-full {{ $bar }} transition-all" style="width: {{ $value }}%"></div></div>
</div>
