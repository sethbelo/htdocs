@props([
    'icon' => 'inbox',
    'title',
    'description' => null,
])

<div {{ $attributes->class('mx-auto flex max-w-md flex-col items-center px-4 py-3 text-center') }} role="status">
    <span class="grid size-12 place-items-center rounded-2xl bg-zinc-100 text-zinc-400 ring-1 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-500 dark:ring-zinc-700">
        <flux:icon :name="$icon" class="size-6" aria-hidden="true" />
    </span>
    <div class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $title }}</div>
    @if($description)
        <div class="mt-1 text-sm font-normal text-zinc-500 dark:text-zinc-400">{{ $description }}</div>
    @endif
</div>
