{{-- Rapport de chantier : synthèse, équipe, matériel, présences, bons, rapports et évaluation --}}
@extends('pdf.layout', ['docSubtitle' => $site->name])

@section('content')
    @php
        $labour = $attendance->flatMap->entries->sum(fn ($e) => $e->dayFraction() * (float) $e->daily_rate);
        $vouchersTotal = $site->vouchers->where('status', 'executed')->sum('amount');
        $lastEval = $site->evaluations->first();
    @endphp
    <table>
        <tr>
            <td class="box-fill" style="width: 58%; vertical-align: top;">
                <div class="label">Chantier</div>
                <div class="bold navy" style="font-size: 13px;">{{ $site->name }}</div>
                <div>{{ $site->address }}{{ $site->city ? ', '.$site->city : '' }}</div>
                @if ($site->description)<div class="small muted" style="margin-top: 4px;">{{ $site->description }}</div>@endif
            </td>
            <td style="width: 3%;"></td>
            <td class="box" style="width: 39%; vertical-align: top;">
                <table class="small">
                    <tr><td class="muted">Maître d’ouvrage</td><td class="right bold">{{ $site->contact?->display_name ?? 'Interne' }}</td></tr>
                    <tr><td class="muted">Responsable</td><td class="right">{{ $site->manager?->full_name ?? '—' }}</td></tr>
                    <tr><td class="muted">Période</td><td class="right">{{ $site->start_date?->format('d/m/Y') }} → {{ $site->planned_end_date?->format('d/m/Y') }}</td></tr>
                    <tr><td class="muted">Statut</td><td class="right">{{ erp_status('site', $site->status)[0] }}</td></tr>
                    <tr><td class="muted">Avancement</td><td class="right bold navy">{{ $site->progress }} %</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Indicateurs</div>
    <table>
        <tr>
            @foreach ([['Budget', money($site->budget)], ['Main-d’œuvre (journaliers)', money($labour)], ['Bons exécutés', money($vouchersTotal)], ['Effectif affecté', $site->members->count().' pers.'], ['Note d’évaluation', $lastEval ? number_format($lastEval->score, 1, ',', ' ').' / 5' : '—']] as [$l, $v])
                <td class="box" style="width: 19%;"><div class="label">{{ $l }}</div><div class="bold navy" style="font-size: 12px;">{{ $v }}</div></td>
                @if (! $loop->last)<td style="width: 1.2%;"></td>@endif
            @endforeach
        </tr>
    </table>

    <div class="section-title">Équipe affectée</div>
    <table class="lines">
        <thead><tr><th>Matricule</th><th>Nom</th><th>Rôle</th><th>Catégorie</th><th class="right">Taux / jour</th><th class="right">Jours pointés</th></tr></thead>
        <tbody>
            @foreach ($site->members as $member)
                @php $days = $attendance->flatMap->entries->where('employee_id', $member->id)->sum(fn ($e) => $e->dayFraction()); @endphp
                <tr class="{{ $loop->even ? 'even' : '' }}"><td>{{ $member->matricule }}</td><td class="bold">{{ $member->full_name }}</td><td>{{ $member->pivot->role }}</td><td>{{ \App\Models\Employee::CATEGORIES[$member->category] ?? '' }}</td><td class="right">{{ $member->isDaily() ? money($member->pivot->daily_rate ?: $member->daily_rate) : '—' }}</td><td class="right">{{ qty($days) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Matériel sur site</div>
    <table class="lines">
        <thead><tr><th>Code</th><th>Équipement</th><th>Affecté le</th><th>Retourné le</th><th>Observations</th></tr></thead>
        <tbody>
            @forelse ($site->equipmentAssignments as $a)
                <tr><td>{{ $a->equipment->code }}</td><td class="bold">{{ $a->equipment->name }}</td><td>{{ $a->assigned_at->format('d/m/Y') }}</td><td>{{ $a->returned_at?->format('d/m/Y') ?? 'Sur site' }}</td><td>{{ $a->notes }}</td></tr>
            @empty
                <tr><td colspan="5" class="center muted">Aucun matériel affecté.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Bons liés au chantier</div>
    <table class="lines">
        <thead><tr><th>N°</th><th>Type</th><th>Date</th><th>Objet</th><th>Statut</th><th class="right">Montant</th></tr></thead>
        <tbody>
            @forelse ($site->vouchers as $v)
                <tr><td>{{ $v->number }}</td><td>{{ config('erp.voucher_types.'.$v->type.'.short') }}</td><td>{{ $v->voucher_date->format('d/m/Y') }}</td><td>{{ $v->purpose }}</td><td>{{ erp_status('voucher', $v->status)[0] }}</td><td class="right">{{ money($v->amount) }}</td></tr>
            @empty
                <tr><td colspan="6" class="center muted">Aucun bon.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Journal de chantier (derniers rapports)</div>
    @foreach ($site->reports->take(8) as $report)
        <div style="border-left: 3px solid #FFCE31; padding: 4px 10px; margin-bottom: 6px;">
            <span class="bold">{{ $report->report_date->format('d/m/Y') }}</span> · <span class="muted">{{ \App\Models\SiteReport::WEATHER[$report->weather] ?? '' }} · {{ $report->workforce }} ouvriers · {{ $report->progress }} %</span><br>
            {{ $report->work_done }}@if ($report->issues)<br><span style="color: #F7313A;">Difficultés : {{ $report->issues }}</span>@endif
        </div>
    @endforeach

    @if ($lastEval)
        <div class="section-title">Évaluation du {{ $lastEval->evaluated_at->format('d/m/Y') }} — {{ $lastEval->evaluator?->name }}</div>
        <table class="lines">
            <tbody>
                @foreach (\App\Models\SiteEvaluation::CRITERIA as $key => $label)
                    <tr><td style="width: 45%;">{{ $label }}</td><td>@for ($i = 1; $i <= 5; $i++)<span style="display: inline-block; width: 16px; height: 8px; margin-right: 3px; background: {{ $i <= (int) ($lastEval->scores[$key] ?? 0) ? '#104A63' : '#e3e9ee' }};"></span>@endfor</td><td class="right bold">{{ $lastEval->scores[$key] ?? 0 }}/5</td></tr>
                @endforeach
            </tbody>
        </table>
        @if ($lastEval->comment)<p class="small"><em>{{ $lastEval->comment }}</em></p>@endif
    @endif
@endsection
