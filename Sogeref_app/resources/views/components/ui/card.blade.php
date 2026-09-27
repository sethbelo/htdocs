{{-- Carte : titre, sous-titre, actions (slot) et corps --}}
@props(['title' => null, 'subtitle' => null, 'icon' => null, 'padded' => true])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <header class="card-header">
            <div class="flex min-w-0 items-center gap-3">
                @if ($icon)<span class="grid size-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300"><x-ui.icon :name="$icon" class="size-[18px]" /></span>@endif
                <div class="min-w-0">
                    @if ($title)<h2 class="card-title truncate">{{ $title }}</h2>@endif
                    @if ($subtitle)<p class="truncate text-[12.5px] text-fg-subtle">{{ $subtitle }}</p>@endif
                </div>
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['card-body' => $padded])>{{ $slot }}</div>
</section>
