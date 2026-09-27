{{-- En-tête de page : fil d'Ariane, titre, description et actions --}}
@props(['title', 'subtitle' => null, 'eyebrow' => null, 'back' => null])
<header {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between']) }}>
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            @if ($back)
                <a href="{{ $back }}" wire:navigate class="btn btn-ghost btn-sm btn-icon -ml-2" aria-label="Retour"><x-ui.icon name="arrow-left" class="size-4" /></a>
            @endif
            @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        </div>
        <h1 class="mt-1 font-display text-[26px] leading-tight font-semibold text-fg sm:text-[28px]">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 max-w-3xl text-[14px] text-fg-muted">{{ $subtitle }}</p>@endif
        {{ $meta ?? '' }}
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
