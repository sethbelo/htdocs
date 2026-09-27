{{-- Carte de service — verso --}}
@if ($company['mark'])<img class="abs" src="{{ $company['mark'] }}" style="left: 3.5mm; top: 3mm; width: 7mm;">@endif
<div class="abs" style="left: 12mm; top: 3.6mm; font-size: 6.6pt; font-weight: bold;">{{ $company['legal_name'] ?: $company['name'] }}</div>
<div class="abs" style="left: 12mm; top: 6.9mm; font-size: 4.8pt; color: #6b6b80;">{{ collect([$company['address'], $company['city']])->filter()->implode(', ') }} · {{ $company['phone'] }}</div>
<div class="abs" style="left: 3.5mm; top: 12.5mm; width: 55mm; font-size: 5pt; line-height: 1.35; color: #3a3a4d;">
    Cette carte est strictement personnelle et demeure la propriété de {{ $company['name'] }}. Le titulaire est autorisé à accéder aux locaux et à représenter l’entreprise dans le cadre de ses fonctions. En cas de perte, merci de la retourner à l’adresse ci-dessus.
</div>
<table class="abs" style="left: 3.5mm; top: 29mm; width: 55mm;">
    <tr>
        <td style="width: 34%;"><div class="label">Groupe sanguin</div><div class="val" style="color: #E6007E;">{{ $employee->blood_group ?? '—' }}</div></td>
        <td><div class="label">Contact d’urgence</div><div class="val" style="font-size: 5.6pt;">{{ $employee->emergency_contact ?? '—' }}</div></td>
    </tr>
</table>
<div class="abs" style="left: 3.5mm; top: 38mm; width: 40mm; border-top: .2mm solid #16161D; padding-top: .6mm; font-size: 4.8pt;"><b>Le Directeur Général</b>{{ $dgName ? ' — '.$dgName : '' }}</div>
<div class="abs" style="right: 3.5mm; top: 12.5mm; text-align: center; width: 21mm;">
    <img src="{{ \App\Services\Support\Codes::qrDataUri($verifyUrls[$employee->id], 120) }}" style="width: 19mm; height: 19mm;">
    <div style="font-size: 4.2pt; color: #6b6b80; margin-top: .4mm;">Scanner pour vérifier</div>
    <div style="font-size: 5pt; font-weight: bold;">{{ $employee->matricule }}</div>
</div>
@include('pdf.cards.stripe', ['pos' => 'bottom: 0'])
