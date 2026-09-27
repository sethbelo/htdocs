@props(['title', 'subtitle' => null, 'eyebrow' => null, 'back' => null])

<header {{ $attributes->class('flex flex-col gap-4 md:flex-row md:items-end md:justify-between') }}>
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            @if ($back)
                <a href="{{ $back }}" wire:navigate class="shell-icon-btn -ms-2 size-8" aria-label="Retour">
                    <flux:icon.arrow-left variant="micro" class="size-4" />
                </a>
            @endif
            @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        </div>
        <flux:heading size="xl" level="1" class="mt-0.5">{{ $title }}</flux:heading>
        @if ($subtitle)
            <flux:text class="mt-1 max-w-3xl">{{ $subtitle }}</flux:text>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
