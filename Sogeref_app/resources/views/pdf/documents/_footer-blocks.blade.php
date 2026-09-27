<table style="margin-top: 16px;">
    <tr>
        <td style="width: 58%; vertical-align: top; padding-right: 14px;">
            @if ($document->notes)
                <div class="section-title">N.B.</div>
                <div>{!! nl2br(e($document->notes)) !!}</div>
            @endif
            @if ($terms)
                <div class="section-title">Conditions</div>
                <div class="small">{!! nl2br(e($terms)) !!}</div>
            @endif
            @if ($type !== 'purchase_order' && data_get($company->settings, 'bank_details'))
                <div class="section-title">Règlement</div>
                <div class="small">Espèces à la caisse, M-Pesa ou virement : {{ data_get($company->settings, 'bank_details') }}</div>
            @endif
        </td>
        <td style="width: 42%; vertical-align: top;">
            @if ($type === 'quote')
                <div class="label" style="margin-top: 16px;">Bon pour accord — date, nom et signature du client</div>
                <div class="signature" style="margin-top: 4px;"></div>
            @endif
            <div class="label" style="margin-top: 12px;">Pour {{ $company->name }}</div>
            <div class="signature" style="margin-top: 4px;">
                @if ($company->seal_path && file_exists(storage_path('app/public/'.$company->seal_path)))<img src="{{ storage_path('app/public/'.$company->seal_path) }}" style="height: 64px; margin: 3px;">@endif
            </div>
        </td>
    </tr>
</table>
