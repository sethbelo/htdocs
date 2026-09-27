{{-- État vide : icône, titre, texte et action --}}
@props(['icon' => 'inbox', 'title' => 'Aucun élément', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-white/6 dark:text-lagoon-300"><x-ui.icon :name="$icon" class="size-7" /></span>
    <p class="mt-4 font-display text-[16px] font-semibold text-fg">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-[13.5px] text-fg-subtle">{{ $text }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>@endif
</div>
