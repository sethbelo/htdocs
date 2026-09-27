@php($baseCurrency = \App\Support\DisplayCurrency::baseCode())

<div {{ $attributes->class('flex shrink-0 items-center gap-2 rounded-xl bg-zinc-100 px-2.5 py-1.5 text-xs font-bold text-violet-700 dark:bg-zinc-800 dark:text-violet-300') }} title="Devise comptable unique : {{ \App\Support\DisplayCurrency::label($baseCurrency) }}" aria-label="Devise comptable {{ $baseCurrency }}">
    <flux:icon.banknotes class="size-4" />
    <span>{{ $baseCurrency }}</span>
</div>
