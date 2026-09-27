{{-- Carte de membre santé / assurance (CR80) --}}
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #16161D; }
    .card { position: relative; width: 85.6mm; height: 54mm; overflow: hidden; }
    .abs { position: absolute; }
</style></head>
<body>
    <div class="card">
        <div class="abs" style="left: 0; top: 0; width: 85.6mm; height: 13mm; background: {{ $affiliation->policy->type->value === 'medical' ? '#00A6E6' : '#16161D' }};"></div>
        @if ($company['logo_light'])<img class="abs" src="{{ $company['logo_light'] }}" style="left: 3.5mm; top: 2.8mm; height: 7.4mm;">@endif
        <div class="abs" style="right: 3.5mm; top: 3mm; text-align: right; color: #fff;"><div style="font-size: 6.8pt; font-weight: bold; text-transform: uppercase;">{{ $affiliation->policy->type->label() }}</div><div style="font-size: 5pt;">{{ $affiliation->policy->provider }}</div></div>
        <div class="abs" style="left: 3.5mm; top: 16mm; right: 26mm;">
            <div style="font-size: 4.8pt; color: #6b6b80; text-transform: uppercase;">Membre</div>
            <div style="font-size: 9pt; font-weight: bold;">{{ $affiliation->memberName() }}</div>
            <div style="font-size: 5.6pt; color: #E6007E; font-weight: bold;">{{ $affiliation->memberRelationship() }}</div>
            <div style="font-size: 4.8pt; color: #6b6b80; text-transform: uppercase; margin-top: 2mm;">Agent titulaire</div>
            <div style="font-size: 6.4pt; font-weight: bold;">{{ $affiliation->employee->fullName() }} · {{ $affiliation->employee->matricule }}</div>
            <div style="font-size: 4.8pt; color: #6b6b80; text-transform: uppercase; margin-top: 2mm;">Contrat</div>
            <div style="font-size: 6pt;">{{ $affiliation->policy->name }}{{ $affiliation->policy->policy_number ? ' · n° '.$affiliation->policy->policy_number : '' }}</div>
        </div>
        <div class="abs" style="right: 3.5mm; top: 16mm; text-align: center; width: 20mm;">
            <img src="{{ \App\Services\Support\Codes::qrDataUri($affiliation->member_number, 100) }}" style="width: 18mm; height: 18mm;">
            <div style="font-size: 5.4pt; font-weight: bold; margin-top: .6mm;">{{ $affiliation->member_number }}</div>
            <div style="font-size: 4.6pt; color: #6b6b80;">depuis {{ $affiliation->starts_on->format('m/Y') }}</div>
        </div>
        <div class="abs" style="left: 3.5mm; top: 47.5mm; font-size: 4.6pt; color: #6b6b80;">Assistance : {{ $affiliation->policy->contact_phone ?: $company['phone'] }}</div>
        <div class="abs" style="left: 0; right: 0; bottom: 0; height: 1.2mm;"><span style="display: inline-block; width: 40%; height: 1.2mm; background: #00A6E6;"></span><span style="display: inline-block; width: 35%; height: 1.2mm; background: #E6007E;"></span><span style="display: inline-block; width: 25%; height: 1.2mm; background: #FFE500;"></span></div>
    </div>
</body></html>
