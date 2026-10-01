{{-- Sélecteur de modèle : $templates (collection CommTemplate), $action (méthode Livewire recevant l'id) --}}
@php $action ??= 'useTemplate'; $args ??= ''; $align ??= 'left'; $up ??= false; @endphp
<div x-data="{ open: false }" class="relative" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <button type="button" class="btn btn-ghost btn-icon" x-on:click="open = !open" title="Insérer un modèle" aria-label="Insérer un modèle" @disabled($templates->isEmpty())>
        <x-ui.icon name="document-duplicate" />
    </button>
    <div x-show="open" x-cloak x-transition @class(['absolute z-30 w-72 max-w-[80vw] rounded-xl border border-line bg-surface p-1 shadow-[var(--shadow-pop)]', 'right-0' => $align === 'right', 'left-0' => $align !== 'right', 'bottom-full mb-2' => $up, 'mt-1' => ! $up])>
        <p class="px-2.5 pt-1.5 pb-1 text-[11px] font-semibold tracking-wider text-fg-subtle uppercase">Modèles</p>
        <div class="max-h-64 overflow-y-auto">
            @foreach ($templates as $template)
                <button type="button" wire:click="{{ $action }}({{ $template->id }}{{ $args }})" x-on:click="open = false" class="block w-full rounded-lg px-2.5 py-2 text-left hover:bg-surface-2">
                    <span class="block truncate text-[13px] font-semibold text-fg">{{ $template->name }}</span>
                    <span class="block truncate text-[12px] text-fg-subtle">{{ \Illuminate\Support\Str::limit($template->body, 80) }}</span>
                </button>
            @endforeach
        </div>
        <a href="{{ route('communication.templates') }}" wire:navigate class="mt-1 flex items-center gap-1.5 rounded-lg border-t border-line px-2.5 py-2 text-[12.5px] font-semibold text-brand-600 hover:bg-surface-2 dark:text-lagoon-300"><x-ui.icon name="cog-6-tooth" class="size-4" />Gérer les modèles</a>
    </div>
</div>
