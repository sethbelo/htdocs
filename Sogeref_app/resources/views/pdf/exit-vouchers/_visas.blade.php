{{-- Visas d'un bon de sortie. Variables : $voucher, $roles (liste [libellé, nom, date]) --}}
<table>
    <tr>
        @foreach ($roles as [$role, $name, $date])
            <td style="width: {{ floor(96 / count($roles)) }}%; vertical-align: top;">
                <div class="label">{{ $role }}</div>
                <div class="small bold" style="min-height: 13px;">{{ $name }}</div>
                <div class="small muted" style="min-height: 13px;">{{ $date?->format('d/m/Y H:i') }}</div>
                <div class="signature" style="height: 52px; margin-top: 3px;"></div>
            </td>
            @if (! $loop->last)<td style="width: 1.3%;"></td>@endif
        @endforeach
    </tr>
</table>
