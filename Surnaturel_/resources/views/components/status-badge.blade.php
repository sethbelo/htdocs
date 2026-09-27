@props(['status', 'size' => 'sm'])

@if ($status)
    <flux:badge :size="$size" :color="$status->color()" {{ $attributes }}>{{ $status->label() }}</flux:badge>
@endif
