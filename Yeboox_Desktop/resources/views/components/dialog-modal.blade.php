@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <flux:heading size="lg">{{ $title }}</flux:heading>
    <flux:text class="mt-2">{{ $content }}</flux:text>
    <div class="mt-6 flex flex-row justify-end gap-2">
        {{ $footer }}
    </div>
</x-modal>
