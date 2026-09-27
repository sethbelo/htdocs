{{-- Boutique : rayons de fournitures, caisse avec ticket, cliente et enfant pour la rentrée. --}}
<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMin meet" class="h-full w-full" role="img" aria-label="Boutique de fournitures avec caissière et clientes">
    <defs>
        <x-auth.scenes.patterns p="sc4" />
        <linearGradient id="sc4-wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9a3412" /><stop offset="1" stop-color="#422006" /></linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#sc4-wall)" />
    <rect x="30" y="40" width="740" height="36" rx="8" fill="url(#sc4-kente)" />
    <g>
        @foreach ([120, 210, 300] as $row => $sy)
            <rect x="40" y="{{ $sy }}" width="330" height="10" rx="3" fill="#fde68a" />
            @for ($i = 0; $i < 7; $i++)
                @php($colors = ['#22d3ee', '#ec4899', '#facc15', '#4f46e5', '#16a34a', '#f97316', '#e11d48'])
                <rect x="{{ 50 + $i * 45 }}" y="{{ $sy - 48 + ($i % 2) * 8 }}" width="36" height="{{ 48 - ($i % 2) * 8 }}" rx="3" fill="{{ $colors[($i + $row * 2) % 7] }}" />
                <rect x="{{ 56 + $i * 45 }}" y="{{ $sy - 38 + ($i % 2) * 8 }}" width="24" height="5" rx="2" fill="#fff" opacity=".7" />
            @endfor
        @endforeach
    </g>
    <rect y="440" width="800" height="160" fill="#2a1505" />
    <x-auth.person x="540" y="440" s=".75" gender="f" wrap="sc4-wax2" top="#0e7490" skin="#5a3620" pose="type" />
    <rect x="420" y="330" width="330" height="120" rx="12" fill="#f5f5f4" /><rect x="420" y="330" width="330" height="20" rx="10" fill="url(#sc4-bogolan)" />
    <rect x="600" y="270" width="110" height="70" rx="10" fill="#1e1b4b" /><rect x="610" y="280" width="90" height="48" rx="5" fill="#e0e7ff" /><rect x="618" y="290" width="46" height="6" rx="3" fill="#4f46e5" /><rect x="618" y="304" width="70" height="5" rx="2" fill="#a5b4fc" />
    <g class="erp-receipt"><path d="M716 280h44v76l-6-4-5 4-6-4-5 4-6-4-5 4-6-4-5 4z" fill="#fff" /><rect x="722" y="292" width="28" height="4" rx="2" fill="#6366f1" /><rect x="722" y="302" width="32" height="3" rx="1.5" fill="#cbd5e1" /><rect x="722" y="324" width="32" height="10" fill="#18181b" /></g>
    <x-auth.person x="300" y="478" s=".9" flip gender="f" wrap="sc4-wax3" top="#7e22ce" skin="#7a4a2a" pose="hold">
        <rect x="-22" y="-160" width="44" height="30" rx="3" fill="#facc15" /><rect x="-16" y="-154" width="30" height="4" fill="#fff" />
    </x-auth.person>
    <x-auth.person x="210" y="478" s=".55" flip gender="m" top="#dc2626" bottom="#1e3a8a" skin="#6b3f22" pose="idle">
        <rect x="24" y="-200" width="34" height="56" rx="8" fill="#1d4ed8" />
    </x-auth.person>
    <rect y="584" width="800" height="16" fill="url(#sc4-kente)" />
</svg>
