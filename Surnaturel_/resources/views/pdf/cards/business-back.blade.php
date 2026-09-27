{{-- Carte de visite — verso --}}
<div class="abs" style="left: 0; top: 0; width: 85mm; height: 55mm; background: #16161D;"></div>
@if ($company['logo_light'])<img class="abs" src="{{ $company['logo_light'] }}" style="left: 17.5mm; top: 14mm; width: 50mm;">@endif
@if ($company['slogan'])<div class="abs" style="left: 0; right: 0; top: 36mm; text-align: center; color: #fff; font-size: 6.4pt; letter-spacing: .3mm;">{{ $company['slogan'] }}</div>@endif
<div class="abs" style="left: 0; right: 0; top: 41mm; text-align: center; color: #b5b5c8; font-size: 5.4pt;">{{ $company['phone'] }}{{ $company['website'] ? ' · '.$company['website'] : '' }}</div>
@include('pdf.cards.stripe', ['pos' => 'bottom: 0'])
