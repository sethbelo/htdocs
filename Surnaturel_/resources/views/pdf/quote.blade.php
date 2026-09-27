{{-- Devis / facture pro forma --}}
@php($money = fn ($v) => \App\Services\Support\Money::format($v))
@extends('pdf.layout', [
    'docTitle' => $quote->type->label(),
    'docReference' => 'N° '.$quote->number,
    'docSubtitle' => 'Établi le '.$quote->created_at->format('d/m/Y').($quote->valid_until ? ' · valable jusqu’au '.$quote->valid_until->format('d/m/Y') : ''),
])

@section('content')
    <table>
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8pt;">
                <div class="box-fill">
                    <table class="kv">
                        <tr><td class="k">Statut</td><td>@include('pdf.partials.status', ['status' => $quote->status])</td></tr>
                        <tr><td class="k">Conseiller</td><td class="bold">{{ $quote->salesRep?->name ?? '—' }}</td></tr>
                        <tr><td class="k">Contact</td><td>{{ $quote->salesRep?->email }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">@include('pdf.partials.party', ['client' => $quote->client])</td>
        </tr>
    </table>

    <div class="section-title">Proposition</div>
    <table class="lines">
        <thead><tr><th style="width: 4%;">#</th><th>Désignation & spécifications techniques</th><th class="right" style="width: 9%;">Qté</th><th class="right" style="width: 14%;">P.U.</th><th class="right" style="width: 8%;">TVA</th><th class="right" style="width: 15%;">Total TTC</th></tr></thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td><span class="bold">{{ $item->description }}</span>@if ($item->specSummary())<div class="small muted">{{ $item->specSummary() }}</div>@endif</td>
                    <td class="right">{{ (float) $item->quantity }}</td>
                    <td class="right nowrap">{{ $money($item->unit_price) }}</td>
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
                    <div class="label">Montant de la proposition</div>
                    <div class="bold" style="margin-top: 2pt;">{{ \App\Services\Support\Money::inWords($quote->total) }}</div>
                </div>
                @if ($quote->notes)<div class="label" style="margin-top: 8pt;">Conditions & remarques</div><p class="small">{!! nl2br(e($quote->notes)) !!}</p>@endif
                <p class="small muted">Les travaux démarrent à réception du bon pour accord et de l’acompte. Délais indicatifs à compter de la validation du BAT.</p>
            </td>
            <td style="width: 46%; vertical-align: top;">
                <table class="totals">
                    <tr><td>Total HT</td><td class="right">{{ $money((float) $quote->total - (float) $quote->tax_total) }}</td></tr>
                    <tr><td>TVA</td><td class="right">{{ $money($quote->tax_total) }}</td></tr>
                    <tr class="grand"><td>TOTAL TTC</td><td class="right">{{ $money($quote->total) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @include('pdf.partials.signatures', ['type' => 'quote', 'title' => 'Bon pour accord'])
@endsection
