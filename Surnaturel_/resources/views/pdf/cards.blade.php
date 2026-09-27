{{--
    Cartes d'agents : badge (portrait), carte de service et carte de visite (paysage), recto / verso.
    $layout « single » : une face par page au format carte ; « sheet » : planche A4 imposée (verso en miroir pour l'impression recto-verso).
--}}
@php
    [$w, $h] = match ($kind) { 'badge' => [54, 85.6], 'business' => [85, 55], default => [85.6, 54] };
    [$cols, $rows] = $kind === 'badge' ? [3, 3] : [2, 5];
    $perSheet = $cols * $rows;
    $offsetX = (210 - $cols * $w) / 2;
    $offsetY = (297 - $rows * $h) / 2;
    $sides = $withBack ? ['front', 'back'] : ['front'];
    $pages = [];
    if ($layout === 'single') {
        foreach ($employees as $employee) {
            foreach ($sides as $side) { $pages[] = [[$employee, $side, 0, 0]]; }
        }
    } else {
        foreach ($employees->chunk($perSheet) as $chunk) {
            foreach ($sides as $side) {
                $page = [];
                foreach ($chunk->values() as $i => $employee) {
                    $col = $i % $cols; $row = intdiv($i, $cols);
                    if ($side === 'back') { $col = $cols - 1 - $col; }
                    $page[] = [$employee, $side, $offsetX + $col * $w, $offsetY + $row * $h];
                }
                $pages[] = $page;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #16161D; }
    .page { position: relative; width: {{ $layout === 'single' ? $w : 210 }}mm; height: {{ $layout === 'single' ? $h : 297 }}mm; page-break-after: always; overflow: hidden; }
    .page.last { page-break-after: auto; }
    .card { position: absolute; width: {{ $w }}mm; height: {{ $h }}mm; overflow: hidden; background: #fff; }
    .guide { outline: .15mm dashed #c9c9d6; }
    .abs { position: absolute; }
    .upper { text-transform: uppercase; letter-spacing: .3mm; }
    .stripe { position: absolute; left: 0; right: 0; height: 1mm; }
    .stripe span { display: inline-block; height: 1mm; }
    .initials { background: #ececf3; color: #6b6b80; font-weight: bold; text-align: center; }
    .label { font-size: 4.6pt; color: #6b6b80; text-transform: uppercase; letter-spacing: .2mm; }
    .val { font-size: 6.4pt; font-weight: bold; }
    table { border-collapse: collapse; }
</style></head>
<body>
@foreach ($pages as $page)
    <div class="page {{ $loop->last ? 'last' : '' }}">
        @if ($layout === 'sheet')
            <div class="abs" style="left: 0; right: 0; top: 4mm; text-align: center; font-size: 6pt; color: #9a9aac;">{{ $company['name'] }} · planche {{ $kind === 'business' ? 'cartes de visite' : ($kind === 'badge' ? 'badges' : 'cartes de service') }} · {{ $page[0][1] === 'front' ? 'RECTO' : 'VERSO (miroir, retournement bord long)' }} · découper selon les pointillés</div>
        @endif
        @foreach ($page as [$employee, $side, $x, $y])
            <div class="card {{ $layout === 'sheet' ? 'guide' : '' }}" style="left: {{ $x }}mm; top: {{ $y }}mm;">
                @include('pdf.cards.'.$kind.'-'.$side, ['employee' => $employee])
            </div>
        @endforeach
    </div>
@endforeach
</body></html>
