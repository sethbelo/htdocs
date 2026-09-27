<div class="space-y-6">
    <x-page-header title="Atelier graphique" subtitle="À concevoir → En conception → Proposition → Correction → BAT → Validée → Impression" />

    <div class="flex gap-1 overflow-x-auto rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800/60">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('view', '{{ $key }}')" wire:key="tab-{{ $key }}" @class(['flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition', 'bg-white shadow-sm dark:bg-zinc-900' => $view === $key, 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $view !== $key])>
                {{ $label }}
                <span @class(['rounded-full px-1.5 text-xs', 'bg-indigo-600 text-white' => $view === $key, 'bg-zinc-200 dark:bg-zinc-700' => $view !== $key])>{{ $this->counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="space-y-2 xl:col-span-2">
            @forelse ($this->jobs as $job)
                <button wire:click="open({{ $job->id }})" wire:key="job-{{ $job->id }}" @class(['panel block w-full p-3 text-left transition hover:border-indigo-300', 'ring-2 ring-indigo-500' => $jobId === $job->id])>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium">{{ $job->order->number }}</span>
                        <x-status-badge :status="$job->status" />
                    </div>
                    <div class="mt-1 truncate text-sm text-zinc-600 dark:text-zinc-300">{{ $job->order->client->displayName() }}</div>
                    <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500">
                        <flux:badge size="sm" :color="$job->order->priority->color()">{{ $job->order->priority->label() }}</flux:badge>
                        <span @class(['text-red-600 font-medium' => $job->order->isOverdue()])>Délai {{ $job->order->due_at?->format('d/m H:i') ?? '—' }}</span>
                        <span>· {{ $job->designer?->name ?? 'Non assigné' }}</span>
                        @if ($job->latestVersion)<span>· v{{ $job->latestVersion->version }}</span>@endif
                        @if ($job->brief?->validated_for_print_at)<flux:badge size="sm" color="emerald" icon="printer">Feu vert impression</flux:badge>@endif
                    </div>
                </button>
            @empty
                <div class="panel"><x-empty-state icon="paint-brush" title="Aucun travail dans cette vue" /></div>
            @endforelse
        </div>

        <div class="xl:col-span-3">
            @if ($this->job)
                @php($job = $this->job)
                <div class="panel space-y-5 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <flux:heading size="lg"><flux:link :href="route('orders.show', $job->order)" wire:navigate>{{ $job->order->number }}</flux:link></flux:heading>
                            <flux:text>{{ $job->order->client->displayName() }} · délai {{ $job->order->due_at?->translatedFormat('d M H:i') ?? 'non défini' }}</flux:text>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" icon="book-open" :href="route('design.notebook', $job)" wire:navigate>Cahier de conception</flux:button>
                            @if (in_array($job->status->value, ['to_design', 'correction']))
                                @can('design.work')<flux:button size="sm" variant="primary" icon="play" wire:click="takeAndStart">{{ $job->designer_id ? 'Démarrer' : 'Prendre & démarrer' }}</flux:button>@endcan
                            @endif
                            @can('design.work')
                                <flux:modal.trigger name="block"><flux:button size="sm" icon="hand-raised">Impossibilité / annuler</flux:button></flux:modal.trigger>
                            @endcan
                        </div>
                    </div>

                    @if ($job->blocked_reason)
                        <flux:callout variant="warning" icon="exclamation-triangle" :heading="$job->blocked_reason" :text="$job->report" />
                    @endif

                    @can('design.approve')
                        <div class="flex items-end gap-2">
                            <flux:select wire:model="assigneeId" label="Affecter à" class="max-w-xs">
                                <flux:select.option value="">—</flux:select.option>
                                @foreach ($this->designers as $designer)
                                    <flux:select.option :value="$designer->id">{{ $designer->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button wire:click="assign" icon="user-plus">Affecter</flux:button>
                        </div>
                    @endcan

                    <div>
                        <flux:heading size="sm">Spécifications techniques</flux:heading>
                        <ul class="mt-2 space-y-2">
                            @foreach ($job->order->items as $item)
                                <li class="rounded-lg bg-zinc-50 p-2.5 text-sm dark:bg-zinc-800" wire:key="spec-{{ $item->id }}">
                                    <div class="font-medium">{{ (float) $item->quantity }} × {{ $item->description }}</div>
                                    <div class="text-zinc-500">{{ $item->specSummary() ?: '—' }}</div>
                                </li>
                            @endforeach
                        </ul>
                        @if ($job->order->technical_notes)
                            <flux:callout class="mt-2" icon="information-circle" :text="$job->order->technical_notes" />
                        @endif
                        @if ($job->order->files->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($job->order->files as $file)
                                    <flux:button size="xs" icon="arrow-down-tray" :href="route('files.order', $file)" wire:key="of-{{ $file->id }}">{{ $file->original_name }}</flux:button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <flux:heading size="sm">Versions & propositions</flux:heading>
                        <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($job->versions as $version)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm" wire:key="v-{{ $version->id }}">
                                    <div>
                                        <span class="font-medium">v{{ $version->version }}</span> · {{ $version->original_name }}
                                        <div class="text-xs text-zinc-500">{{ $version->created_at->format('d/m H:i') }} · {{ $version->uploader?->name }} {{ $version->comment ? '· '.$version->comment : '' }}</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:button size="xs" icon="eye" :href="route('files.design', $version)" target="_blank">Voir</flux:button>
                                        @if ($job->status->value === 'proposal' && $loop->first)
                                            @can('proofs.create')<flux:button size="xs" variant="primary" icon="document-check" wire:click="generateProof({{ $version->id }})">Générer le BAT</flux:button>@endcan
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="py-2 text-sm text-zinc-500">Aucune version déposée.</li>
                            @endforelse
                        </ul>
                        @if (in_array($job->status->value, ['in_progress', 'correction', 'proposal']))
                            @can('design.work')
                                <form wire:submit="uploadVersion" class="mt-3 space-y-2 rounded-lg border border-dashed border-zinc-300 p-3 dark:border-zinc-700">
                                    <input type="file" wire:model="file" class="text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 dark:file:bg-zinc-800" />
                                    <div wire:loading wire:target="file" class="text-xs text-zinc-500">Téléversement…</div>
                                    <flux:input wire:model="versionComment" size="sm" placeholder="Commentaire sur cette version" />
                                    <flux:error name="file" />
                                    <flux:button type="submit" size="sm" icon="arrow-up-tray">Déposer la proposition</flux:button>
                                </form>
                            @endcan
                        @endif
                    </div>

                    <div class="space-y-2">
                        <flux:heading size="sm">Échanges</flux:heading>
                        <flux:textarea wire:model="note" rows="2" placeholder="Question au commercial ou demande de correction…" />
                        <flux:error name="note" />
                        <div class="flex gap-2">
                            <flux:button size="sm" icon="question-mark-circle" wire:click="askInformation">Demander des informations</flux:button>
                            @if (in_array($job->status->value, ['proposal', 'proof']))
                                <flux:button size="sm" icon="arrow-uturn-left" wire:click="requestCorrection">Demander une correction</flux:button>
                            @endif
                        </div>
                        <ul class="space-y-2 pt-2">
                            @foreach ($job->order->comments->take(8) as $comment)
                                <li class="rounded-lg bg-zinc-50 p-2 text-sm dark:bg-zinc-800" wire:key="cm-{{ $comment->id }}">
                                    <div class="text-xs text-zinc-500">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>
                                    <div class="whitespace-pre-line">{{ $comment->body }}</div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <flux:modal name="block" class="w-full max-w-lg">
                    <div class="space-y-4">
                        <flux:heading size="lg">Signaler une impossibilité</flux:heading>
                        <flux:text>Le motif et un rapport sont obligatoires. « Annuler mon intervention » remet le travail dans la file des nouvelles commandes.</flux:text>
                        <flux:input wire:model="reason" label="Motif" required />
                        <flux:textarea wire:model="report" label="Rapport" rows="4" required />
                        <div class="flex justify-end gap-2">
                            <flux:button wire:click="withdrawIntervention" variant="ghost">Annuler mon intervention</flux:button>
                            <flux:button wire:click="block" variant="danger">Signaler l’impossibilité</flux:button>
                        </div>
                    </div>
                </flux:modal>
            @else
                <div class="panel"><x-empty-state icon="cursor-arrow-rays" title="Sélectionnez un travail" text="Les spécifications, fichiers et versions s’affichent ici." /></div>
            @endif
        </div>
    </div>
</div>
