@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <flux:callout variant="danger" icon="exclamation-triangle" heading="{{ $title }}">{{ $content }}</flux:callout>
    <div class="mt-6 flex flex-row justify-end gap-2">
        {{ $footer }}
    </div>
</x-modal>
