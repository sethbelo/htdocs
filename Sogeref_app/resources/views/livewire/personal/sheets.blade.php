<div>
    <x-ui.page-header title="Mes tableurs" eyebrow="Espace personnel" subtitle="Feuilles de calcul personnelles : saisie au clavier, formules =SUM(A1:A5), =AVERAGE, =MIN, =MAX, export Excel et PDF.">
        <x-slot:actions>
            <x-ui.button variant="accent" icon="plus" wire:click="create">Nouveau classeur</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>


    {{-- Classeurs --}}
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
        @foreach ($sheets as $item)
            <button type="button" wire:key="sheet-tab-{{ $item->id }}" wire:click="select({{ $item->id }})" @class(['btn btn-sm shrink-0', 'btn-soft ring-1 ring-brand-200 dark:ring-white/15' => $sheet?->id === $item->id, 'btn-secondary' => $sheet?->id !== $item->id])>
                <x-ui.icon name="table-cells" class="size-4 text-emerald-600" />{{ \Illuminate\Support\Str::limit($item->title, 32) }}
            </button>
        @endforeach
    </div>

    @if ($sheet)
        <section class="card min-w-0" wire:key="sheet-editor-{{ $sheet->id }}">
            <div wire:ignore x-data="sheetEditor({ id: {{ $sheet->id }}, title: @js($sheet->title), rows: {{ $sheet->rows }}, cols: {{ $sheet->cols }}, cells: @js((object) ($sheet->cells ?? [])), maxRows: {{ \App\Models\PersonalSheet::MAX_ROWS }}, maxCols: {{ \App\Models\PersonalSheet::MAX_COLS }} })">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300"><x-ui.icon name="table-cells" /></span>
                        <div class="min-w-0 flex-1">
                            <input type="text" x-model="title" x-on:input="changed()" maxlength="255" aria-label="Nom du classeur" class="w-full border-0 bg-transparent p-0 font-display text-[18px] font-semibold text-fg focus:ring-0 focus:outline-none">
                            <p class="flex items-center gap-2 text-[12px] text-fg-subtle" aria-live="polite">
                                <span class="size-2 rounded-full" :class="{ 'bg-emerald-500': state === 'saved', 'bg-sun-400': state === 'dirty', 'animate-pulse bg-lagoon-400': state === 'saving' }"></span>
                                <span x-text="state === 'saved' ? 'Enregistré' : (state === 'saving' ? 'Enregistrement…' : 'Non enregistré')">Enregistré</span>
                                <span>·</span><span x-text="rows + ' lignes × ' + cols + ' colonnes'"></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1">
                        <button type="button" class="btn btn-ghost btn-sm" title="Imprimer" x-on:click="openAfterSave(@js(route('personal.sheets.print', $sheet)))"><x-ui.icon name="printer" class="size-4" /><span class="max-sm:hidden">Imprimer</span></button>
                        <button type="button" class="btn btn-ghost btn-sm" title="Exporter en Excel (.xlsx)" x-on:click="openAfterSave(@js(route('personal.sheets.xlsx', $sheet)))"><x-ui.icon name="arrow-down-tray" class="size-4" /><span class="max-sm:hidden">Excel</span></button>
                        <button type="button" class="btn btn-ghost btn-sm" title="Exporter en PDF" x-on:click="openAfterSave(@js(route('personal.sheets.pdf', $sheet)))"><x-ui.icon name="document-arrow-down" class="size-4" /><span class="max-sm:hidden">PDF</span></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Dupliquer" aria-label="Dupliquer" x-on:click="await save(); $wire.duplicate({{ $sheet->id }})"><x-ui.icon name="document-duplicate" class="size-4" /></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon hover:text-flame-600" title="Supprimer" aria-label="Supprimer" x-on:click="if (confirm('Supprimer définitivement ce classeur ?')) { state = 'saved'; clearTimeout(timer); $wire.delete({{ $sheet->id }}) }"><x-ui.icon name="trash" class="size-4" /></button>
                        <button type="button" class="btn btn-primary btn-sm ml-1" x-on:click="save()" :disabled="state === 'saving'"><x-ui.icon name="check" class="size-4" />Enregistrer</button>
                    </div>
                </header>

                {{-- Barre d'outils + barre de formule --}}
                <div class="flex flex-wrap items-center gap-2 border-b border-line bg-surface-2 px-3 py-2" x-on:mousedown="if ($event.target.closest('button')) $event.preventDefault()">
                    <div class="rt-toolbar border-0 bg-transparent p-0">
                        <button type="button" class="rt-btn gap-1 px-2" title="Insérer une ligne sous la sélection" x-on:click="addRow()"><x-ui.icon name="plus" class="size-3.5" />Ligne</button>
                        <button type="button" class="rt-btn gap-1 px-2" title="Insérer une colonne à droite" x-on:click="addCol()"><x-ui.icon name="plus" class="size-3.5" />Colonne</button>
                        <button type="button" class="rt-btn gap-1 px-2" title="Supprimer la ligne sélectionnée" x-on:click="removeRow()"><x-ui.icon name="minus" class="size-3.5" />Ligne</button>
                        <button type="button" class="rt-btn gap-1 px-2" title="Supprimer la colonne sélectionnée" x-on:click="removeCol()"><x-ui.icon name="minus" class="size-3.5" />Colonne</button>
                        <span class="rt-sep"></span>
                        @foreach (['SUM' => 'Σ Somme', 'AVERAGE' => 'Moyenne', 'MIN' => 'Min', 'MAX' => 'Max'] as $fn => $label)
                            <button type="button" class="rt-btn px-2" title="Insérer ={{ $fn }}(…)" x-on:click="insertFunction('{{ $fn }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="flex min-w-[240px] flex-1 items-center gap-2">
                        <span class="kbd min-w-12 text-center text-[12px]" x-text="selectedRef"></span>
                        <span class="font-serif text-[15px] text-fg-subtle italic">fx</span>
                        <input type="text" class="input h-8 flex-1 font-mono text-[13px]" aria-label="Barre de formule" placeholder="Valeur ou formule, ex. =SUM(B2:B8)"
                               x-model="formulaBar" x-on:keydown.enter.prevent="commit(); select(sel.r + 1, sel.c)" x-on:keydown.escape.prevent="cancel()">
                    </div>
                </div>

                <div class="sheet-wrap" x-ref="grid" tabindex="0" x-on:keydown="onGridKey($event)" role="grid" aria-label="Grille du tableur">
                    <table class="sheet">
                        <thead>
                            <tr>
                                <th></th>
                                <template x-for="c in cols" :key="'h' + c">
                                    <th :class="sel.c === c - 1 && 'is-active'" x-text="col(c - 1)"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in rows" :key="'r' + r">
                                <tr>
                                    <th :class="sel.r === r - 1 && 'is-active'" x-text="r"></th>
                                    <template x-for="c in cols" :key="'c' + r + '-' + c">
                                        <td :data-ref="ref(r - 1, c - 1)" x-on:mousedown="clickCell(r - 1, c - 1)" x-on:dblclick="startEdit()"
                                            :class="{ 'is-selected': sel.r === r - 1 && sel.c === c - 1, 'is-number': isNumber(r - 1, c - 1), 'is-error': isError(r - 1, c - 1) }"
                                            :title="raw(r - 1, c - 1).startsWith('=') ? raw(r - 1, c - 1) : ''">
                                            <span x-text="display(r - 1, c - 1)"></span>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <input type="text" x-ref="cellInput" class="sheet-input" style="display: none" x-model="draft" x-on:keydown.stop="onEditKey($event)" x-on:blur="commit()" aria-label="Édition de la cellule" autocomplete="off" spellcheck="false">
                </div>
                <p class="flex flex-wrap gap-x-4 gap-y-1 border-t border-line px-4 py-2.5 text-[11.5px] text-fg-subtle">
                    <span><span class="kbd">↑ ↓ ← →</span> se déplacer</span><span><span class="kbd">Entrée</span> / <span class="kbd">F2</span> modifier</span><span><span class="kbd">Tab</span> cellule suivante</span><span><span class="kbd">Suppr</span> effacer</span><span><span class="kbd">Échap</span> annuler</span><span><span class="kbd">Ctrl S</span> enregistrer</span>
                </p>
            </div>
        </section>
    @else
        <div class="card"><x-ui.empty icon="table-cells" title="Aucun classeur" text="Créez un tableur pour vos calculs, suivis et listes chiffrées."><x-ui.button variant="primary" icon="plus" wire:click="create">Nouveau classeur</x-ui.button></x-ui.empty></div>
    @endif
</div>
