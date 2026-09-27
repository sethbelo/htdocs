{{-- Bon interne (caisse, décaissement, achat, sortie…) avec circuit de validation et visas --}}
@php($money = fn ($v) => \App\Services\Support\Money::format($v))
@extends('pdf.layout', [
    'docTitle' => $voucher->type->label(),
    'docReference' => 'N° '.$voucher->number,
    'docSubtitle' => 'Émis le '.$voucher->created_at->format('d/m/Y à H:i'),
    'watermark' => match ($voucher->status->value) { 'draft' => 'BROUILLON', 'pending_approval' => 'EN ATTENTE', 'rejected' => 'REFUSÉ', 'cancelled' => 'ANNULÉ', default => null },
])

@section('content')
    <table>
        <tr>
            <td style="width: 58%; vertical-align: top;" class="box-fill">
                <div class="label">Objet</div>
                <div class="bold ink" style="font-size: 10.5pt; margin: 2pt 0 5pt;">{{ $voucher->description ?: '—' }}</div>
                <table class="kv">
                    <tr><td class="k">Bénéficiaire</td><td class="bold">{{ $voucher->beneficiary ?? '—' }}</td></tr>
                    <tr><td class="k">Émis par</td><td>{{ $voucher->creator?->name }}</td></tr>
                    @if ($voucher->site)<tr><td class="k">Siège</td><td>{{ $voucher->site->name }}</td></tr>@endif
                </table>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 39%; vertical-align: top;" class="box">
                <div class="label">Statut</div>
                <div style="margin: 2pt 0 7pt;">@include('pdf.partials.status', ['status' => $voucher->status])</div>
                <div class="label">Montant</div>
                <div class="bold ink" style="font-size: 16pt;">{{ $money($voucher->amount) }}</div>
                <div class="small muted" style="margin-top: 3pt;">{{ \App\Services\Support\Money::inWords($voucher->amount) }}</div>
            </td>
        </tr>
    </table>

    @if ($voucher->lines)
        <div class="section-title">Détail</div>
        <table class="lines">
            <thead><tr><th style="width: 5%;">#</th><th>Désignation</th><th class="right" style="width: 14%;">Quantité</th><th class="right" style="width: 18%;">Montant</th></tr></thead>
            <tbody>
                @foreach ($voucher->lines as $line)
                    <tr class="{{ $loop->even ? 'even' : '' }}"><td class="muted">{{ $loop->iteration }}</td><td>{{ $line['label'] ?? '' }}</td><td class="right">{{ $line['quantity'] ?? '' }}</td><td class="right nowrap">{{ isset($line['amount']) ? $money($line['amount']) : '' }}</td></tr>
                @endforeach
                <tr class="total"><td colspan="3" class="right">TOTAL</td><td class="right nowrap">{{ $money($voucher->amount) }}</td></tr>
            </tbody>
        </table>
    @endif

    <div class="section-title">Circuit de validation</div>
    <table class="lines">
        <thead><tr><th>Étape</th><th>Décision</th><th>Par</th><th>Date</th><th>Commentaire</th></tr></thead>
        <tbody>
            <tr><td>Émission</td><td>-</td><td>{{ $voucher->creator?->name }}</td><td class="nowrap">{{ $voucher->created_at->format('d/m/Y H:i') }}</td><td></td></tr>
            @foreach ($voucher->approvalRequest?->actions ?? [] as $action)
                <tr class="{{ $loop->odd ? 'even' : '' }}"><td>{{ $action->step?->name }}</td><td>{{ $action->decision->label() }}</td><td>{{ $action->user->name }}</td><td class="nowrap">{{ $action->created_at->format('d/m/Y H:i') }}</td><td>{{ $action->reason }} {{ $action->comment }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.signatures', ['type' => 'voucher', 'names' => [0 => [$voucher->creator?->name, $voucher->created_at]]])
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($voucher->number, 1, 28) }}" style="height: 22pt;"></div>
@endsection
