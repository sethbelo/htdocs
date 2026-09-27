{{-- Fenêtre modale centrée, pilotée par une propriété Livewire booléenne --}}
@props(['title' => null, 'icon' => null, 'tone' => 'brand', 'width' => 'max-w-lg'])
<div x-data="{ open: @entangle($attributes->wire('model')) }" x-on:keydown.escape.window="open = false" x-cloak>
    <template x-teleport="body">
        <div x-show="open" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto p-4" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity class="overlay" x-on:click="open = false"></div>
            <div x-show="open" x-transition.scale.95 class="modal {{ $width }}">
                <div class="flex items-start gap-4 p-5 sm:p-6">
                    @if ($icon)
                        <span @class(['grid size-10 shrink-0 place-items-center rounded-xl', 'bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300' => $tone === 'brand', 'bg-flame-50 text-flame-600' => $tone === 'danger', 'bg-sun-100 text-sun-800' => $tone === 'sun', 'bg-emerald-50 text-emerald-700' => $tone === 'success'])><x-ui.icon :name="$icon" /></span>
                    @endif
                    <div class="min-w-0 flex-1">
                        @if ($title)<h2 class="font-display text-[18px] font-semibold text-fg">{{ $title }}</h2>@endif
                        <div class="mt-2 text-[14px] text-fg-muted">{{ $slot }}</div>
                    </div>
                </div>
                @isset($footer)
                    <footer class="flex flex-wrap justify-end gap-2 rounded-b-2xl border-t border-line bg-surface-2 px-5 py-3.5 sm:px-6">{{ $footer }}</footer>
                @endisset
            </div>
        </div>
    </template>
</div>
