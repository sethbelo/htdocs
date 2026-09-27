{{-- Logo : image personnalisée (Paramètres > Entreprise) ou marque « S » d’imprimerie par défaut. --}}
@php($logo = \App\Models\Setting::get('company.logo_path'))
@if ($logo)
    <img src="{{ Storage::disk('public')->url($logo) }}" alt="Logo" {{ $attributes->class('object-contain') }} />
@else
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ \App\Models\Setting::get('company.name', 'Logo') }}" {{ $attributes }}>
        <defs>
            <linearGradient id="app-logo-bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4f46e5" /><stop offset="1" stop-color="#1e1b4b" /></linearGradient>
        </defs>
        <rect width="64" height="64" rx="16" fill="url(#app-logo-bg)" />
        <path d="M47 13H25a8.5 8.5 0 0 0 0 17h14a8.5 8.5 0 0 1 0 17H17" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" />
        <circle cx="25" cy="21.5" r="3.6" fill="#22d3ee" />
        <circle cx="39" cy="38.5" r="3.6" fill="#ec4899" />
        <path d="M50.5 8.5c0 0-3.2 4.3-3.2 6.2a3.2 3.2 0 0 0 6.4 0c0-1.9-3.2-6.2-3.2-6.2z" fill="#facc15" />
        <g transform="translate(12 53)"><rect width="10" height="3" rx="1.5" fill="#22d3ee" /><rect x="10.7" width="10" height="3" rx="1.5" fill="#ec4899" /><rect x="21.4" width="10" height="3" rx="1.5" fill="#facc15" /><rect x="32.1" width="7.9" height="3" rx="1.5" fill="#fff" opacity=".85" /></g>
    </svg>
@endif
