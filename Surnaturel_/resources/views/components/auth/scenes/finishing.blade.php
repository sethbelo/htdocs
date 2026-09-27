{{-- Finition : massicot, reliure et plastification, piles de documents terminés. --}}
<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMin meet" class="h-full w-full" role="img" aria-label="Opérateur au massicot et piles de documents reliés">
    <defs>
        <x-auth.scenes.patterns p="sc5" />
        <linearGradient id="sc5-wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#115e59" /><stop offset="1" stop-color="#134e4a" /></linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#sc5-wall)" />
    <path d="M0 0 H800 V40 H0Z" fill="#0f3d3a" />
    @foreach ([80, 240, 400, 560, 720] as $lx)
        <path d="M{{ $lx }} 40 v30" stroke="#0f3d3a" stroke-width="3" /><path d="M{{ $lx - 26 }} 70 h52 l-10 22 h-32z" fill="#fde68a" /><path d="M{{ $lx - 40 }} 92 L{{ $lx + 40 }} 92 L{{ $lx + 90 }} 300 L{{ $lx - 90 }} 300Z" fill="#fef3c7" opacity=".06" />
    @endforeach
    <rect y="445" width="800" height="155" fill="#0b2e2b" />
    <g>
        <rect x="300" y="210" width="230" height="245" rx="16" fill="#e2e8f0" />
        <rect x="318" y="240" width="194" height="16" rx="4" fill="#94a3b8" />
        <g class="erp-blade"><rect x="330" y="258" width="170" height="14" rx="3" fill="#64748b" /><path d="M330 272 h170 l-8 10 h-154z" fill="#cbd5e1" /></g>
        <rect x="340" y="300" width="150" height="60" rx="3" fill="#fff" /><rect x="340" y="300" width="150" height="8" fill="#22d3ee" /><rect x="340" y="312" width="150" height="6" fill="#ec4899" opacity=".6" />
        <rect x="318" y="372" width="194" height="60" rx="10" fill="#cbd5e1" /><circle class="erp-pulse" cx="490" cy="400" r="7" fill="#ef4444" /><circle cx="466" cy="400" r="7" fill="#22c55e" />
    </g>
    @foreach ([0, 1, 2, 3, 4] as $i)
        <rect x="{{ 580 + ($i % 2) * 4 }}" y="{{ 420 - $i * 20 }}" width="120" height="18" rx="3" fill="{{ ['#4f46e5', '#db2777', '#0e7490', '#f59e0b', '#16a34a'][$i] }}" />
        <rect x="{{ 580 + ($i % 2) * 4 }}" y="{{ 420 - $i * 20 }}" width="10" height="18" fill="#111827" opacity=".4" />
    @endforeach
    <g class="erp-float"><rect x="620" y="270" width="80" height="50" rx="8" fill="url(#sc5-wax2)" stroke="#fff" stroke-width="4" /><rect x="640" y="290" width="40" height="6" rx="3" fill="#fff" /></g>
    <rect x="110" y="380" width="120" height="70" rx="8" fill="#1e293b" /><rect x="118" y="360" width="104" height="24" rx="4" fill="#475569" />
    <g class="erp-sheet-short"><rect x="125" y="330" width="90" height="30" rx="3" fill="#fff" stroke="#a5f3fc" stroke-width="3" /></g>
    <x-auth.person x="260" y="470" s=".9" gender="m" top="#e11d48" bottom="#0f172a" skin="#4a2c1a" pose="work" />
    <rect y="584" width="800" height="16" fill="url(#sc5-kente)" />
</svg>
