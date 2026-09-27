{{-- Bon à tirer (BAT) --}}
@extends('pdf.layout', [
    'docTitle' => 'Bon à tirer',
    'docReference' => $proof->number.' · version '.$proof->version->version,
    'docSubtitle' => 'Commande '.$proof->order->number.' · '.$proof->order->client->displayName(),
    'watermark' => $proof->status->value === 'pending' ? 'ÉPREUVE' : ($proof->status->value === 'rejected' ? 'REFUSÉ' : null),
])

@section('content')
    <table>
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 6pt;"><div class="box-fill">
                <table class="kv">
                    <tr><td class="k">Client</td><td class="bold">{{ $proof->order->client->displayName() }}</td></tr>
                    <tr><td class="k">Graphiste</td><td>{{ $proof->designJob->designer?->name ?? '—' }}</td></tr>
                    <tr><td class="k">Fichier</td><td>{{ $proof->version->original_name }}</td></tr>
                    @if ($proof->locked_checksum)<tr><td class="k">Empreinte</td><td class="small mono">{{ \Illuminate\Support\Str::limit($proof->locked_checksum, 32, '…') }}</td></tr>@endif
                </table>
            </div></td>
            <td style="width: 40%; vertical-align: top;"><div class="box">
                <div class="label">Statut</div>
                <div style="margin: 2pt 0 6pt;">@include('pdf.partials.status', ['status' => $proof->status])</div>
                @if ($proof->validated_at)
                    <div class="small">{{ $proof->validated_at->format('d/m/Y à H:i') }} · {{ $proof->validator_type?->label() }}</div>
                    <div class="bold">{{ $proof->validator_name }}</div>
                @endif
            </div></td>
        </tr>
    </table>

    <div class="section-title">Spécifications à imprimer</div>
    <table class="lines">
        <thead><tr><th>Produit</th><th>Spécifications</th><th class="right" style="width: 12%;">Quantité</th></tr></thead>
        <tbody>
            @foreach ($proof->order->items as $item)
                <tr class="{{ $loop->even ? 'even' : '' }}"><td class="bold">{{ $item->description }}</td><td class="small">{{ $item->specSummary() }}</td><td class="right">{{ (float) $item->quantity }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if (str_starts_with((string) $proof->version->mime_type, 'image/') && Storage::disk($proof->version->disk)->exists($proof->version->path))
        <div class="center" style="margin: 12pt 0;">
            <img src="data:{{ $proof->version->mime_type }};base64,{{ base64_encode(Storage::disk($proof->version->disk)->get($proof->version->path)) }}" style="max-width: 100%; max-height: 300pt; border: .8pt solid #dcdce6;">
        </div>
    @endif

    <div class="box-accent" style="margin-top: 10pt;">
        @if ($proof->validated_at && $proof->comment)<div class="bold">« {{ $proof->comment }} »</div>@endif
        <div class="small">Je soussigné(e) valide le présent BAT (textes, orthographe, couleurs, dimensions, format) et autorise son impression. Aucune réclamation ne pourra porter sur les éléments validés. Les couleurs à l’écran peuvent différer légèrement du rendu imprimé (CMJN).</div>
    </div>

    @include('pdf.partials.signatures', ['type' => 'proof', 'names' => [0 => [$proof->designJob->designer?->name, $proof->created_at], 1 => [$proof->validator_type?->value === 'client' ? $proof->validator_name : null, $proof->validator_type?->value === 'client' ? $proof->validated_at : null]]])
@endsection
