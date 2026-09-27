{{-- Motifs africains (wax, kente, bogolan) : identifiants préfixés par scène pour éviter les collisions entre SVG. --}}
@props(['p'])
<pattern id="{{ $p }}-wax1" width="26" height="26" patternUnits="userSpaceOnUse">
    <rect width="26" height="26" fill="#ea580c" />
    <circle cx="13" cy="13" r="7.5" fill="#fde047" /><circle cx="13" cy="13" r="3.6" fill="#0f766e" />
    <circle cx="0" cy="0" r="4.5" fill="#0f766e" /><circle cx="26" cy="0" r="4.5" fill="#0f766e" /><circle cx="0" cy="26" r="4.5" fill="#0f766e" /><circle cx="26" cy="26" r="4.5" fill="#0f766e" />
</pattern>
<pattern id="{{ $p }}-wax2" width="22" height="22" patternUnits="userSpaceOnUse">
    <rect width="22" height="22" fill="#0e7490" />
    <path d="M0 11 L11 0 L22 11 L11 22Z" fill="#facc15" /><circle cx="11" cy="11" r="3.2" fill="#be123c" />
</pattern>
<pattern id="{{ $p }}-wax3" width="24" height="24" patternUnits="userSpaceOnUse">
    <rect width="24" height="24" fill="#7e22ce" />
    <path d="M0 12 Q6 4 12 12 T24 12" fill="none" stroke="#f9a8d4" stroke-width="3" /><circle cx="6" cy="19" r="2.4" fill="#fde047" /><circle cx="18" cy="5" r="2.4" fill="#fde047" />
</pattern>
<pattern id="{{ $p }}-kente" width="60" height="20" patternUnits="userSpaceOnUse">
    <rect width="20" height="20" fill="#facc15" /><rect x="20" width="20" height="20" fill="#15803d" /><rect x="40" width="20" height="20" fill="#b91c1c" />
    <rect y="8" width="60" height="4" fill="#111827" /><rect x="8" width="4" height="20" fill="#111827" opacity=".55" /><rect x="48" width="4" height="20" fill="#111827" opacity=".55" />
</pattern>
<pattern id="{{ $p }}-bogolan" width="28" height="28" patternUnits="userSpaceOnUse">
    <rect width="28" height="28" fill="#78350f" />
    <path d="M4 4h8M16 4h8M4 24h8M16 24h8" stroke="#fef3c7" stroke-width="2" /><circle cx="14" cy="14" r="4" fill="none" stroke="#fef3c7" stroke-width="2" />
</pattern>
