@props(['icon' => 'inbox', 'title' => 'Aucun élément', 'text' => null])

<div {{ $attributes->class('flex flex-col items-center justify-center gap-1 py-10 text-center') }}>
    <span class="mb-2 grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-white/6 dark:text-cyan2-300">
        <flux:icon :icon="$icon" variant="mini" class="size-6" />
    </span>
    <flux:heading size="sm">{{ $title }}</flux:heading>
    @if ($text)
        <flux:text size="sm" class="max-w-sm">{{ $text }}</flux:text>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-3 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
