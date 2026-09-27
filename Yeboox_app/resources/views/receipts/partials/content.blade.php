@php
    $isAddition ??= false;
    $provisionalDocumentType ??= 'addition';
    $provisionalLabel = $provisionalDocumentType === 'proforma' ? 'PROFORMA' : 'ADDITION';
    $documentNumber = $isAddition ? $orderNumber : $order->order_number;
    $documentDate = $isAddition ? $generatedAt : $order->created_at;
    $documentTitle = $isAddition ? ($provisionalDocumentType === 'proforma' ? 'REÇU PROFORMA' : 'ADDITION') : $ticketSettings['header_text'];
    $documentTotal = (float) ($isAddition ? $total : $order->total);
    $documentSubtotal = (float) ($isAddition ? $subtotal : $order->subtotal);
    $documentDiscount = (float) ($isAddition ? $discountAmount : $order->discount_amount);
    $documentTax = (float) ($isAddition ? ($documentTotal - ($documentTotal / 1.16)) : $order->tax_amount);
@endphp

<div class="receipt-shell">
    <header class="center">
        @if($ticketSettings['show_logo'] && $logoSource)
            <img src="{{ $logoSource }}" class="company-logo" alt="Logo">
        @endif
        <div class="company-name">{{ $establishment->name ?? 'YEBOOX POS' }}</div>
        @if($establishment?->slogan)<div class="small muted">{{ $establishment->slogan }}</div>@endif
        @if($establishment?->address)<div class="small">{{ $establishment->address }}</div>@endif
        @if($establishment?->phone)<div class="small">Tél. {{ $establishment->phone }}</div>@endif

        @if($ticketSettings['show_fiscal_info'])
            <div class="fiscal">
                @if($establishment?->rccm)<span>RCCM : {{ $establishment->rccm }}</span><br>@endif
                @if($establishment?->id_nat)<span>ID national : {{ $establishment->id_nat }}</span><br>@endif
                @if($establishment?->import_id)<span>ID importateur : {{ $establishment->import_id }}</span><br>@endif
                @if($establishment?->nif)<span>NIF : {{ $establishment->nif }}</span>@endif
                @if($establishment?->vat_number)<span>{{ $establishment?->nif ? ' · ' : '' }}TVA : {{ $establishment->vat_number }}</span>@endif
            </div>
        @endif
    </header>

    <div class="document-title">{{ $documentTitle }}</div>
    <table class="document-meta">
        <tr><td class="bold">N° {{ $isAddition ? $provisionalLabel : 'FACTURE' }} : {{ $documentNumber }}</td><td class="right">{{ $documentDate->format('d/m/Y') }}</td></tr>
        <tr><td>{{ $isAddition ? 'Document provisoire — non encaissé' : $order->type->label() }}</td><td class="right">{{ $documentDate->format('H:i:s') }}</td></tr>
        @if($isAddition && ($displayExchangeRate ?? 1) > 1)<tr><td class="small">Devise : USD</td><td class="right small">1 USD = {{ number_format($displayExchangeRate, 2, ',', '.') }} CDF</td></tr>@endif
        @if(($table ?? null) || (! $isAddition && $order->table))<tr><td>Table : {{ ($table ?? null)?->name ?? $order->table?->name }}</td><td></td></tr>@endif
        @if(! $isAddition && $order->user)<tr><td class="small">Caissier : {{ $order->user->name }}</td><td></td></tr>@endif
    </table>

    <div class="rule-strong"></div>
    <table class="items">
        <thead><tr><th class="item-name">Article</th><th class="item-qty">Qté</th><th class="item-price">Montant</th></tr></thead>
        <tbody>
            @foreach($items as $item)
                <tr><td class="item-name">{{ $item->name ?? $item->product?->name ?? 'Produit' }}</td><td class="item-qty">{{ number_format($item->quantity, 0) }}</td><td class="item-price">{{ number_format($item->total, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr>
                @if($item->quantity > 1)<tr><td colspan="3" class="small muted">&nbsp;@ {{ number_format($item->unit_price, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr>@endif
            @endforeach
        </tbody>
    </table>

    <div class="rule-strong"></div>
    <table class="totals">
        <tr><td>Sous-total</td><td class="right">{{ number_format($documentSubtotal, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr>
        @if($documentDiscount > 0)<tr><td>Remise</td><td class="right">-{{ number_format($documentDiscount, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr>@endif
        @if($ticketSettings['show_tax_details'])<tr><td>TVA (16% incluse)</td><td class="right">{{ number_format($documentTax, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr><tr><td>Total hors TVA</td><td class="right">{{ number_format($documentTotal - $documentTax, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr>@endif
        <tr class="total-row"><td>TOTAL</td><td class="right">{{ number_format($documentTotal, $currencyDecimals ?? 0, ',', '.') }} {{ $currencySymbol }}</td></tr>
    </table>

    @if(! $isAddition && $ticketSettings['show_payment_details'] && $payments->isNotEmpty())
        <div class="payment-title">PAIEMENT</div>
        <table class="payments">
            @foreach($payments as $payment)
                @php
                    $paymentSymbol = $payment->currency?->code === 'CDF' ? 'FC' : ($payment->currency?->code ?? $currencySymbol);
                    $cashGiven = (float) ($payment->tendered_amount ?: $payment->amount);
                @endphp
                <tr><td>{{ strtoupper($payment->paymentMethod?->name ?? 'CASH') }}</td><td class="right">{{ number_format($payment->amount, 0, ',', '.') }} {{ $currencySymbol }}</td></tr>
                @if($payment->paymentMethod?->code === 'cash')
                    <tr><td>CASH REÇU</td><td class="right">{{ number_format($cashGiven, $paymentSymbol === 'FC' ? 0 : 2, ',', '.') }} {{ $paymentSymbol }}</td></tr>
                    <tr class="change-row"><td>CHANGE</td><td class="right">{{ number_format($payment->change_amount, $paymentSymbol === 'FC' ? 0 : 2, ',', '.') }} {{ $paymentSymbol }}</td></tr>
                @endif
            @endforeach
        </table>
    @endif

    @if($codeImage)
        <img src="{{ $codeImage }}" class="code-image {{ $ticketSettings['code_type'] }}" alt="{{ $ticketSettings['code_type'] === 'qrcode' ? 'QR code' : 'Code-barres' }}">
    @endif

    <footer class="footer"><div class="bold">{{ $ticketSettings['footer_text'] }}</div><div class="small muted">{{ $generatedAt->format('d/m/Y H:i:s') }}</div></footer>
    <div class="powered">Propulsé par Yeboox POS</div>
</div>
