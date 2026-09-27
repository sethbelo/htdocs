{{-- Dossier de commande : synthèse du parcours devis → livraison et journal --}}
@extends('pdf.layout', ['docSubtitle' => $order->title])

@section('content')
    <table>
        <tr>
            <td class="box-fill" style="width: 58%; vertical-align: top;">
                <div class="label">Client</div><div class="bold navy" style="font-size: 13px;">{{ $order->contact->display_name }}</div>
                <div>{{ $order->contact->phone }} · {{ $order->contact->email }}</div>
                <div class="label" style="margin-top: 6px;">Objet</div><div class="bold">{{ $order->title }}</div>
                @if ($order->description)<div class="small">{{ $order->description }}</div>@endif
            </td>
            <td style="width: 3%;"></td>
            <td class="box" style="width: 39%; vertical-align: top;">
                <table class="small">
                    <tr><td class="muted">Nature</td><td class="right bold">{{ config('erp.order_kinds.'.$order->kind) }}</td></tr>
                    <tr><td class="muted">Étape actuelle</td><td class="right bold">{{ erp_status('order_stage', $order->stage)[0] }}</td></tr>
                    <tr><td class="muted">Montant TTC</td><td class="right bold navy">{{ money($order->total) }}</td></tr>
                    <tr><td class="muted">Encaissé</td><td class="right">{{ money($order->amount_paid) }} ({{ round($order->paidRatio() * 100) }} %)</td></tr>
                    <tr><td class="muted">Livraison prévue</td><td class="right">{{ $order->delivery_date?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="muted">Chargé d’affaires</td><td class="right">{{ $order->owner?->name }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Pièces du dossier</div>
    <table class="lines">
        <thead><tr><th>Pièce</th><th>Référence</th><th>Statut</th><th class="right">Montant</th></tr></thead>
        <tbody>
            <tr><td>Devis</td><td class="bold">{{ $order->quote?->number ?? '—' }}</td><td>{{ $order->quote ? erp_status('document', $order->quote->status->value)[0] : '' }}</td><td class="right">{{ $order->quote ? money($order->quote->total) : '' }}</td></tr>
            <tr><td>Facture</td><td class="bold">{{ $order->invoice?->number ?? '—' }}</td><td>{{ $order->invoice ? erp_status('document', $order->invoice->status->value)[0] : 'À émettre' }}</td><td class="right">{{ $order->invoice ? money($order->invoice->total) : '' }}</td></tr>
            <tr><td>{{ $order->kind === 'construction' ? 'Dossier de construction' : 'Ordre de fabrication' }}</td><td class="bold">{{ $order->manufacturingOrder?->number ?? '—' }}</td><td>{{ $order->manufacturingOrder ? erp_status('manufacturing', $order->manufacturingOrder->status)[0] : '' }}</td><td></td></tr>
            <tr><td>Projet</td><td class="bold">{{ $order->project?->code ?? '—' }}</td><td>{{ $order->project ? erp_status('project', $order->project->status)[0] : '' }}</td><td></td></tr>
            <tr><td>Chantier</td><td class="bold">{{ $order->site?->code ?? '—' }}</td><td>{{ $order->site ? erp_status('site', $order->site->status)[0] : '' }}</td><td></td></tr>
            @foreach ($order->vouchers as $v)
                <tr><td>{{ config('erp.voucher_types.'.$v->type.'.label') }}</td><td class="bold">{{ $v->number }}</td><td>{{ erp_status('voucher', $v->status)[0] }}</td><td class="right">{{ money($v->amount) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Articles commandés</div>
    <table class="lines">
        <thead><tr><th>Désignation</th><th class="right">Qté</th><th>Unité</th><th class="right">P.U.</th><th class="right">Total TTC</th></tr></thead>
        <tbody>
            @foreach ($order->quote?->items ?? [] as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}"><td>{{ $item->description }}</td><td class="right">{{ qty($item->quantity) }}</td><td>{{ $item->unit }}</td><td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td><td class="right bold">{{ number_format($item->total, 2, ',', ' ') }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Journal de suivi</div>
    <table class="lines">
        <thead><tr><th style="width: 18%;">Date</th><th>Événement</th><th style="width: 20%;">Par</th></tr></thead>
        <tbody>
            @foreach ($order->events->sortBy('id') as $event)
                <tr><td>{{ $event->created_at->format('d/m/Y H:i') }}</td><td><strong>{{ $event->title }}</strong>@if ($event->note)<br><span class="small muted">{{ $event->note }}</span>@endif</td><td>{{ $event->user?->name }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endsection
