{{--
    Personnage stylisé (illustration « flat ») : pieds à l’origine, environ 290 unités de haut.
    genre : f (pagne + gele) ou m ; poses : idle, work, hold, point, type.
--}}
@props([
    'x' => 0, 'y' => 0, 's' => 1, 'flip' => false,
    'skin' => '#7a4a2a', 'top' => '#4f46e5', 'bottom' => '#1e293b',
    'wrap' => null, 'gender' => 'm', 'pose' => 'idle', 'hair' => '#161616',
])
@php
    $cloth = $wrap ? "url(#{$wrap})" : $top;
    $sx = $flip ? -$s : $s;
    $arms = match ($pose) {
        'work' => [['M-32 -186 Q6 -160 58 -152', 60, -152], ['M34 -188 Q66 -176 86 -158', 88, -156]],
        'hold' => [['M-36 -186 Q-44 -148 -14 -138', -12, -138], ['M36 -186 Q44 -148 14 -138', 12, -138]],
        'point' => [['M-36 -188 Q-52 -150 -44 -110', -44, -108], ['M34 -190 Q64 -214 84 -240', 86, -242]],
        'type' => [['M-34 -186 Q-26 -150 16 -140', 18, -140], ['M34 -186 Q44 -150 60 -142', 62, -142]],
        default => [['M-38 -188 Q-54 -150 -46 -108', -46, -106], ['M38 -188 Q54 -150 46 -108', 46, -106]],
    };
@endphp
<g transform="translate({{ $x }} {{ $y }}) scale({{ $sx }} {{ $s }})">
    <ellipse cx="0" cy="4" rx="54" ry="9" fill="#000" opacity=".22" />
    @if ($gender === 'f')
        <path d="M-36 -112 L36 -112 L46 -2 L-46 -2Z" fill="{{ $cloth }}" />
        <path d="M-36 -112 L36 -112 L38 -96 L-38 -96Z" fill="#000" opacity=".12" />
        <ellipse cx="-17" cy="0" rx="13" ry="5" fill="#27272a" /><ellipse cx="17" cy="0" rx="13" ry="5" fill="#27272a" />
    @else
        <rect x="-31" y="-114" width="27" height="112" rx="9" fill="{{ $bottom }}" /><rect x="4" y="-114" width="27" height="112" rx="9" fill="{{ $bottom }}" />
        <ellipse cx="-19" cy="0" rx="15" ry="6" fill="#18181b" /><ellipse cx="19" cy="0" rx="15" ry="6" fill="#18181b" />
    @endif
    <path d="M-40 -104 Q-45 -188 -30 -198 L30 -198 Q45 -188 40 -104Z" fill="{{ $gender === 'f' ? $cloth : $top }}" />
    <path d="M-12 -198 L0 -184 L12 -198Z" fill="{{ $skin }}" />
    @foreach ($arms as [$path, $hx, $hy])
        <path d="{{ $path }}" fill="none" stroke="{{ $gender === 'f' ? $top : $top }}" stroke-width="17" stroke-linecap="round" />
        <circle cx="{{ $hx }}" cy="{{ $hy }}" r="8.5" fill="{{ $skin }}" />
    @endforeach
    <rect x="-8" y="-216" width="16" height="20" rx="4" fill="{{ $skin }}" />
    <ellipse cx="0" cy="-236" rx="21" ry="25" fill="{{ $skin }}" />
    <ellipse cx="-20" cy="-234" rx="4" ry="6" fill="{{ $skin }}" /><ellipse cx="20" cy="-234" rx="4" ry="6" fill="{{ $skin }}" />
    <path d="M-7 -226 Q0 -221 7 -226" fill="none" stroke="#000" stroke-opacity=".35" stroke-width="2" stroke-linecap="round" />
    @if ($gender === 'f')
        <path d="M-26 -240 Q-36 -276 -6 -288 Q24 -298 36 -270 Q42 -252 26 -242 Q30 -266 6 -270 Q-18 -270 -22 -242Z" fill="{{ $wrap ? "url(#{$wrap})" : $top }}" />
        <path d="M24 -276 Q46 -292 40 -262 Q34 -270 24 -276Z" fill="{{ $wrap ? "url(#{$wrap})" : $top }}" stroke="#000" stroke-opacity=".15" />
        <circle cx="-22" cy="-222" r="3" fill="#facc15" /><circle cx="22" cy="-222" r="3" fill="#facc15" />
    @else
        <path d="M-21 -242 Q-22 -264 0 -264 Q22 -264 21 -242 Q14 -256 0 -256 Q-14 -256 -21 -242Z" fill="{{ $hair }}" />
    @endif
    {{ $slot }}
</g>
