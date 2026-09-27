{{-- Champ de formulaire avec libellé, aide et erreur de validation --}}
@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false])
@php $errorKey = $error ?? $for; @endphp
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="label">{{ $label }}@if ($required)<span class="text-flame-500"> *</span>@endif</label>
    @endif
    {{ $slot }}
    @if ($errorKey)
        @error($errorKey)<p class="field-error">{{ $message }}</p>@enderror
    @endif
    @if ($hint)<p class="field-hint">{{ $hint }}</p>@endif
</div>
