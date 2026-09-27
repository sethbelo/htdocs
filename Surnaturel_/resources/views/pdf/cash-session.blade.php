@extends('pdf.layout')

@php($money = fn ($v) => \App\Services\Support\Money::format($v))

@section('content')
    @include('pdf.partials.ticket-header')
    <div class="center bold" style="font-size: 10pt;">{{ $session->closed_at ? 'RAPPORT Z — CLÔTURE' : 'RAPPORT X — SITUATION' }}</div>
    <div class="center">{{ $session->number }} · {{ $session->register->code }}</div>
    <div class="center small">{{ $session->register->site->name }} · entrepôt {{ $r['warehouse']?->name ?? '—' }}</div>
    <div class="dashed"></div>
    <table class="ticket">
        <tr><td>Caissier</td><td class="right">{{ $session->cashier->name }}</td></tr>
        <tr><td>Ouverture</td><td class="right">{{ $session->opened_at->format('d/m/Y H:i') }}</td></tr>
        @if ($session->closed_at)<tr><td>Clôture</td><td class="right">{{ $session->closed_at->format('d/m/Y H:i') }}</td></tr>@endif
        <tr><td>Durée</td><td class="right">{{ $r['duration'] }}</td></tr>
        <tr><td>Fond de caisse</td><td class="right">{{ $money($r['opening']) }}</td></tr>
    </table>
    <div class="dashed"></div>
    <div class="bold">Activité</div>
    <table class="ticket">
        <tr><td>Ventes ({{ $r['sales_count'] }})</td><td class="right">{{ $money($r['sales_total']) }}</td></tr>
        <tr><td>dont remises</td><td class="right">{{ $money($r['discounts']) }}</td></tr>
        <tr><td>dont TVA</td><td class="right">{{ $money($r['tax']) }}</td></tr>
        <tr><td>Panier moyen</td><td class="right">{{ $money($r['average_basket']) }}</td></tr>
        <tr><td>Ventes annulées</td><td class="right">{{ $r['voided_count'] }}</td></tr>
        <tr><td>Commandes encaissées ({{ $r['orders_count'] }})</td><td class="right">{{ $money($r['orders_total']) }}</td></tr>
        <tr><td>Ventes à crédit</td><td class="right">{{ $money($r['credit_sales']) }}</td></tr>
        <tr><td>Points fidélité</td><td class="right">{{ $r['loyalty_points'] }}</td></tr>
    </table>
    <div class="dashed"></div>
    <div class="bold">Encaissements par moyen</div>
    <table class="ticket">
        @forelse ($r['by_method'] as $row)
            <tr><td>{{ $row['method']->label() }}</td><td class="right">{{ $money($row['total']) }}</td></tr>
        @empty
            <tr><td colspan="2" class="muted">Aucun encaissement</td></tr>
        @endforelse
        <tr><td class="bold">Total encaissé</td><td class="right bold">{{ $money($r['collected_total']) }}</td></tr>
    </table>
    @if ($r['disbursements']->isNotEmpty())
        <div class="dashed"></div>
        <div class="bold">Décaissements</div>
        <table class="ticket">
            @foreach ($r['disbursements'] as $movement)
                <tr><td class="small">{{ $movement->created_at->format('H:i') }} {{ $movement->description }}</td><td class="right">{{ $money($movement->amount) }}</td></tr>
            @endforeach
        </table>
    @endif
    @if ($r['top_products']->isNotEmpty())
        <div class="dashed"></div>
        <div class="bold">Articles vendus</div>
        <table class="ticket">
            @foreach ($r['top_products'] as $product)
                <tr><td class="small">{{ (float) $product->quantity }} × {{ $product->description }}</td><td class="right">{{ $money($product->total) }}</td></tr>
            @endforeach
        </table>
    @endif
    <div class="dashed"></div>
    <div class="bold">Espèces</div>
    <table class="ticket">
        <tr><td>Solde théorique</td><td class="right bold">{{ $money($r['expected_cash']) }}</td></tr>
        @if ($session->closed_at)
            @foreach ($session->denominations ?? [] as $value => $count)
                <tr><td class="small">{{ $count }} × {{ rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',') }}</td><td class="right small">{{ $money((float) $value * $count) }}</td></tr>
            @endforeach
            <tr><td>Solde compté</td><td class="right bold">{{ $money($session->counted_balance) }}</td></tr>
            <tr><td>Écart</td><td class="right bold">{{ $money($session->difference) }}</td></tr>
            @if ((float) $session->deposit_amount > 0)<tr><td>Versé ({{ $session->depositAccount?->name }})</td><td class="right">{{ $money($session->deposit_amount) }}</td></tr>@endif
            <tr><td>Clôturé par</td><td class="right">{{ $session->closer?->name }}</td></tr>
            @if ($session->supervisor)<tr><td>Écart validé par</td><td class="right">{{ $session->supervisor->name }}</td></tr>@endif
        @endif
    </table>
    <div class="dashed"></div>
    <div class="small">Comptabilité : {{ $r['accounting']['entries'] }} écriture(s) · {{ $r['accounting']['synced'] ? 'synchronisée' : $r['accounting']['missing'].' en attente' }}{{ $session->accounting_synced_at ? ' le '.$session->accounting_synced_at->format('d/m H:i') : '' }}</div>
    @if ($session->notes)<div class="small">Obs. : {{ $session->notes }}</div>@endif
    <div class="dashed"></div>
    <div class="center small" style="margin-top: 16pt;">Signature caissier ______________<br><br>Signature responsable ______________</div>
@endsection
