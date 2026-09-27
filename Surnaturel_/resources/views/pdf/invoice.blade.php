@extends('pdf.layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))
@php($reference = $invoice->order?->number ?? $invoice->sale?->number)

@section('content')
    @if ($format === 'ticket')
        @include('pdf.partials.ticket-header')
        <div class="center bold" style="font-size: 11pt;">{{ mb_strtoupper($invoice->type->label()) }}</div>
        <div class="center bold">{{ $invoice->number }}</div>
        @if ($reference)<div class="center">Réf. commande : <span class="bold">{{ $reference }}</span></div>@endif
        <div class="center small">{{ $invoice->issued_at->format('d/m/Y H:i') }}</div>
        <div class="dashed"></div>
        @if ($invoice->client)<div>Client : <span class="bold">{{ $invoice->client->displayName() }}</span></div><div class="dashed"></div>@endif
        <table class="ticket">
            @foreach ($invoice->items as $item)
                <tr><td colspan="2">{{ $item->description }}</td></tr>
                <tr><td class="muted">{{ (float) $item->quantity }} × {{ $money($item->unit_price) }}</td><td class="right">{{ $money($item->total) }}</td></tr>
            @endforeach
        </table>
        <div class="dashed"></div>
        <table class="ticket">
            <tr><td>Total HT</td><td class="right">{{ $money((float) $invoice->total - (float) $invoice->tax_total) }}</td></tr>
            <tr><td>TVA</td><td class="right">{{ $money($invoice->tax_total) }}</td></tr>
            <tr><td class="bold">TOTAL TTC</td><td class="right bold" style="font-size: 11pt;">{{ $money($invoice->total) }}</td></tr>
            @foreach ($invoice->payments as $payment)
                <tr><td class="small">{{ $payment->method->label() }} {{ $payment->paid_at->format('d/m H:i') }}</td><td class="right small">{{ $money($payment->amount) }}</td></tr>
            @endforeach
            <tr><td>Payé</td><td class="right">{{ $money($invoice->amount_paid) }}</td></tr>
            <tr><td class="bold">Reste dû</td><td class="right bold">{{ $money($invoice->balanceDue()) }}</td></tr>
        </table>
        <div class="center" style="margin-top: 6pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($reference ?? $invoice->number, 90) }}" style="width: 64pt; height: 64pt;"></div>
        <div class="dashed"></div>
        <div class="center small">{{ $company['footer'] }}</div>
    @else
        @component('pdf.partials.company-header', ['company' => $company])
            <div style="font-size: 16pt;" class="bold">{{ mb_strtoupper($invoice->type->label()) }}</div>
            <div class="bold" style="font-size: 11pt;">N° {{ $invoice->number }}</div>
            @if ($reference)<div>Commande : <span class="bold">{{ $reference }}</span></div>@endif
            <div class="muted">Date : {{ $invoice->issued_at->format('d/m/Y') }}</div>
            @if ($invoice->due_at)<div class="muted">Échéance : {{ $invoice->due_at->format('d/m/Y') }}</div>@endif
            <div style="margin-top: 4pt;"><span class="badge">{{ $invoice->status->label() }}</span></div>
        @endcomponent

        @if ($invoice->client)
            <div class="box" style="width: 48%; margin-left: 52%; margin-bottom: 14pt;">
                <div class="muted small">FACTURÉ À</div>
                <div class="bold">{{ $invoice->client->company_name ?? $invoice->client->name }}</div>
                @if ($invoice->client->company_name)<div>{{ $invoice->client->name }}</div>@endif
                <div class="small">{{ $invoice->client->address }} {{ $invoice->client->city }}</div>
                <div class="small">{{ $invoice->client->phone }} {{ $invoice->client->email }}</div>
                @if ($invoice->client->tax_id)<div class="small">NIF : {{ $invoice->client->tax_id }}</div>@endif
                <div class="small muted">Client n° {{ $invoice->client->number }}</div>
            </div>
        @endif

        <table class="items">
            <thead><tr><th style="width: 52%;">Désignation</th><th class="right">Qté</th><th class="right">P.U. HT</th><th class="right">Remise</th><th class="right">TVA</th><th class="right">Total TTC</th></tr></thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="right">{{ (float) $item->quantity }}</td>
                        <td class="right">{{ $money($item->unit_price) }}</td>
                        <td class="right">{{ (float) $item->discount ? $money($item->discount) : '—' }}</td>
                        <td class="right">{{ (float) $item->tax_rate }} %</td>
                        <td class="right">{{ $money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top: 10pt;">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    @if ($invoice->payments->isNotEmpty())
                        <div class="muted small bold" style="margin-bottom: 3pt;">RÈGLEMENTS</div>
                        <table class="small">
                            @foreach ($invoice->payments as $payment)
                                <tr><td>{{ $payment->paid_at->format('d/m/Y') }} — {{ $payment->method->label() }} {{ $payment->reference }}</td><td class="right">{{ $money($payment->amount) }}</td></tr>
                            @endforeach
                        </table>
                    @endif
                    <div style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($reference ?? $invoice->number, 90) }}" style="width: 60pt; height: 60pt;"></div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <table class="totals">
                        <tr><td>Sous-total</td><td class="right">{{ $money($invoice->subtotal) }}</td></tr>
                        <tr><td>Remises</td><td class="right">− {{ $money($invoice->discount_total) }}</td></tr>
                        <tr><td>Total HT</td><td class="right">{{ $money((float) $invoice->total - (float) $invoice->tax_total) }}</td></tr>
                        <tr><td>TVA</td><td class="right">{{ $money($invoice->tax_total) }}</td></tr>
                        <tr class="grand"><td>TOTAL TTC</td><td class="right">{{ $money($invoice->total) }}</td></tr>
                        <tr><td>Déjà réglé</td><td class="right">{{ $money($invoice->amount_paid) }}</td></tr>
                        <tr><td class="bold">Net à payer</td><td class="right bold">{{ $money($invoice->balanceDue()) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table style="margin-top: 30pt;">
            <tr>
                <td style="width: 50%;" class="small muted">Établie par : {{ $invoice->creator?->name }}</td>
                <td style="width: 50%;" class="right small">Signature & cachet<br><br><br>________________________</td>
            </tr>
        </table>
        <div class="footer">{{ $company['legal_name'] ?? $company['name'] }} · RCCM {{ $company['rccm'] }} · ID.NAT {{ $company['id_nat'] }} · NIF {{ $company['nif'] }}</div>
    @endif
@endsection
