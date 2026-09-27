@props(['amount' => 0, 'currency' => null, 'withCode' => true])

<span {{ $attributes->class('tabular-nums whitespace-nowrap') }}>{{ \App\Support\DisplayCurrency::format($amount, $currency, (bool) $withCode) }}</span>
