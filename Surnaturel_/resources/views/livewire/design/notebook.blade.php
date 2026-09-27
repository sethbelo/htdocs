@php
    $job = $this->job;
    $order = $job->order;
    $locked = (bool) $job->brief?->isValidatedForPrint();
    $canEdit = auth()->user()->can('design.update') && ! $locked;
    $w = (float) ($brief['width_mm'] ?: 0);
    $h = (float) ($brief['height_mm'] ?: 0);
    $ratio = $w > 0 && $h > 0 ? $w / $h : 0.707;
    $previewW = $ratio >= 1 ? 200 : round(200 * $ratio);
    $previewH = $ratio >= 1 ? round(200 / $ratio) : 200;
@endphp

<div class="space-y-6">
    <x-page-header :title="$brief['title'] ?: 'Cahier de conception'" :eyebrow="'Cahier de conception · '.$order->number" :back="route('design.board')"
        :subtitle="$order->client->displayName().' · délai '.($order->due_at?->translatedFormat('d M à H:i') ?? 'non défini').' · graphiste '.($job->designer?->name ?? 'non assigné')">
        <x-slot:actions>
            <x-status-badge :status="$job->status" />
            <flux:button size="sm" icon="document-arrow-down" :href="route('print.design-brief', $job)" target="_blank">Fiche technique PDF</flux:button>
            @if ($canEdit)<flux:button size="sm" icon="check" wire:click="saveBrief">Enregistrer</flux:button>@endif
        </x-slot:actions>
    </x-page-header>

    @if ($locked)
        <flux:callout variant="success" icon="printer" heading="Transmis à la salle d’impression"
            :text="'Conception validée par '.$job->brief->validator?->name.' le '.$job->brief->validated_for_print_at->format('d/m/Y à H:i').' — '.$job->brief->printSummary()" />
    @elseif (! in_array($job->status->value, ['validated', 'sent_to_print']))
        <flux:callout icon="information-circle" heading="Étape suivante" text="Rédigez le brief, déposez vos versions depuis l’atelier graphique et faites valider le BAT par le client. Vous pourrez ensuite donner le feu vert à l’impression." />
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="panel p-5">
                <div class="flex items-center gap-2"><flux:icon.light-bulb class="text-safran-500" /><flux:heading>Brief créatif</flux:heading></div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="brief.title" label="Titre du projet" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.target_audience" label="Cible" placeholder="Grand public, entreprises, étudiants…" :disabled="! $canEdit" />
                    <flux:textarea wire:model="brief.objective" label="Objectif & message clé" rows="3" class="md:col-span-2" :disabled="! $canEdit" placeholder="Que doit ressentir ou faire la personne qui voit ce support ?" />
                    <flux:textarea wire:model="brief.content" label="Textes et éléments à intégrer" rows="5" class="md:col-span-2" :disabled="! $canEdit" placeholder="Titres, slogans, coordonnées, logos, photos fournies par le client…" />
                    <flux:textarea wire:model="brief.style_notes" label="Direction artistique" rows="3" class="md:col-span-2" :disabled="! $canEdit" placeholder="Couleurs de la marque, polices, ambiance, références…" />
                </div>
            </div>

            <div class="panel p-5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2"><flux:icon.book-open class="text-brand-500" /><flux:heading>Carnet de travail</flux:heading></div>
                    <span class="text-xs text-fg-subtle">{{ $job->notes->count() }} note(s)</span>
                </div>
                @can('design.update')
                    <form wire:submit="addNote" class="mt-4 space-y-2">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($noteTypes as $type)
                                <button type="button" wire:click="$set('noteType', '{{ $type->value }}')" @class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition', 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-white/10 dark:text-white' => $noteType === $type->value, 'border-line text-fg-muted hover:border-line-strong' => $noteType !== $type->value])>
                                    <flux:icon :icon="$type->icon()" variant="micro" /> {{ $type->label() }}
                                </button>
                            @endforeach
                        </div>
                        <flux:textarea wire:model="noteBody" rows="3" placeholder="Idée, retour du client, décision, tâche à faire…" />
                        <flux:error name="noteBody" />
                        <div class="flex justify-end"><flux:button type="submit" size="sm" icon="plus">Ajouter au carnet</flux:button></div>
                    </form>
                @endcan
                <ul class="mt-4 space-y-2">
                    @forelse ($job->notes as $note)
                        <li class="group flex gap-3 rounded-xl border border-line p-3" wire:key="note-{{ $note->id }}">
                            @if ($note->type->value === 'todo')
                                <button type="button" wire:click="toggleTodo({{ $note->id }})" class="mt-0.5 shrink-0" aria-label="Cocher">
                                    <flux:icon :icon="$note->is_done ? 'check-circle' : 'stop'" variant="mini" @class(['text-emerald-500' => $note->is_done, 'text-fg-subtle' => ! $note->is_done]) />
                                </button>
                            @else
                                <flux:icon :icon="$note->type->icon()" variant="mini" class="mt-0.5 shrink-0 text-fg-subtle" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-fg-subtle">
                                    <flux:badge size="sm" :color="$note->type->color()">{{ $note->type->label() }}</flux:badge>
                                    {{ $note->user->name }} · {{ $note->created_at->format('d/m/Y H:i') }}
                                </div>
                                <p @class(['mt-1 whitespace-pre-line text-sm', 'line-through text-fg-subtle' => $note->is_done])>{{ $note->body }}</p>
                            </div>
                            @if ($note->user_id === auth()->id())
                                <button type="button" wire:click="deleteNote({{ $note->id }})" wire:confirm="Supprimer cette note ?" class="shrink-0 text-fg-subtle opacity-0 transition group-hover:opacity-100 hover:text-carmin-600"><flux:icon.trash variant="micro" /></button>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-line p-6 text-center text-sm text-fg-subtle">Le carnet est vide : notez vos idées, retours client et décisions au fil du projet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="panel p-5">
                <flux:heading>Commande & versions</flux:heading>
                <ul class="mt-3 space-y-2">
                    @foreach ($order->items as $item)
                        <li class="rounded-lg bg-zinc-50 p-2.5 text-sm dark:bg-white/4" wire:key="it-{{ $item->id }}"><span class="font-medium">{{ (float) $item->quantity }} × {{ $item->description }}</span><div class="text-fg-subtle">{{ $item->specSummary() ?: '—' }}</div></li>
                    @endforeach
                </ul>
                <ul class="mt-3 divide-y divide-line text-sm">
                    @forelse ($job->versions as $version)
                        <li class="flex items-center justify-between gap-2 py-2" wire:key="ver-{{ $version->id }}">
                            <span><span class="font-semibold">v{{ $version->version }}</span> · {{ $version->original_name }} <span class="text-xs text-fg-subtle">{{ $version->created_at->format('d/m H:i') }} · {{ $version->uploader?->name }}</span></span>
                            <flux:button size="xs" icon="eye" :href="route('files.design', $version)" target="_blank">Voir</flux:button>
                        </li>
                    @empty
                        <li class="py-2 text-fg-subtle">Aucune version déposée — utilisez l’atelier graphique pour déposer vos propositions.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="panel p-5">
                <div class="flex items-center gap-2"><flux:icon.printer class="text-cyan2-500" /><flux:heading>Fiche technique d’impression</flux:heading></div>

                <div class="mt-4 flex justify-center rounded-xl bg-zinc-100 p-6 dark:bg-white/4">
                    <div class="relative grid place-items-center rounded-sm bg-white text-center shadow-md ring-1 ring-black/5 dark:bg-zinc-800" style="width: {{ $previewW }}px; height: {{ $previewH }}px;">
                        <div class="absolute inset-1.5 rounded-[1px] border border-dashed border-carmin-400/70"></div>
                        <div class="px-2 text-xs leading-tight text-fg-muted">
                            <div class="font-display text-base font-bold text-fg">{{ $brief['final_format'] ?: 'Format libre' }}</div>
                            {{ $w ? rtrim(rtrim(number_format($w, 1, ',', ''), '0'), ',') : '?' }} × {{ $h ? rtrim(rtrim(number_format($h, 1, ',', ''), '0'), ',') : '?' }} mm
                            <div class="text-[10px] text-carmin-500">+ {{ $brief['bleed_mm'] ?: 0 }} mm de fonds perdus</div>
                        </div>
                    </div>
                </div>

                @if ($canEdit)
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach (array_keys($formats) as $format)
                            <button type="button" wire:click="applyFormat('{{ $format }}')" class="rounded-md border border-line px-2 py-0.5 text-[11px] font-medium text-fg-muted transition hover:border-brand-400 hover:text-brand-600">{{ $format }}</button>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <flux:input wire:model.live.debounce.500ms="brief.width_mm" type="number" step="0.1" label="Largeur (mm)" :disabled="! $canEdit" />
                    <flux:input wire:model.live.debounce.500ms="brief.height_mm" type="number" step="0.1" label="Hauteur (mm)" :disabled="! $canEdit" />
                    <flux:input wire:model.live.debounce.500ms="brief.bleed_mm" type="number" step="0.5" label="Fonds perdus (mm)" :disabled="! $canEdit" />
                    <flux:select wire:model="brief.orientation" label="Orientation" :disabled="! $canEdit"><flux:select.option value="portrait">Portrait</flux:select.option><flux:select.option value="paysage">Paysage</flux:select.option></flux:select>
                    <flux:input wire:model="brief.final_format" label="Format" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.colors" label="Couleurs" placeholder="Quadri 4/4 CMJN" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.paper" label="Papier / support" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.grammage" type="number" label="Grammage (g/m²)" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.quantity" type="number" label="Quantité" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.pages" type="number" label="Pages" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.finishing" label="Finitions" class="col-span-2" :disabled="! $canEdit" />
                    <flux:input wire:model="brief.final_file_name" label="Fichier final" :placeholder="$job->latestVersion?->original_name" class="col-span-2" :disabled="! $canEdit" />
                    <flux:textarea wire:model="brief.print_notes" label="Consignes pour l’imprimeur" rows="2" class="col-span-2" :disabled="! $canEdit" />
                </div>
            </div>

            <div class="panel p-5">
                <flux:heading>Contrôles pré-presse</flux:heading>
                <div class="mt-3 space-y-2">
                    @foreach ($checks as $key => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 text-sm transition hover:bg-zinc-50 dark:hover:bg-white/4">
                            <input type="checkbox" wire:model.live="checklist.{{ $key }}" @disabled(! $canEdit) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                            <span @class(['text-fg-subtle line-through' => $checklist[$key] ?? false])>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/8"><div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ round(collect($checklist)->filter()->count() / max(1, count($checks)) * 100) }}%"></div></div>

                @if (! $locked)
                    @can('design.work')
                        <flux:button class="mt-4 w-full" variant="primary" icon="paper-airplane" wire:click="validateForPrint"
                            wire:confirm="Confirmer que la conception est terminée ? La fiche sera verrouillée et la salle d’impression prévenue.">Conception terminée — transmettre à l’impression</flux:button>
                    @endcan
                @endif
            </div>
        </div>
    </div>
</div>
