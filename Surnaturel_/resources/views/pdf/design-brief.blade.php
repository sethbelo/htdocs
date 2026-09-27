{{-- Fiche technique d'impression (bon à imprimer) --}}
@php
    $order = $job->order;
    $num = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 1, ',', ' '), '0'), ',');
    $validated = $brief->isValidatedForPrint();
@endphp
@extends('pdf.layout', [
    'docTitle' => 'Fiche technique d’impression',
    'docReference' => $order->number,
    'docSubtitle' => $order->client->displayName().' · délai '.($order->due_at?->format('d/m/Y H:i') ?? 'non défini'),
    'watermark' => $validated ? null : 'NON VALIDÉE',
])

@section('content')
    <table>
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 8pt;">
                <div class="box-fill">
                    <div class="label">Projet</div>
                    <div class="bold ink" style="font-size: 11pt; margin: 2pt 0 5pt;">{{ $brief->title ?: $order->number }}</div>
                    <table class="kv">
                        <tr><td class="k">Client</td><td class="bold">{{ $order->client->displayName() }}</td></tr>
                        <tr><td class="k">Commercial</td><td>{{ $order->salesRep?->name ?? '-' }}</td></tr>
                        <tr><td class="k">Graphiste</td><td>{{ $job->designer?->name ?? '-' }}</td></tr>
                        <tr><td class="k">Fichier final</td><td>{{ $brief->final_file_name ?: ($job->latestVersion?->original_name ?? '-') }}{{ $job->latestVersion ? ' (v'.$job->latestVersion->version.')' : '' }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="box" style="text-align: center;">
                    <div class="label">Format fini</div>
                    <div class="bold ink" style="font-size: 15pt; margin-top: 2pt;">{{ $num($brief->width_mm) }} × {{ $num($brief->height_mm) }} mm</div>
                    <div class="magenta bold">{{ $brief->final_format ?: 'Format libre' }} · {{ $brief->orientation === 'paysage' ? 'Paysage' : 'Portrait' }}</div>
                    <div class="small muted" style="margin-top: 2pt;">Fonds perdus {{ $num($brief->bleed_mm) }} mm · format ouvert {{ $num((float) $brief->width_mm + 2 * (float) $brief->bleed_mm) }} × {{ $num((float) $brief->height_mm + 2 * (float) $brief->bleed_mm) }} mm</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Spécifications d’impression</div>
    <table class="lines">
        <tbody>
            <tr><td class="bold" style="width: 25%;">Couleurs</td><td style="width: 25%;">{{ $brief->colors ?: '-' }}</td><td class="bold" style="width: 25%;">Quantité</td><td class="bold magenta">{{ $brief->quantity ? number_format($brief->quantity, 0, ',', ' ').' ex.' : '-' }}</td></tr>
            <tr class="even"><td class="bold">Papier / support</td><td>{{ $brief->paper ?: '-' }}</td><td class="bold">Grammage</td><td>{{ $brief->grammage ? $brief->grammage.' g/m²' : '-' }}</td></tr>
            <tr><td class="bold">Finitions</td><td>{{ $brief->finishing ?: '-' }}</td><td class="bold">Pages</td><td>{{ $brief->pages ?: '-' }}</td></tr>
        </tbody>
    </table>
    @if ($brief->print_notes)<div class="box-accent" style="margin-top: 8pt;"><div class="label">Consignes pour l’imprimeur</div><div>{!! nl2br(e($brief->print_notes)) !!}</div></div>@endif

    <div class="section-title">Lignes de la commande</div>
    <table class="lines">
        <thead><tr><th>Produit</th><th>Spécifications commandées</th><th class="right" style="width: 12%;">Quantité</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}"><td class="bold">{{ $item->description }}</td><td class="small">{{ $item->specSummary() }}</td><td class="right">{{ (float) $item->quantity }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 4pt;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8pt;">
                <div class="section-title">Contrôles pré-presse</div>
                <table class="kv">
                    @foreach (\App\Models\DesignBrief::CHECKLIST as $key => $label)
                        <tr><td style="width: 14pt;" class="bold {{ ($brief->checklist[$key] ?? false) ? '' : 'magenta' }}">{{ ($brief->checklist[$key] ?? false) ? '[x]' : '[  ]' }}</td><td>{{ $label }}</td></tr>
                    @endforeach
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="section-title">Brief créatif</div>
                @if ($brief->objective)<p class="small"><span class="bold">Objectif :</span> {{ $brief->objective }}</p>@endif
                @if ($brief->target_audience)<p class="small"><span class="bold">Cible :</span> {{ $brief->target_audience }}</p>@endif
                @if ($brief->style_notes)<p class="small"><span class="bold">Direction artistique :</span> {{ $brief->style_notes }}</p>@endif
            </td>
        </tr>
    </table>

    @if ($validated)
        <div style="margin-top: 8pt;"><span class="stamp">Bon pour impression · {{ $brief->validator?->name }} · {{ $brief->validated_for_print_at->format('d/m/Y H:i') }}</span></div>
    @endif

    @include('pdf.partials.signatures', ['type' => 'print_order', 'names' => [0 => [$job->designer?->name, null], 1 => [$validated ? $brief->validator?->name : null, $brief->validated_for_print_at]]])
    <div class="center" style="margin-top: 10pt;"><img src="{{ \App\Services\Support\Codes::barcodeDataUri($order->number, 1, 28) }}" style="height: 22pt;"></div>
@endsection
