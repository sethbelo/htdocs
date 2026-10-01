{{-- Factures réglées par ce paiement (ventilation). Variables : $payment, $compact (bool) --}}
@php $compact ??= false; @endphp
@if ($payment->allocations->isNotEmpty())
    <table class="lines" style="margin-top: {{ $compact ? 6 : 10 }}px;">
        <thead><tr><th>Facture réglée</th><th class="right">Montant facture</th><th class="right">Réglé ici</th><th class="right">Reste dû</th></tr></thead>
        <tbody>
            @foreach ($payment->allocations as $allocation)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="bold">{{ $allocation->document?->number ?? '—' }}</td>
                    <td class="right">{{ money($allocation->document?->total ?? 0, $payment->currency_code) }}</td>
                    <td class="right bold">{{ money($allocation->amount, $payment->currency_code) }}</td>
                    <td class="right">{{ money($allocation->document?->balance_due ?? 0, $payment->currency_code) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
