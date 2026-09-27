{{-- Liste déroulante : options = [valeur => libellé] ou slot libre --}}
@props(['label' => null, 'hint' => null, 'options' => null, 'placeholder' => null, 'required' => false])
@php $model = $attributes->wire('model')->value(); @endphp
<x-ui.field :label="$label" :for="$model" :hint="$hint" :required="$required" :class="$attributes->get('class')">
    <select id="{{ $model }}" {{ $attributes->except('class')->class(['input', 'input-error' => $model && $errors->has($model)]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @if ($options)
            @foreach ($options as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
        @endif
        {{ $slot }}
    </select>
</x-ui.field>
