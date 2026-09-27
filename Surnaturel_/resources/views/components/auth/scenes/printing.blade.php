{{-- Salle d’impression : presse offset CMJN, opératrice en pagne wax, collègue contrôlant les feuilles. --}}
<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMin meet" class="h-full w-full" role="img" aria-label="Opératrice à la presse offset dans la salle d’impression">
    <defs>
        <x-auth.scenes.patterns p="sc1" />
        <linearGradient id="sc1-wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#312e81" /><stop offset="1" stop-color="#1e1b4b" /></linearGradient>
        <linearGradient id="sc1-beam" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fde68a" stop-opacity=".35" /><stop offset="1" stop-color="#fde68a" stop-opacity="0" /></linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#sc1-wall)" />
    <rect x="560" y="50" width="170" height="160" rx="10" fill="#fcd34d" opacity=".25" />
    <path d="M645 50v160M560 130h170" stroke="#1e1b4b" stroke-width="6" />
    <path d="M560 210 L730 210 L640 470 L380 470Z" fill="url(#sc1-beam)" />
    <rect y="455" width="800" height="145" fill="#17153b" />
    <rect x="40" y="215" width="120" height="12" rx="3" fill="#4338ca" /><rect x="40" y="315" width="120" height="12" rx="3" fill="#4338ca" />
    <rect x="52" y="180" width="44" height="35" rx="3" fill="#e0e7ff" /><rect x="100" y="188" width="50" height="27" rx="3" fill="#c7d2fe" />
    <rect x="52" y="280" width="96" height="35" rx="3" fill="url(#sc1-kente)" />
    <g>
        <rect x="235" y="215" width="360" height="235" rx="24" fill="#c7d2fe" />
        <rect x="235" y="215" width="360" height="42" rx="20" fill="#a5b4fc" />
        <rect x="255" y="232" width="120" height="10" rx="5" fill="#4f46e5" />
        <rect x="500" y="228" width="72" height="18" rx="5" fill="#1e1b4b" /><circle class="erp-pulse" cx="512" cy="237" r="4" fill="#4ade80" />
        @foreach ([[305, '#22d3ee', '#0e7490'], [380, '#ec4899', '#9d174d'], [455, '#facc15', '#a16207'], [530, '#27272a', '#71717a']] as [$cx, $fill, $hub])
            <g class="erp-spin"><circle cx="{{ $cx }}" cy="320" r="30" fill="{{ $fill }}" /><circle cx="{{ $cx }}" cy="320" r="10" fill="{{ $hub }}" /><path d="M{{ $cx }} 292v56M{{ $cx - 28 }} 320h56" stroke="{{ $hub }}" stroke-width="4" /></g>
        @endforeach
        <rect x="265" y="372" width="300" height="10" rx="5" fill="#818cf8" />
        <rect x="255" y="395" width="320" height="40" rx="10" fill="#a5b4fc" />
        <rect x="200" y="360" width="50" height="16" rx="3" fill="#f8fafc" /><rect x="200" y="348" width="50" height="14" rx="3" fill="#e2e8f0" />
        <rect x="585" y="410" width="80" height="44" rx="4" fill="#fff" /><rect x="585" y="398" width="80" height="12" rx="3" fill="#e0e7ff" class="erp-grow" />
    </g>
    <g class="erp-sheet-long">
        <rect x="200" y="300" width="64" height="44" rx="3" fill="#fff" />
        <rect x="208" y="308" width="32" height="6" rx="2" fill="#22d3ee" /><rect x="208" y="318" width="46" height="4" rx="2" fill="#ec4899" /><rect x="208" y="326" width="38" height="4" rx="2" fill="#facc15" />
    </g>
    <x-auth.person x="150" y="470" s=".92" gender="f" wrap="sc1-wax1" top="#ea580c" skin="#6b3f22" pose="work" />
    <x-auth.person x="700" y="478" s=".86" flip gender="m" top="#0e7490" bottom="#1e293b" skin="#4a2c1a" pose="hold">
        <rect x="-26" y="-162" width="52" height="36" rx="3" fill="#fff" transform="rotate(-6)" /><rect x="-18" y="-156" width="30" height="5" rx="2" fill="#ec4899" transform="rotate(-6)" />
    </x-auth.person>
    <rect y="584" width="800" height="16" fill="url(#sc1-kente)" />
</svg>
