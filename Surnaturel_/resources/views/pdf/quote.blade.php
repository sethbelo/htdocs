@extends('pdf.layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">{{ mb_strtoupper($quote->type->label()) }}</div>
        <div class="bold">N° {{ $quote->number }}</div>
        <div class="muted">Date : {{ $quote->created_at->format('d/m/Y') }}</div>
        @if ($quote->valid_until)<div class="muted">Valable jusqu’au {{ $quote->valid_until->format('d/m/Y') }}</div>@endif
    @endcomponent

    <div class="box" style="width: 48%; margin-left: 52%; margin-bottom: 14pt;">
        <div class="muted small">CLIENT</div>
        <div class="bold">{{ $quote->client->displayName() }}</div>
        <div class="small">{{ $quote->client->address }} {{ $quote->client->city }} · {{ $quote->client->phone }}</div>
    </div>

    <table class="items">
        <thead><tr><th style="width: 55%;">Désignation</th><th class="right">Qté</th><th class="right">P.U.</th><th class="right">TVA</th><th class="right">Total TTC</th></tr></thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr><td>{{ $item->description }}<div class="small muted">{{ $item->specSummary() }}</div></td><td class="right">{{ (float) $item->quantity }}</td><td class="right">{{ $money($item->unit_price) }}</td><td class="right">{{ (float) $item->tax_rate }} %</td><td class="right">{{ $money($item->total) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table style="margin-top: 10pt;"><tr><td style="width: 55%;" class="small">{{ $quote->notes }}</td><td>
        <table class="totals">
            <tr><td>Total HT</td><td class="right">{{ $money((float) $quote->total - (float) $quote->tax_total) }}</td></tr>
            <tr><td>TVA</td><td class="right">{{ $money($quote->tax_total) }}</td></tr>
            <tr class="grand"><td>TOTAL TTC</td><td class="right">{{ $money($quote->total) }}</td></tr>
        </table>
    </td></tr></table>
    <table style="margin-top: 30pt;"><tr><td class="small">Établi par {{ $quote->salesRep?->name }}</td><td class="right small">Bon pour accord — date & signature<br><br><br>________________</td></tr></table>
@endsection
