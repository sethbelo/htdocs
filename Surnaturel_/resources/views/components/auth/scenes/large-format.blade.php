{{-- Grand format : pose d’une bâche sur une façade de Kinshasa au coucher du soleil. --}}
<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMin meet" class="h-full w-full" role="img" aria-label="Pose d’une bâche publicitaire sur une façade en ville">
    <defs>
        <x-auth.scenes.patterns p="sc2" />
        <linearGradient id="sc2-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7c3aed" /><stop offset=".45" stop-color="#f97316" /><stop offset="1" stop-color="#fcd34d" /></linearGradient>
        <linearGradient id="sc2-banner" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#4f46e5" /><stop offset="1" stop-color="#db2777" /></linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#sc2-sky)" />
    <circle cx="640" cy="170" r="70" fill="#fef3c7" opacity=".85" />
    <path d="M0 330 L60 330 L60 270 L110 270 L110 300 L170 300 L170 240 L230 240 L230 330 Z" fill="#431407" opacity=".55" />
    <path d="M560 330 L600 330 L600 280 L650 280 L650 250 L700 250 L700 300 L800 300 L800 330Z" fill="#431407" opacity=".55" />
    <g transform="translate(700 300)"><rect x="-6" y="0" width="12" height="150" fill="#3f1d0b" />
        <path d="M0 0 Q-60 -20 -90 20 M0 0 Q-40 -50 -70 -60 M0 0 Q40 -50 80 -40 M0 0 Q60 -10 90 30 M0 0 Q0 -60 10 -80" fill="none" stroke="#166534" stroke-width="14" stroke-linecap="round" /></g>
    <rect x="170" y="120" width="420" height="345" fill="#fde68a" />
    <rect x="170" y="120" width="420" height="22" fill="#b45309" />
    @foreach ([200, 290, 380, 470] as $wx)
        <rect x="{{ $wx }}" y="370" width="60" height="70" rx="4" fill="#78350f" opacity=".75" />
    @endforeach
    <g class="erp-sway">
        <rect x="200" y="165" width="360" height="170" rx="6" fill="url(#sc2-banner)" />
        <circle cx="210" cy="175" r="5" fill="#e5e7eb" /><circle cx="550" cy="175" r="5" fill="#e5e7eb" /><circle cx="210" cy="325" r="5" fill="#e5e7eb" /><circle cx="550" cy="325" r="5" fill="#e5e7eb" />
        <text x="380" y="235" text-anchor="middle" font-family="ui-sans-serif, system-ui" font-size="44" font-weight="900" fill="#fff">GRANDE PROMO</text>
        <text x="380" y="272" text-anchor="middle" font-family="ui-sans-serif, system-ui" font-size="20" font-weight="700" fill="#fde047">Rentrée scolaire · -20 %</text>
        <rect x="290" y="292" width="180" height="24" rx="12" fill="url(#sc2-kente)" />
    </g>
    <g stroke="#a1a1aa" stroke-width="8" stroke-linecap="round"><path d="M600 470 L585 150 M650 470 L635 150" /><path d="M592 420h52M590 370h52M589 320h52M587 270h52M586 220h52M585 175h52" stroke-width="6" /></g>
    <rect y="455" width="800" height="145" fill="#292524" />
    <path d="M0 520 H800" stroke="#fbbf24" stroke-width="6" stroke-dasharray="40 30" />
    <x-auth.person x="615" y="290" s=".62" flip gender="m" top="#16a34a" bottom="#1f2937" skin="#5a3620" pose="point" />
    <x-auth.person x="130" y="470" s=".8" gender="m" top="#1d4ed8" bottom="#111827" skin="#4a2c1a" pose="point" />
    <x-auth.person x="80" y="478" s=".72" gender="f" wrap="sc2-wax3" top="#7e22ce" skin="#7a4a2a" pose="idle" />
    <rect y="584" width="800" height="16" fill="url(#sc2-kente)" />
</svg>
