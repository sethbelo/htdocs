@props(['value'])

<flux:label {{ $attributes }}>{{ $value ?? $slot }}</flux:label>
