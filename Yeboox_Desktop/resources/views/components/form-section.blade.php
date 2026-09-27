@props(['submit'])

<div {{ $attributes->merge(['class' => 'md:grid md:grid-cols-3 md:gap-6']) }}>
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <form wire:submit="{{ $submit }}">
            <flux:card class="{{ isset($actions) ? 'rounded-b-none!' : '' }}">
                <div class="grid grid-cols-6 gap-6">
                    {{ $form }}
                </div>
            </flux:card>

            @if (isset($actions))
                <flux:footer class="justify-end rounded-b-xl border border-t-0 border-zinc-200 p-4 dark:border-zinc-700">
                    {{ $actions }}
                </flux:footer>
            @endif
        </form>
    </div>
</div>
