{{-- Badge (portrait 54 × 85,6 mm) --}}
<div class="abs" style="left: 0; top: 0; width: 54mm; height: 19mm; background: #16161D;"></div>
<div class="abs" style="left: 20mm; top: 2mm; width: 14mm; height: 2.6mm; background: #fff; border-radius: 1.3mm;"></div>
@if ($company['logo_light'])<img class="abs" src="{{ $company['logo_light'] }}" style="left: 12mm; top: 6.5mm; width: 30mm;">@endif
@include('pdf.cards.stripe', ['pos' => 'top: 19mm'])
<div class="abs" style="left: 14.5mm; top: 23mm;">@include('pdf.cards.photo', ['pw' => 25, 'ph' => 29])</div>
<div class="abs" style="left: 2mm; right: 2mm; top: 54mm; text-align: center;">
    <div style="font-size: 9pt; font-weight: bold; line-height: 1.15;" class="upper">{{ $employee->last_name }}</div>
    <div style="font-size: 8pt;">{{ $employee->first_name }}</div>
    <div style="font-size: 6.4pt; color: #E6007E; font-weight: bold; margin-top: .6mm;">{{ $employee->job_title ?? 'Agent' }}</div>
    <div style="font-size: 5.6pt; color: #6b6b80;">{{ $employee->department?->name }}</div>
</div>
<div class="abs" style="left: 0; right: 0; bottom: 0; height: 11mm; background: #E6007E; text-align: center; color: #fff;">
    <div style="font-size: 8pt; font-weight: bold; letter-spacing: .8mm; margin-top: 1.6mm;">PERSONNEL</div>
    <div style="font-size: 5.6pt; letter-spacing: .3mm;">{{ $employee->matricule }}</div>
</div>
