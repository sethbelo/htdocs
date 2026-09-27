{{-- Studio graphique : maquette à l’écran, cliente qui valide le BAT, nuancier CMJN. --}}
<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMin meet" class="h-full w-full" role="img" aria-label="Graphiste et cliente validant un bon à tirer">
    <defs>
        <x-auth.scenes.patterns p="sc3" />
        <linearGradient id="sc3-wall" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0e7490" /><stop offset="1" stop-color="#0f172a" /></linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#sc3-wall)" />
    <rect x="40" y="60" width="150" height="190" rx="8" fill="url(#sc3-bogolan)" opacity=".9" />
    <rect x="610" y="70" width="130" height="95" rx="8" fill="#f8fafc" /><rect x="624" y="84" width="102" height="50" rx="4" fill="url(#sc3-wax2)" /><rect x="624" y="142" width="60" height="8" rx="4" fill="#94a3b8" />
    <rect y="430" width="800" height="170" fill="#0b1220" />
    <rect x="120" y="400" width="560" height="22" rx="6" fill="#78350f" /><rect x="150" y="420" width="16" height="60" fill="#451a03" /><rect x="634" y="420" width="16" height="60" fill="#451a03" />
    <rect x="300" y="190" width="300" height="190" rx="14" fill="#1e293b" /><rect x="312" y="202" width="276" height="160" rx="6" fill="#f8fafc" />
    <rect x="435" y="380" width="30" height="22" fill="#334155" /><rect x="400" y="398" width="100" height="8" rx="4" fill="#334155" />
    <rect x="326" y="216" width="120" height="132" rx="4" fill="url(#sc3-wax2)" />
    <path class="erp-draw" d="M460 330 C 480 240, 530 320, 570 230" fill="none" stroke="#4f46e5" stroke-width="5" stroke-linecap="round" />
    <rect x="460" y="220" width="110" height="10" rx="5" fill="#0f172a" /><rect x="460" y="238" width="80" height="7" rx="3" fill="#94a3b8" />
    @foreach ([['#22d3ee', 0], ['#ec4899', .2], ['#facc15', .4], ['#18181b', .6]] as $i => [$fill, $delay])
        <circle class="erp-pop" style="animation-delay: {{ $delay }}s" cx="{{ 200 + $i * 32 }}" cy="385" r="12" fill="{{ $fill }}" />
    @endforeach
    <g transform="translate(560 150) rotate(-12)"><g class="erp-stamp">
        <rect x="-62" y="-26" width="124" height="52" rx="10" fill="#fff" fill-opacity=".9" stroke="#16a34a" stroke-width="5" />
        <text x="0" y="10" text-anchor="middle" font-family="ui-sans-serif, system-ui" font-size="24" font-weight="900" fill="#16a34a">BAT OK</text>
    </g></g>
    <x-auth.person x="250" y="470" s=".88" gender="m" top="#f59e0b" bottom="#1e293b" skin="#4a2c1a" pose="type" />
    <x-auth.person x="690" y="478" s=".9" flip gender="f" wrap="sc3-wax1" top="#0f766e" skin="#8d5a36" pose="point" />
    <rect y="584" width="800" height="16" fill="url(#sc3-kente)" />
</svg>
