<div>
    <x-ui.page-header title="Mes notes" eyebrow="Espace personnel" subtitle="Prenez des notes riches : titres, listes, cases à cocher, citations. Enregistrement automatique, privé et imprimable.">
        <x-slot:actions>
            <x-ui.button variant="accent" icon="plus" wire:click="create">Nouvelle note</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('livewire.personal.partials.nav', ['current' => 'personal.notes'])

    <div class="grid items-start gap-5 lg:grid-cols-[300px_minmax(0,1fr)]">
        {{-- ═══ LISTE ═══════════════════════════════════════════════════════ --}}
        <aside class="card overflow-hidden lg:sticky lg:top-20">
            <div class="border-b border-line p-3"><x-ui.search wire:model.live.debounce.300ms="search" placeholder="Rechercher une note…" class="sm:w-full" /></div>
            <ul class="max-h-[34vh] divide-y divide-line overflow-y-auto lg:max-h-[calc(100dvh-260px)]">
                @forelse ($notes as $item)
                    <li wire:key="note-item-{{ $item->id }}">
                        <button type="button" wire:click="select({{ $item->id }})" @class(['block w-full px-4 py-3 text-left transition', 'bg-brand-50/70 dark:bg-white/6' => $note?->id === $item->id, 'hover:bg-surface-2' => $note?->id !== $item->id])>
                            <span class="flex items-center gap-1.5">
                                @if ($item->is_pinned)<x-ui.icon name="bookmark" variant="s" class="size-3.5 text-sun-500" />@endif
                                <span @class(['truncate text-[13.5px] font-semibold', 'text-brand-700 dark:text-lagoon-200' => $note?->id === $item->id])>{{ $item->title }}</span>
                            </span>
                            <span class="mt-0.5 line-clamp-2 block text-[12.5px] text-fg-subtle">{{ $item->excerpt(90) ?: 'Note vide' }}</span>
                            <span class="mt-1 block text-[11px] text-fg-subtle">{{ $item->updated_at->translatedFormat('j M Y · H:i') }}</span>
                        </button>
                    </li>
                @empty
                    <li><x-ui.empty icon="pencil-square" :title="$search ? 'Aucun résultat' : 'Aucune note'" :text="$search ? 'Essayez un autre mot-clé.' : 'Créez votre première note.'" /></li>
                @endforelse
            </ul>
            <p class="border-t border-line px-4 py-2.5 text-[12px] text-fg-subtle">{{ $notes->count() }} note(s) · visibles par vous seul</p>
        </aside>

        {{-- ═══ ÉDITEUR ═════════════════════════════════════════════════════ --}}
        @if ($note)
            <section class="card min-w-0" wire:key="note-editor-{{ $note->id }}">
              <div wire:ignore x-data="noteEditor({ id: {{ $note->id }}, title: @js($note->title) })" x-init="pinned = @js($note->is_pinned)">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-2.5 sm:px-5">
                    <p class="flex items-center gap-2 text-[12px] font-medium text-fg-subtle" aria-live="polite">
                        <span class="size-2 rounded-full" :class="{ 'bg-emerald-500': state === 'saved', 'bg-sun-400': state === 'dirty', 'animate-pulse bg-lagoon-400': state === 'saving' }"></span>
                        <span x-text="state === 'saved' ? 'Enregistré' : (state === 'saving' ? 'Enregistrement…' : 'Modifications non enregistrées')">Enregistré</span>
                    </p>
                    <div class="flex flex-wrap items-center gap-1">
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" :class="pinned && 'text-sun-600'" :title="pinned ? 'Désépingler' : 'Épingler'" aria-label="Épingler" x-on:click="pinned = !pinned; $wire.togglePin({{ $note->id }})">
                            <span x-show="!pinned" class="contents"><x-ui.icon name="bookmark" class="size-4" /></span><span x-show="pinned" x-cloak class="contents"><x-ui.icon name="bookmark" variant="s" class="size-4" /></span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Dupliquer" aria-label="Dupliquer" x-on:click="await save(); $wire.duplicate({{ $note->id }})"><x-ui.icon name="document-duplicate" class="size-4" /></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Imprimer" aria-label="Imprimer" x-on:click="openAfterSave(@js(route('personal.notes.print', $note)))"><x-ui.icon name="printer" class="size-4" /></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Exporter en PDF" aria-label="Exporter en PDF" x-on:click="openAfterSave(@js(route('personal.notes.pdf', $note)))"><x-ui.icon name="document-arrow-down" class="size-4" /></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon hover:text-flame-600" title="Supprimer" aria-label="Supprimer" x-on:click="if (confirm('Supprimer définitivement cette note ?')) { state = 'saved'; $wire.delete({{ $note->id }}) }"><x-ui.icon name="trash" class="size-4" /></button>
                        <button type="button" class="btn btn-primary btn-sm ml-1" x-on:click="save()" :disabled="state === 'saving'"><x-ui.icon name="check" class="size-4" />Enregistrer</button>
                    </div>
                </header>

                <div class="px-4 pt-5 sm:px-10 sm:pt-8">
                    <input type="text" x-model="title" x-on:input="changed()" x-on:keydown.enter.prevent="focus()" maxlength="255" placeholder="Sans titre" aria-label="Titre de la note"
                           class="w-full border-0 bg-transparent p-0 font-display text-[30px] leading-tight font-semibold text-fg placeholder:text-fg-subtle/60 focus:ring-0 focus:outline-none">
                    <p class="mt-1 text-[12px] text-fg-subtle">Créée le {{ $note->created_at->translatedFormat('j F Y') }} · modifiée {{ $note->updated_at->diffForHumans() }}</p>

                    {{-- Barre d'outils --}}
                    <div class="rt-toolbar sticky top-[72px] z-10 mt-4" role="toolbar" aria-label="Mise en forme" x-on:mousedown.prevent>
                        <button type="button" class="rt-btn" title="Titre 1 (# + espace)" :aria-pressed="!!active.h1" x-on:click="block('h1')"><x-ui.icon name="h1" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Titre 2 (## + espace)" :aria-pressed="!!active.h2" x-on:click="block('h2')"><x-ui.icon name="h2" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Titre 3 (### + espace)" :aria-pressed="!!active.h3" x-on:click="block('h3')"><x-ui.icon name="h3" class="size-4" /></button>
                        <span class="rt-sep"></span>
                        <button type="button" class="rt-btn" title="Gras (Ctrl+B)" :aria-pressed="!!active.bold" x-on:click="exec('bold')"><x-ui.icon name="bold" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Italique (Ctrl+I)" :aria-pressed="!!active.italic" x-on:click="exec('italic')"><x-ui.icon name="italic" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Souligné (Ctrl+U)" :aria-pressed="!!active.underline" x-on:click="exec('underline')"><x-ui.icon name="underline" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Barré" :aria-pressed="!!active.strike" x-on:click="exec('strikeThrough')"><x-ui.icon name="strikethrough" class="size-4" /></button>
                        <span class="rt-sep"></span>
                        <button type="button" class="rt-btn" title="Liste à puces (- + espace)" :aria-pressed="!!active.ul" x-on:click="exec('insertUnorderedList')"><x-ui.icon name="list-bullet" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Liste numérotée (1. + espace)" :aria-pressed="!!active.ol" x-on:click="exec('insertOrderedList')"><x-ui.icon name="numbered-list" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Liste de contrôle ([] + espace)" :aria-pressed="!!active.checklist" x-on:click="checklist()"><x-ui.icon name="check-circle" class="size-4" /></button>
                        <span class="rt-sep"></span>
                        <button type="button" class="rt-btn" title="Citation (> + espace)" :aria-pressed="!!active.quote" x-on:click="block('blockquote')"><x-ui.icon name="chat-bubble-bottom-center-text" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Séparateur (--- + espace)" x-on:click="divider()"><x-ui.icon name="minus" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Lien" x-on:click="link()"><x-ui.icon name="link" class="size-4" /></button>
                        <button type="button" class="rt-btn" title="Effacer la mise en forme" x-on:click="exec('removeFormat'); block('p')"><x-ui.icon name="x-mark" class="size-4" /></button>
                    </div>
                </div>

                <div class="relative px-4 pt-4 pb-10 sm:px-10">
                    <div x-ref="editor" contenteditable="true" spellcheck="true" role="textbox" aria-multiline="true" aria-label="Contenu de la note"
                         data-placeholder="Commencez à écrire… (# titre, - liste, [] case à cocher, > citation)"
                         class="note-prose min-h-[48vh]">{!! $note->content !!}</div>
                </div>
              </div>
            </section>
        @else
            <div class="card"><x-ui.empty icon="pencil-square" title="Aucune note sélectionnée" text="Créez une note pour commencer : comptes rendus, listes de contrôle, idées…"><x-ui.button variant="primary" icon="plus" wire:click="create">Nouvelle note</x-ui.button></x-ui.empty></div>
        @endif
    </div>
</div>
