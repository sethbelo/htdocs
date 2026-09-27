@extends('pdf.layout')

@section('content')
    @component('pdf.partials.company-header', ['company' => $company])
        <div style="font-size: 16pt;" class="bold">BON À TIRER</div>
        <div class="bold">{{ $proof->number }} · version {{ $proof->version->version }}</div>
        <div>Commande <span class="bold">{{ $proof->order->number }}</span></div>
        <div style="margin-top: 4pt;"><span class="badge">{{ $proof->status->label() }}</span></div>
    @endcomponent

    <div class="box" style="margin-bottom: 12pt;">
        <table>
            <tr><td class="muted" style="width: 30%;">Client</td><td class="bold">{{ $proof->order->client->displayName() }}</td></tr>
            <tr><td class="muted">Graphiste</td><td>{{ $proof->designJob->designer?->name }}</td></tr>
            <tr><td class="muted">Fichier</td><td>{{ $proof->version->original_name }}</td></tr>
            @if ($proof->locked_checksum)<tr><td class="muted">Empreinte SHA-256</td><td class="small">{{ $proof->locked_checksum }}</td></tr>@endif
        </table>
    </div>

    <table class="items">
        <thead><tr><th>Produit</th><th>Spécifications</th><th class="right">Quantité</th></tr></thead>
        <tbody>
            @foreach ($proof->order->items as $item)
                <tr><td>{{ $item->description }}</td><td class="small">{{ $item->specSummary() }}</td><td class="right">{{ (float) $item->quantity }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if (str_starts_with((string) $proof->version->mime_type, 'image/') && Storage::disk($proof->version->disk)->exists($proof->version->path))
        <div class="center" style="margin: 14pt 0;">
            <img src="data:{{ $proof->version->mime_type }};base64,{{ base64_encode(Storage::disk($proof->version->disk)->get($proof->version->path)) }}" style="max-width: 100%; max-height: 330pt; border: 0.5pt solid #d4d4d8;">
        </div>
    @endif

    <div class="box" style="margin-top: 14pt;">
        <div class="bold" style="margin-bottom: 6pt;">Validation</div>
        @if ($proof->validated_at)
            <div>{{ $proof->status->label() }} le {{ $proof->validated_at->format('d/m/Y à H:i') }} par {{ $proof->validator_type?->label() }} : <span class="bold">{{ $proof->validator_name }}</span></div>
            <div class="small muted">Enregistré par {{ $proof->validator?->name }}</div>
            @if ($proof->comment)<div style="margin-top: 4pt;">« {{ $proof->comment }} »</div>@endif
        @else
            <div class="small">Je soussigné(e) valide le présent BAT (textes, couleurs, dimensions) et autorise son impression. Aucune réclamation ne pourra porter sur les éléments validés.</div>
            <table style="margin-top: 20pt;"><tr><td>Nom : ______________________</td><td>Date : ____/____/______</td><td class="right">Signature : ______________</td></tr></table>
        @endif
    </div>
@endsection
