{{-- Liste de présence journalière d'un chantier (base de la paie des journaliers) --}}
@extends('pdf.layout', ['docSubtitle' => ucfirst($sheet->work_date->translatedFormat('l j F Y'))])

@section('content')
    <table>
        <tr>
            <td class="box-fill" style="width: 60%;"><div class="label">Chantier</div><div class="bold navy" style="font-size: 12px;">{{ $sheet->site->code }} · {{ $sheet->site->name }}</div><div>{{ $sheet->site->address }}{{ $sheet->site->city ? ', '.$sheet->site->city : '' }}</div></td>
            <td style="width: 3%;"></td>
            <td class="box" style="width: 37%;"><div class="label">Responsable</div><div class="bold">{{ $sheet->site->manager?->full_name ?? '—' }}</div><div class="label" style="margin-top: 4px;">Statut</div><div>{{ $sheet->status === 'validated' ? 'Validée le '.$sheet->validated_at?->format('d/m/Y H:i').' par '.$sheet->validator?->name : 'Brouillon' }}</div></td>
        </tr>
    </table>
    @php $entries = $sheet->entries->sortBy(fn ($e) => $e->employee->last_name); @endphp
    <div class="section-title">Pointage ({{ $entries->whereIn('status', ['present', 'late', 'half'])->count() }} présents / {{ $entries->count() }})</div>
    <table class="lines">
        <thead><tr><th style="width: 4%;">#</th><th style="width: 11%;">Matricule</th><th>Nom et prénom</th><th style="width: 14%;">Fonction</th><th style="width: 12%;">Statut</th><th style="width: 8%;">Arrivée</th><th style="width: 8%;">Départ</th><th class="right" style="width: 7%;">H. sup</th><th class="right" style="width: 9%;">Montant</th><th style="width: 11%;">Signature</th></tr></thead>
        <tbody>
            @foreach ($entries as $entry)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td class="muted">{{ $loop->iteration }}</td><td>{{ $entry->employee->matricule }}</td><td class="bold">{{ $entry->employee->full_name }}</td>
                    <td class="small">{{ $entry->employee->position?->title }}</td><td>{{ erp_status('attendance', $entry->status)[0] }}</td>
                    <td>{{ $entry->check_in ? substr($entry->check_in, 0, 5) : '' }}</td><td>{{ $entry->check_out ? substr($entry->check_out, 0, 5) : '' }}</td>
                    <td class="right">{{ (float) $entry->overtime_hours ? qty($entry->overtime_hours) : '' }}</td>
                    <td class="right">{{ number_format($entry->dayFraction() * (float) $entry->daily_rate, 2, ',', ' ') }}</td><td></td>
                </tr>
            @endforeach
            <tr><td colspan="8" class="right bold">TOTAL DE LA JOURNÉE</td><td class="right bold navy">{{ money($entries->sum(fn ($e) => $e->dayFraction() * (float) $e->daily_rate)) }}</td><td></td></tr>
        </tbody>
    </table>
    @if ($sheet->notes)<p style="margin-top: 10px;"><span class="label">Observations :</span> {{ $sheet->notes }}</p>@endif
    <table style="margin-top: 20px;">
        <tr>
            <td style="width: 48%;"><div class="label">Pointé par</div><div class="small bold">{{ $sheet->recorder?->name }}</div><div class="signature" style="margin-top: 4px; height: 56px;"></div></td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;"><div class="label">Visa du conducteur de travaux</div><div class="small bold">{{ $sheet->validator?->name }}</div><div class="signature" style="margin-top: 4px; height: 56px;"></div></td>
        </tr>
    </table>
@endsection
