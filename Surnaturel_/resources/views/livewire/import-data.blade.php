<div class="contents">
    @can($definition['permission'])
        <flux:modal.trigger :name="'import-'.$type">
            <flux:button size="sm" icon="arrow-up-tray">Importer</flux:button>
        </flux:modal.trigger>

        <flux:modal :name="'import-'.$type" class="w-full max-w-3xl" x-on:close="$wire.restart()">
            <div class="space-y-5">
                <div class="flex items-start gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-cyan2-50 text-cyan2-600 dark:bg-cyan2-400/10 dark:text-cyan2-300"><flux:icon.arrow-up-tray /></span>
                    <div>
                        <flux:heading size="lg">Importer : {{ $definition['label'] }}</flux:heading>
                        <flux:text>Fichier Excel (.xlsx) ou CSV. Les lignes existantes sont mises à jour (clé : {{ $definition['key'] }}), les autres sont créées.</flux:text>
                    </div>
                </div>

                @if ($done && $result)
                    <div class="grid grid-cols-3 gap-3">
                        <x-kpi label="Créés" :value="$result['created']" icon="plus-circle" color="emerald" />
                        <x-kpi label="Mis à jour" :value="$result['updated']" icon="arrow-path" color="sky" />
                        <x-kpi label="Ignorés (erreurs)" :value="$result['skipped']" icon="exclamation-triangle" color="red" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <flux:button wire:click="restart" icon="arrow-up-tray">Importer un autre fichier</flux:button>
                        <flux:button variant="primary" x-on:click="window.location.reload()">Fermer et actualiser</flux:button>
                    </div>
                @else
                    <ol class="grid gap-3 sm:grid-cols-3">
                        <li class="rounded-xl border border-line p-3">
                            <div class="text-xs font-bold tracking-wide text-cyan2-600 uppercase">Étape 1</div>
                            <div class="mt-1 text-sm font-semibold">Télécharger le modèle</div>
                            <flux:button size="xs" class="mt-2" icon="document-arrow-down" wire:click="downloadTemplate">Modèle Excel</flux:button>
                        </li>
                        <li class="rounded-xl border border-line p-3 sm:col-span-2">
                            <div class="text-xs font-bold tracking-wide text-cyan2-600 uppercase">Étape 2</div>
                            <div class="mt-1 text-sm font-semibold">Déposer le fichier rempli</div>
                            <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="mt-2 block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:font-semibold file:text-white dark:file:bg-cyan2-400 dark:file:text-brand-950" />
                            <div wire:loading wire:target="file" class="mt-1 text-xs text-fg-subtle">Lecture du fichier…</div>
                            <flux:error name="file" />
                        </li>
                    </ol>

                    <div class="flex flex-wrap gap-1.5 text-[11px]">
                        @foreach ($definition['columns'] as $key => $column)
                            <span @class(['rounded-md px-1.5 py-0.5 font-mono', 'bg-brand-700 text-white dark:bg-cyan2-400 dark:text-brand-950' => str_contains($column[1], 'required'), 'bg-zinc-100 text-zinc-600 dark:bg-white/8 dark:text-zinc-300' => ! str_contains($column[1], 'required')]) title="{{ $column[0] }}{{ isset($column[3]) ? ' — '.$column[3] : '' }}">{{ $key }}</span>
                        @endforeach
                    </div>

                    @if ($rows)
                        @php($columns = array_keys($definition['columns']))
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <flux:badge color="sky">{{ count($rows) }} ligne(s) lue(s)</flux:badge>
                            <flux:badge color="emerald">{{ count($rows) - count($rowErrors) }} valide(s)</flux:badge>
                            @if ($rowErrors)<flux:badge color="red">{{ count($rowErrors) }} en erreur (seront ignorées)</flux:badge>@endif
                        </div>
                        <div class="max-h-72 overflow-auto rounded-xl border border-line">
                            <table class="w-full min-w-max text-xs">
                                <thead class="sticky top-0 bg-zinc-100 text-left dark:bg-zinc-900"><tr><th class="px-2 py-1.5">#</th>@foreach ($columns as $c)<th class="px-2 py-1.5 font-mono">{{ $c }}</th>@endforeach<th class="px-2 py-1.5">Contrôle</th></tr></thead>
                                <tbody class="divide-y divide-line">
                                    @foreach (array_slice($rows, 0, 50, true) as $index => $row)
                                        <tr @class(['bg-carmin-50/60 dark:bg-carmin-900/20' => isset($rowErrors[$index])])>
                                            <td class="px-2 py-1.5 text-fg-subtle">{{ $index + 2 }}</td>
                                            @foreach ($columns as $c)<td class="px-2 py-1.5">{{ $row[$c] ?? '' }}</td>@endforeach
                                            <td class="px-2 py-1.5">@if (isset($rowErrors[$index]))<span class="text-carmin-600">{{ implode(' ', $rowErrors[$index]) }}</span>@else<span class="text-emerald-600">OK</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (count($rows) > 50)<p class="text-xs text-fg-subtle">Aperçu des 50 premières lignes.</p>@endif
                        <div class="flex justify-end gap-2">
                            <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                            <flux:button variant="primary" icon="check" wire:click="import" :disabled="count($rows) === count($rowErrors)" wire:loading.attr="disabled" wire:target="import">Importer {{ count($rows) - count($rowErrors) }} ligne(s)</flux:button>
                        </div>
                    @endif
                @endif
            </div>
        </flux:modal>
    @endcan
</div>
