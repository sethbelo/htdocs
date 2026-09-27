{{--
    Panneau latéral piloté par une propriété Livewire booléenne :
    <x-ui.drawer wire:model="showForm" title="Nouveau bon" size="lg"> … <x-slot:footer> … </x-slot:footer></x-ui.drawer>
--}}
@props(['title' => null, 'subtitle' => null, 'size' => null, 'icon' => null])
<div x-data="{ open: @entangle($attributes->wire('model')) }" x-on:keydown.escape.window="open = false" x-cloak>
    <template x-teleport="body">
        <div x-show="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity class="overlay" x-on:click="open = false"></div>
            <div x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-8 opacity-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-x-8 opacity-0"
                 @class(['drawer', 'drawer-'.$size => $size])>
                <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($icon)<span class="grid size-9 shrink-0 place-items-center rounded-xl bg-sun-100 text-sun-800 dark:bg-sun-400/15 dark:text-sun-300"><x-ui.icon :name="$icon" /></span>@endif
                        <div class="min-w-0">
                            <h2 class="truncate font-display text-[18px] font-semibold text-fg">{{ $title }}</h2>
                            @if ($subtitle)<p class="truncate text-[12.5px] text-fg-subtle">{{ $subtitle }}</p>@endif
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-icon btn-sm -mr-2" x-on:click="open = false" aria-label="Fermer"><x-ui.icon name="x-mark" /></button>
                </header>
                <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">{{ $slot }}</div>
                @isset($footer)
                    <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-line bg-surface-2 px-5 py-3.5 sm:px-6">{{ $footer }}</footer>
                @endisset
            </div>
        </div>
    </template>
</div>
