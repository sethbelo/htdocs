@props(['active'])

<flux:navbar.item :current="$active ?? false" {{ $attributes }}>{{ $slot }}</flux:navbar.item>
