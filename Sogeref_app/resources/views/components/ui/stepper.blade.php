{{-- Étapes d'un processus : steps = [clé => libellé], current = clé courante --}}
@props(['steps', 'current', 'cancelled' => false])
@php
    $keys = array_keys($steps);
    $index = array_search($current, $keys, true);
@endphp
<ol {{ $attributes->merge(['class' => 'stepper']) }}>
    @foreach ($steps as $key => $label)
        @php $state = $cancelled ? 'todo' : ($loop->index < $index ? 'done' : ($loop->index === $index ? 'current' : 'todo')); @endphp
        <li class="step" data-state="{{ $state }}">
            <span class="step-dot">@if ($state === 'done')<x-ui.icon name="check" variant="s" class="size-4" />@else{{ $loop->iteration }}@endif</span>
            <span class="step-label">{{ $label }}</span>
        </li>
    @endforeach
</ol>
