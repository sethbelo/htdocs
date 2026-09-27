{{-- Carte de service — recto (85,6 × 54 mm) --}}
<div class="abs" style="left: 0; top: 0; width: 85.6mm; height: 12mm; background: #16161D;"></div>
@if ($company['logo_light'])<img class="abs" src="{{ $company['logo_light'] }}" style="left: 3.5mm; top: 2.4mm; height: 7.2mm;">@endif
<div class="abs upper" style="right: 3.5mm; top: 3.2mm; text-align: right; color: #fff; font-size: 6.6pt; font-weight: bold;">Carte de service</div>
<div class="abs" style="right: 3.5mm; top: 6.9mm; text-align: right; color: #FFE500; font-size: 4.8pt; letter-spacing: .2mm;">N° {{ $employee->matricule }}</div>
@include('pdf.cards.stripe', ['pos' => 'top: 12mm'])
<div class="abs" style="left: 3.5mm; top: 16mm;">@include('pdf.cards.photo', ['pw' => 21, 'ph' => 26])</div>
<div class="abs" style="left: 28mm; top: 15.5mm; right: 3mm;">
    <div style="font-size: 9pt; font-weight: bold; line-height: 1.1;" class="upper">{{ $employee->last_name }}</div>
    <div style="font-size: 7.6pt; line-height: 1.2;">{{ $employee->first_name }}</div>
    <div style="font-size: 6.4pt; color: #E6007E; font-weight: bold; margin-top: .8mm;">{{ $employee->job_title ?? 'Agent' }}</div>
    <table style="margin-top: 1.8mm; width: 100%;">
        <tr><td style="padding: .3mm 0;"><div class="label">Service</div><div class="val">{{ $employee->department?->name ?? '—' }}</div></td></tr>
        <tr><td style="padding: .3mm 0;"><div class="label">Siège</div><div class="val">{{ $employee->site?->name ?? '—' }}</div></td></tr>
        <tr><td style="padding: .3mm 0;"><div class="label">Valable jusqu’au</div><div class="val" style="color: #E6007E;">{{ ($employee->card_expires_at ?? now()->addYears($validity))->format('d/m/Y') }}</div></td></tr>
    </table>
</div>
<div class="abs" style="left: 0; right: 0; bottom: 0; height: 2.2mm; background: #E6007E;"></div>
