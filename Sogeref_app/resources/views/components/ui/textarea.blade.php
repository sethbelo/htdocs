@props(['label' => null, 'hint' => null, 'rows' => 3, 'required' => false])
@php $model = $attributes->wire('model')->value(); @endphp
<x-ui.field :label="$label" :for="$model" :hint="$hint" :required="$required" :class="$attributes->get('class')">
    <textarea id="{{ $model }}" rows="{{ $rows }}" {{ $attributes->except('class')->class(['input', 'input-error' => $model && $errors->has($model)]) }}></textarea>
</x-ui.field>
