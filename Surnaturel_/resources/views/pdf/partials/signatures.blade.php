{{--
    Bloc de signatures des responsables (Paramètres › Documents).
    $type : clé du document ; $names (optionnel) : [libellé => [nom, date]] pour pré-remplir.
--}}
@php
    $signatories = \App\Support\DocumentSignatures::for($type);
    $names ??= [];
    $count = max(1, count($signatories));
    $width = floor((100 - ($count - 1) * 1.5) / $count);
@endphp
@if ($signatories)
    <div class="section-title">{{ $title ?? 'Visas & signatures' }}</div>
    <table style="page-break-inside: avoid;">
        <tr>
            @foreach ($signatories as $index => $role)
                @php([$name, $date] = $names[$role] ?? $names[$index] ?? [null, null])
                <td style="width: {{ $width }}%; vertical-align: top;">
                    <div class="label">{{ $role }}</div>
                    <div class="small bold" style="min-height: 10pt;">{{ $name }}</div>
                    <div class="small muted" style="min-height: 10pt;">{{ $date instanceof \DateTimeInterface ? $date->format('d/m/Y H:i') : $date }}</div>
                    <div class="signature" style="margin-top: 2pt;"></div>
                </td>
                @if (! $loop->last)<td style="width: 1.5%;"></td>@endif
            @endforeach
        </tr>
    </table>
@endif
