@props(['amount', 'currency' => 'USD', 'muted' => false])
<span {{ $attributes->merge(['class' => 'tabular-nums whitespace-nowrap'.($muted ? ' text-fg-subtle' : '')]) }}>{{ money($amount, $currency) }}</span>
