@props(['amount'])
<span {{ $attributes->class('tabular-nums whitespace-nowrap') }}>{{ \App\Services\Support\Money::format($amount) }}</span>
