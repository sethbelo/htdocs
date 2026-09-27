{{-- Bon interne : achat, décaissement ou sortie matériel, avec circuit de visas --}}
@extends('pdf.layout', ['docSubtitle' => 'Établi le '.$voucher->voucher_date->format('d/m/Y')])

@section('content')
    <table>
        <tr>
            <td style="width: 58%; vertical-align: top;" class="box-fill">
                <div class="label">Objet</div>
                <div class="bold navy" style="font-size: 12px; margin: 3px 0 6px;">{{ $voucher->purpose }}</div>
                <table class="small">
                    <tr><td class="muted" style="width: 36%;">Bénéficiaire</td><td class="bold">{{ $voucher->beneficiaryName() }}</td></tr>
                    @if ($voucher->contact)<tr><td class="muted">Fournisseur</td><td>{{ $voucher->contact->display_name }}</td></tr>@endif
                    @if ($voucher->site)<tr><td class="muted">Chantier</td><td>{{ $voucher->site->code }} · {{ $voucher->site->name }}</td></tr>@endif
                    @if ($voucher->warehouse)<tr><td class="muted">Magasin</td><td>{{ $voucher->warehouse->name }}</td></tr>@endif
                    @if ($voucher->manufacturingOrder)<tr><td class="muted">Ordre de fabrication</td><td>{{ $voucher->manufacturingOrder->number }}</td></tr>@endif
                    @if ($voucher->salesOrder)<tr><td class="muted">Commande client</td><td>{{ $voucher->salesOrder->number }}</td></tr>@endif
                </table>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 39%; vertical-align: top;" class="box">
                <div class="label">Statut</div>
                <div class="bold" style="margin: 2px 0 8px;">{{ erp_status('voucher', $voucher->status)[0] }}</div>
                <div class="label">Montant</div>
                <div class="bold navy" style="font-size: 18px;">{{ money($voucher->amount, $voucher->currency_code) }}</div>
                @if ($voucher->type === 'decaissement')
                    <div class="small muted" style="margin-top: 4px;">{{ \App\Support\AmountInWords::make((float) $voucher->amount, $voucher->currency_code) }}</div>
                    <div class="small" style="margin-top: 4px;">Mode : {{ ['cash' => 'Espèces', 'mobile_money' => 'Mobile money', 'bank_transfer' => 'Virement', 'cheque' => 'Chèque'][$voucher->payment_method] ?? '—' }}{{ $voucher->financialAccount ? ' · '.$voucher->financialAccount->name : '' }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Détail</div>
    <table class="lines">
        <thead><tr><th style="width: 5%;">#</th><th>Désignation</th><th class="right" style="width: 12%;">Quantité</th><th style="width: 10%;">Unité</th><th class="right" style="width: 14%;">P.U.</th><th class="right" style="width: 15%;">Total</th></tr></thead>
        <tbody>
            @foreach ($voucher->lines as $line)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td>{{ $line->product?->sku ? $line->product->sku.' — ' : '' }}{{ $line->description }}</td>
                    <td class="right">{{ qty($line->quantity) }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                    <td class="right bold">{{ number_format($line->total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr><td colspan="5" class="right bold">TOTAL</td><td class="right bold navy">{{ money($voucher->amount, $voucher->currency_code) }}</td></tr>
        </tbody>
    </table>

    @if ($voucher->notes)
        <div class="section-title">Observations</div>
        <p>{!! nl2br(e($voucher->notes)) !!}</p>
    @endif

    <div class="section-title">Circuit de validation</div>
    <table>
        <tr>
            @foreach ([['Demandé par', $voucher->requester?->name, $voucher->created_at], ['Approuvé par', $voucher->approver?->name, $voucher->approved_at], [$voucher->type === 'sortie' ? 'Remis par (magasin)' : 'Exécuté par', $voucher->executor?->name, $voucher->executed_at], [$voucher->type === 'sortie' ? 'Reçu par' : 'Bénéficiaire', null, null]] as [$role, $name, $date])
                <td style="width: 24%; vertical-align: top;">
                    <div class="label">{{ $role }}</div>
                    <div class="small bold" style="min-height: 13px;">{{ $name }}</div>
                    <div class="small muted" style="min-height: 13px;">{{ $date?->format('d/m/Y H:i') }}</div>
                    <div class="signature" style="height: 58px; margin-top: 3px;"></div>
                </td>
                @if (! $loop->last)<td style="width: 1.3%;"></td>@endif
            @endforeach
        </tr>
    </table>
@endsection
