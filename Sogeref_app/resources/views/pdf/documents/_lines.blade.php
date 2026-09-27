<table class="lines">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th>Désignation</th>
            <th class="right" style="width: 9%;">Qté</th>
            <th style="width: 8%;">Unité</th>
            <th class="right" style="width: 13%;">P.U. HT</th>
            @if ($document->items->sum('discount_rate') > 0)<th class="right" style="width: 8%;">Rem.</th>@endif
            <th class="right" style="width: 8%;">TVA</th>
            <th class="right" style="width: 14%;">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($document->items as $item)
            <tr class="{{ $loop->even ? 'even' : '' }}">
                <td class="muted">{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $item->product?->sku ? $item->product->sku.' — ' : '' }}{{ $item->description }}</strong>
                    @if ($item->product?->description && $item->product->description !== $item->description)<br><em class="muted">{{ $item->product->description }}</em>@endif
                </td>
                <td class="right">{{ qty($item->quantity) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="right nowrap">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                @if ($document->items->sum('discount_rate') > 0)<td class="right">{{ (float) $item->discount_rate ? qty($item->discount_rate).' %' : '—' }}</td>@endif
                <td class="right">{{ qty($item->tax_rate) }} %</td>
                <td class="right nowrap bold">{{ number_format($item->subtotal, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
