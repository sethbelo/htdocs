{{-- Carte de visite — recto (85 × 55 mm) --}}
@if ($company['logo'])<img class="abs" src="{{ $company['logo'] }}" style="left: 5mm; top: 5mm; height: 10mm;">@endif
<div class="abs" style="right: 0; top: 0; width: 2mm; height: 55mm;">
    <div style="height: 22mm; background: #16161D;"></div><div style="height: 12mm; background: #00A6E6;"></div><div style="height: 12mm; background: #E6007E;"></div><div style="height: 9mm; background: #FFE500;"></div>
</div>
<div class="abs" style="left: 5mm; top: 21mm; right: 6mm;">
    <div style="font-size: 10.5pt; font-weight: bold;">{{ $employee->fullName() }}</div>
    <div style="font-size: 6.8pt; color: #E6007E; font-weight: bold; margin-top: .4mm;" class="upper">{{ $employee->job_title ?? 'Conseiller' }}</div>
    @if ($employee->department)<div style="font-size: 5.8pt; color: #6b6b80;">{{ $employee->department->name }}</div>@endif
</div>
<table class="abs" style="left: 5mm; top: 36.5mm; font-size: 5.8pt; line-height: 1.45;">
    @if ($employee->phone)<tr><td style="color: #E6007E; font-weight: bold; width: 4mm;">T</td><td>{{ $employee->phone }}</td></tr>@endif
    <tr><td style="color: #E6007E; font-weight: bold;">E</td><td>{{ $employee->email ?: $company['email'] }}</td></tr>
    @if ($company['website'])<tr><td style="color: #E6007E; font-weight: bold;">W</td><td>{{ $company['website'] }}</td></tr>@endif
    <tr><td style="color: #E6007E; font-weight: bold;">A</td><td>{{ collect([$company['address'], $company['city']])->filter()->implode(', ') }}</td></tr>
</table>
