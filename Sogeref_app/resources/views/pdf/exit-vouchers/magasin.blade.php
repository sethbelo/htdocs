{{-- ═══ BON DE SORTIE « MAGASIN » — liste de préparation sans prix ═══ --}}
@extends('pdf.layout', ['docTitle' => 'BON DE SORTIE MAGASIN', 'docReference' => 'N° '.$voucher->number, 'docSubtitle' => 'Établi le '.$voucher->voucher_date->format('d/m/Y')])

@section('content')
    <table>
        <tr>
            <td class="box-fill" style="width: 32%;"><div class="label">Magasin</div><div class="bold">{{ $voucher->warehouse?->name ?? '—' }}</div></td>
            <td style="width: 2%;"></td>
            <td class="box-fill" style="width: 32%;"><div class="label">Demandeur</div><div class="bold">{{ $voucher->beneficiaryName() }}</div></td>
            <td style="width: 2%;"></td>
            <td class="box-fill" style="width: 32%;"><div class="label">Destination</div><div class="bold">{{ $voucher->site ? $voucher->site->code.' · '.$voucher->site->name : ($voucher->manufacturingOrder?->number ?? 'Usage interne') }}</div></td>
        </tr>
    </table>
    <p style="margin: 10px 0 0;"><span class="label">Motif :</span> <strong>{{ $voucher->purpose }}</strong></p>

    <div class="section-title">Articles à préparer</div>
    <table class="lines">
        <thead><tr><th style="width: 5%;">✓</th><th style="width: 15%;">Code</th><th>Désignation</th><th style="width: 9%;">Unité</th><th class="right" style="width: 12%;">Demandé</th><th class="right" style="width: 12%;">Sorti</th><th style="width: 16%;">Emplacement</th></tr></thead>
        <tbody>
            @foreach ($voucher->lines as $line)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td><span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #104A63;"></span></td>
                    <td class="small">{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->description }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="right bold">{{ qty($line->quantity) }}</td>
                    <td class="right" style="border-bottom: 1px dotted #8a9dac;"></td>
                    <td style="border-bottom: 1px dotted #8a9dac;"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="small muted" style="margin-top: 6px;">{{ $voucher->lines->count() }} ligne(s) · total demandé {{ qty($voucher->lines->sum('quantity')) }} unité(s). Toute différence entre « demandé » et « sorti » doit être justifiée ci-dessous.</p>
    <div class="box" style="height: 46px; margin-top: 6px;"><span class="label">Observations du magasinier</span><br>{{ $voucher->notes }}</div>

    <div class="section-title">Visas</div>
    @include('pdf.exit-vouchers._visas', ['roles' => [['Demandé par', $voucher->requester?->name, $voucher->created_at], ['Approuvé par', $voucher->approver?->name, $voucher->approved_at], ['Préparé / remis par', $voucher->executor?->name, $voucher->executed_at], ['Reçu par', null, null]]])
@endsection
