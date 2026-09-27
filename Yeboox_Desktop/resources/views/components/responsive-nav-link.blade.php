@props(['active'])

<flux:sidebar.item :current="$active ?? false" {{ $attributes }}>{{ $slot }}</flux:sidebar.item>
