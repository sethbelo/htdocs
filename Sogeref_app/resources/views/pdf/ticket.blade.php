{{-- Ticket de caisse 80 mm --}}
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">
<style>
    @page { margin: 12px 10px; }
    body { font-family: Helvetica, sans-serif; font-size: 9px; color: #111; }
    table { width: 100%; border-collapse: collapse; }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
    .sep { border-top: 1px dashed #999; margin: 6px 0; }
</style></head>
<body>
    <div class="c"><img src="{{ public_path('brand/logo-horizontal.png') }}" style="height: 34px;"></div>
    <div class="c" style="margin-top: 4px;">{{ data_get($company->address, 'line1') }}<br>{{ data_get($company->address, 'line2') }}, Kinshasa<br>Tél. {{ $company->phone }}<br>N° Impôt {{ $company->tax_number }}</div>
    <div class="sep"></div>
    <div>Ticket <span class="b">{{ $order->number }}</span><br>{{ $order->created_at->format('d/m/Y H:i') }} · Caisse : {{ $order->session?->user?->name }}@if ($order->contact)<br>Client : {{ $order->contact->display_name }}@endif</div>
    <div class="sep"></div>
    <table>
        @foreach ($order->lines as $line)
            <tr><td colspan="2" class="b">{{ $line->name }}</td></tr>
            <tr><td>{{ qty($line->quantity) }} × {{ number_format($line->unit_price, 2, ',', ' ') }}@if ((float) $line->discount_rate) (−{{ qty($line->discount_rate) }} %)@endif</td><td class="r">{{ number_format($line->total, 2, ',', ' ') }}</td></tr>
        @endforeach
    </table>
    <div class="sep"></div>
    <table>
        <tr><td>Sous-total</td><td class="r">{{ number_format($order->subtotal, 2, ',', ' ') }}</td></tr>
        @if ((float) $order->discount)<tr><td>Remises</td><td class="r">−{{ number_format($order->discount, 2, ',', ' ') }}</td></tr>@endif
        <tr><td>TVA</td><td class="r">{{ number_format($order->tax, 2, ',', ' ') }}</td></tr>
        <tr><td class="b" style="font-size: 12px;">TOTAL</td><td class="r b" style="font-size: 12px;">{{ money($order->total) }}</td></tr>
        <tr><td>Payé ({{ ['cash' => 'Espèces', 'card' => 'Carte', 'mobile_money' => 'Mobile money'][$order->payment_method] ?? $order->payment_method }})</td><td class="r">{{ number_format($order->tendered, 2, ',', ' ') }}</td></tr>
        @if ((float) $order->change_due)<tr><td>Rendu</td><td class="r">{{ number_format($order->change_due, 2, ',', ' ') }}</td></tr>@endif
    </table>
    @if ($order->points_earned || $order->points_redeemed)
        <div class="sep"></div>
        <div>Fidélité : +{{ $order->points_earned }} pts @if ($order->points_redeemed)· −{{ $order->points_redeemed }} pts utilisés @endif</div>
    @endif
    <div class="sep"></div>
    <div class="c">Merci de votre confiance !<br><span class="b">{{ data_get($company->settings, 'slogan') }}</span></div>
</body></html>
