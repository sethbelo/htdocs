{{-- Badge — verso --}}
<div class="abs" style="left: 20mm; top: 2mm; width: 14mm; height: 2.6mm; background: #ececf3; border-radius: 1.3mm;"></div>
@if ($company['logo'])<img class="abs" src="{{ $company['logo'] }}" style="left: 12mm; top: 7mm; width: 30mm;">@endif
<div class="abs" style="left: 12mm; top: 21mm; text-align: center; width: 30mm;">
    <img src="{{ \App\Services\Support\Codes::qrDataUri($verifyUrls[$employee->id], 120) }}" style="width: 26mm; height: 26mm;">
    <div style="font-size: 4.6pt; color: #6b6b80;">Scanner pour vérifier la validité</div>
</div>
<div class="abs" style="left: 4mm; right: 4mm; top: 52mm; text-align: center;">
    <img src="{{ \App\Services\Support\Codes::barcodeDataUri($employee->badge_code ?: $employee->matricule, 1, 24) }}" style="height: 6mm; max-width: 44mm;">
    <div style="font-size: 5pt; margin-top: .4mm;">{{ $employee->badge_code ?: $employee->matricule }}</div>
    <div style="font-size: 4.8pt; color: #3a3a4d; margin-top: 1.6mm; line-height: 1.35;">Badge personnel à porter de manière visible. En cas de perte, prévenir les RH.<br>Groupe sanguin : <b>{{ $employee->blood_group ?? '—' }}</b></div>
</div>
<div class="abs" style="left: 0; right: 0; top: 79.5mm; text-align: center; font-size: 4.8pt; color: #6b6b80;">{{ $company['phone'] }} · {{ $company['email'] }}</div>
@include('pdf.cards.stripe', ['pos' => 'bottom: 0'])
