@extends('pdf.layout')

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">{{ mb_strtoupper($voucher->type->label()) }}</div>
        <div class="bold">N° {{ $voucher->number }}</div>
        <div class="muted">Émis le {{ $voucher->created_at->format('d/m/Y') }}</div>
        <div style="margin-top: 4pt;"><span class="badge">{{ $voucher->status->label() }}</span></div>
    @endcomponent

    <div class="box" style="margin-bottom: 12pt;">
        <table>
            <tr><td class="muted" style="width: 25%;">Bénéficiaire</td><td class="bold">{{ $voucher->beneficiary ?? '—' }}</td></tr>
            <tr><td class="muted">Montant</td><td class="bold" style="font-size: 12pt;">{{ \App\Services\Support\Money::format($voucher->amount) }}</td></tr>
            <tr><td class="muted">Objet</td><td>{{ $voucher->description }}</td></tr>
        </table>
    </div>

    @if ($voucher->lines)
        <table class="items">
            <thead><tr><th>Désignation</th><th class="right">Quantité</th><th class="right">Montant</th></tr></thead>
            <tbody>
                @foreach ($voucher->lines as $line)
                    <tr><td>{{ $line['label'] ?? '' }}</td><td class="right">{{ $line['quantity'] ?? '' }}</td><td class="right">{{ isset($line['amount']) ? \App\Services\Support\Money::format($line['amount']) : '' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="bold" style="margin-top: 14pt;">Circuit de validation</div>
    <table class="items">
        <thead><tr><th>Étape</th><th>Décision</th><th>Par</th><th>Date</th><th>Commentaire</th></tr></thead>
        <tbody>
            <tr><td>Émission</td><td>—</td><td>{{ $voucher->creator?->name }}</td><td>{{ $voucher->created_at->format('d/m/Y H:i') }}</td><td></td></tr>
            @foreach ($voucher->approvalRequest?->actions ?? [] as $action)
                <tr><td>{{ $action->step?->name }}</td><td>{{ $action->decision->label() }}</td><td>{{ $action->user->name }}</td><td>{{ $action->created_at->format('d/m/Y H:i') }}</td><td>{{ $action->reason }} {{ $action->comment }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 36pt;"><tr><td>Demandeur<br><br>____________</td><td class="center">Approbateur<br><br>____________</td><td class="right">Bénéficiaire<br><br>____________</td></tr></table>
@endsection
