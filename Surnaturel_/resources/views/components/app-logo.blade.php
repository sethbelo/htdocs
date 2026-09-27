{{--
    Logo SURNATUREL PRINT.
    variant : « mark » (monogramme, format rétractable / icône) ou « full » (logo horizontal).
    tone    : pour le logo horizontal, « auto » (suit le thème), « light » (fond sombre) ou « dark » (fond clair).
    Un logo personnalisé (Paramètres › Entreprise) remplace la marque par défaut.
--}}
@props(['variant' => 'mark', 'tone' => 'auto'])
@php
    $custom = \App\Models\Setting::get('company.logo_path');
    $name = \App\Models\Setting::get('company.name', 'Surnaturel Print');
@endphp
@if ($custom)
    <img src="{{ Storage::disk('public')->url($custom) }}" alt="{{ $name }}" {{ $attributes->class('object-contain') }} />
@elseif ($variant === 'full' && $tone === 'auto')
    <img src="{{ asset('brand/logo.svg') }}" alt="{{ $name }}" {{ $attributes->class('object-contain dark:hidden') }} />
    <img src="{{ asset('brand/logo-light.svg') }}" alt="" aria-hidden="true" {{ $attributes->class('hidden object-contain dark:block') }} />
@elseif ($variant === 'full')
    <img src="{{ asset($tone === 'light' ? 'brand/logo-light.svg' : 'brand/logo.svg') }}" alt="{{ $name }}" {{ $attributes->class('object-contain') }} />
@else
    <img src="{{ asset('brand/mark.svg') }}" alt="{{ $name }}" {{ $attributes->class('object-contain') }} />
@endif
