{{-- Livraison : coursier à moto dans les rues de la ville, colis remis à la cliente. --}}
<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMin meet" class="h-full w-full" role="img" aria-label="Coursier à moto livrant un colis en ville">
    <defs>
        <x-auth.scenes.patterns p="sc6" />
        <linearGradient id="sc6-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1d4ed8" /><stop offset="1" stop-color="#60a5fa" /></linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#sc6-sky)" />
    <circle cx="120" cy="110" r="46" fill="#fde047" />
    <g fill="#1e3a8a" opacity=".55">
        <rect x="0" y="200" width="90" height="250" /><rect x="100" y="160" width="70" height="290" /><rect x="180" y="230" width="110" height="220" />
        <rect x="520" y="180" width="90" height="270" /><rect x="620" y="140" width="80" height="310" /><rect x="710" y="220" width="90" height="230" />
    </g>
    <g fill="#bfdbfe" opacity=".5">
        @foreach ([[110, 180], [140, 180], [110, 220], [140, 220], [635, 170], [665, 170], [635, 210], [665, 210], [535, 210], [565, 210]] as [$wx, $wy])
            <rect x="{{ $wx }}" y="{{ $wy }}" width="16" height="22" rx="2" />
        @endforeach
    </g>
    <rect x="300" y="250" width="200" height="200" fill="#fef3c7" /><rect x="300" y="240" width="200" height="22" fill="url(#sc6-kente)" />
    <rect x="330" y="330" width="60" height="120" rx="4" fill="#92400e" /><rect x="410" y="300" width="70" height="60" rx="4" fill="#bae6fd" />
    <text x="400" y="290" text-anchor="middle" font-family="ui-sans-serif, system-ui" font-size="16" font-weight="800" fill="#1e1b4b">BUREAU CLIENT</text>
    <g class="erp-bounce-pin"><path d="M620 60a34 34 0 0 1 34 34c0 28-34 64-34 64s-34-36-34-64a34 34 0 0 1 34-34z" fill="#ec4899" /><circle cx="620" cy="94" r="12" fill="#fff" /></g>
    <rect y="450" width="800" height="150" fill="#1f2937" />
    <line class="erp-road" x1="0" y1="515" x2="800" y2="515" stroke="#fde047" stroke-width="6" stroke-dasharray="36 26" />
    <g class="erp-ride">
        <g class="erp-spin"><circle cx="150" cy="470" r="30" fill="#111827" /><circle cx="150" cy="470" r="11" fill="#9ca3af" /></g>
        <g class="erp-spin"><circle cx="290" cy="470" r="30" fill="#111827" /><circle cx="290" cy="470" r="11" fill="#9ca3af" /></g>
        <path d="M150 470 L200 420 L260 420 L290 470" fill="none" stroke="#dc2626" stroke-width="14" stroke-linejoin="round" />
        <path d="M250 420 L270 380 L292 378" fill="none" stroke="#1f2937" stroke-width="8" stroke-linecap="round" />
        <rect x="120" y="360" width="76" height="62" rx="8" fill="#4f46e5" /><path d="M130 380h56" stroke="#fff" stroke-width="4" />
        <text x="158" y="410" text-anchor="middle" font-family="ui-sans-serif, system-ui" font-size="12" font-weight="800" fill="#fff">S·IMP</text>
        <x-auth.person x="228" y="440" s=".62" gender="m" top="#f59e0b" bottom="#1e293b" skin="#4a2c1a" pose="work">
            <path d="M-24 -250 Q0 -290 24 -250 L26 -238 L-26 -238Z" fill="#dc2626" />
        </x-auth.person>
    </g>
    <x-auth.person x="560" y="470" s=".86" flip gender="f" wrap="sc6-wax1" top="#ea580c" skin="#6b3f22" pose="hold">
        <rect x="-28" y="-166" width="56" height="42" rx="4" fill="#d97706" /><path d="M-28 -148 h56" stroke="#92400e" stroke-width="3" />
    </x-auth.person>
    <rect y="584" width="800" height="16" fill="url(#sc6-kente)" />
</svg>
