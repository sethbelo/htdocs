{{-- Facture / facture d'acompte / avoir — A4 ou A5 --}}
@php
    $money = fn ($v) => \App\Services\Support\Money::format($v);
    $reference = $invoice->order?->number ?? $invoice->sale?->number;
@endphp
@extends('pdf.layout', [
    'docTitle' => $invoice->type->label(),
    'docReference' => 'N° '.$invoice->number,
    'docSubtitle' => 'Émise le '.$invoice->issued_at->format('d/m/Y').($invoice->due_at ? ' · échéance '.$invoice->due_at->format('d/m/Y') : ''),
    'watermark' => in_array($invoice->status->value, ['cancelled', 'void'], true) ? 'ANNULÉE' : null,
])

@section('content')
    <table>
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8pt;">
                <div class="box-fill">
                    <table class="kv">
                        <tr><td class="k">Statut</td><td>@include('pdf.partials.status', ['status' => $invoice->status])</td></tr>
                        @if ($reference)<tr><td class="k">{{ $invoice->sale ? 'Vente' : 'Commande' }}</td><td class="bold">{{ $reference }}</td></tr>@endif
                        <tr><td class="k">Siège</td><td>{{ $invoice->site?->name }}</td></tr>
                        <tr><td class="k">Établie par</td><td>{{ $invoice->creator?->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                @if ($invoice->client)
                    @include('pdf.partials.party', ['client' => $invoice->client, 'label' => 'Facturé à'])
                @else
                    <div class="box"><div class="label">Client</div><div class="value">Client comptoir</div></div>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Désignation</div>
    <table class="lines">
        <thead><tr><th style="width: 4%;">#</th><th>Désignation</th><th class="right" style="width: 9%;">Qté</th><th class="right" style="width: 14%;">P.U. HT</th><th class="right" style="width: 11%;">Remise</th><th class="right" style="width: 8%;">TVA</th><th class="right" style="width: 15%;">Total TTC</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ (float) $item->quantity }}</td>
                    <td class="right nowrap">{{ $money($item->unit_price) }}</td>
                    <td class="right nowrap">{{ (float) $item->discount ? $money($item->discount) : '-' }}</td>
                    <td class="right">{{ (float) $item->tax_rate }} %</td>
                    <td class="right bold nowrap">{{ $money($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 10pt; page-break-inside: avoid;">
        <tr>
            <td style="width: 54%; vertical-align: top; padding-right: 12pt;">
                <div class="box-accent">
                    <div class="label">Arrêtée la présente facture à la somme de</div>
                    <div class="bold" style="margin-top: 2pt;">{{ \App\Services\Support\Money::inWords($invoice->total) }}</div>
                </div>
                @if ($invoice->payments->isNotEmpty())
                    <div class="label" style="margin-top: 8pt;">Règlements reçus</div>
                    <table class="kv small">
                        @foreach ($invoice->payments as $payment)
                            <tr><td>{{ $payment->paid_at->format('d/m/Y') }} · {{ $payment->method->label() }} {{ $payment->reference }}</td><td class="right nowrap">{{ $money($payment->amount) }}</td></tr>
                        @endforeach
                    </table>
                @endif
                <table style="margin-top: 8pt;"><tr>
                    <td style="width: 58pt;"><img src="{{ \App\Services\Support\Codes::qrDataUri($reference ?? $invoice->number, 90) }}" style="width: 52pt; height: 52pt;"></td>
                    <td class="small muted" style="vertical-align: middle;">Scannez pour retrouver ce document.<br>Tout règlement doit mentionner la référence {{ $invoice->number }}.</td>
                </tr></table>
            </td>
            <td style="width: 46%; vertical-align: top;">
                <table class="totals">
                    <tr><td>Sous-total</td><td class="right">{{ $money($invoice->subtotal) }}</td></tr>
                    @if ((float) $invoice->discount_total)<tr><td>Remises</td><td class="right">- {{ $money($invoice->discount_total) }}</td></tr>@endif
                    <tr><td>Total HT</td><td class="right">{{ $money((float) $invoice->total - (float) $invoice->tax_total) }}</td></tr>
                    <tr><td>TVA</td><td class="right">{{ $money($invoice->tax_total) }}</td></tr>
                    <tr class="grand"><td>TOTAL TTC</td><td class="right">{{ $money($invoice->total) }}</td></tr>
                    <tr><td>Déjà réglé</td><td class="right">{{ $money($invoice->amount_paid) }}</td></tr>
                    <tr class="{{ $invoice->balanceDue() > 0 ? 'due' : '' }}"><td class="bold">Net à payer</td><td class="right bold">{{ $money($invoice->balanceDue()) }}</td></tr>
                </table>
                @if ($invoice->balanceDue() <= 0 && (float) $invoice->total > 0)
                    <div class="right" style="margin-top: 8pt;"><span class="stamp">Acquittée</span></div>
                @endif
            </td>
        </tr>
    </table>

    @include('pdf.partials.signatures', ['type' => 'invoice', 'title' => 'Signature & cachet'])
@endsection
