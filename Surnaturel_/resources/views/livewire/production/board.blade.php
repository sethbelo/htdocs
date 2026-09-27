<div class="space-y-6" wire:poll.60s>
    <x-page-header title="Salle d’impression" subtitle="File d’attente → Préparation → Impression → Finition → Contrôle qualité → Prête à livrer" />

    <div class="flex gap-4 overflow-x-auto pb-2">
        @foreach (\App\Enums\PrintJobStatus::cases() as $status)
            <div class="w-72 shrink-0 space-y-2" wire:key="col-{{ $status->value }}">
                <div class="flex items-center justify-between px-1">
                    <flux:heading size="sm">{{ $status->label() }}</flux:heading>
                    <flux:badge size="sm" :color="$status->color()">{{ $this->columns[$status->value]->count() }}</flux:badge>
                </div>
                <div class="min-h-24 space-y-2 rounded-xl bg-zinc-100 p-2 dark:bg-zinc-800/50">
                    @foreach ($this->columns[$status->value] as $job)
                        <button wire:click="open({{ $job->id }})" wire:key="pj-{{ $job->id }}" class="panel block w-full p-3 text-left text-sm transition hover:border-indigo-300">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $job->order->number }}</span>
                                @if (in_array($job->order->priority->value, ['urgent', 'high']))<flux:badge size="sm" :color="$job->order->priority->color()">{{ $job->order->priority->label() }}</flux:badge>@endif
                            </div>
                            <div class="mt-1 line-clamp-2 text-zinc-600 dark:text-zinc-300">{{ $job->orderItem?->description }}</div>
                            <div class="mt-1 text-xs text-zinc-500">{{ $job->planned_quantity }} ex. · {{ $job->orderItem?->format }} {{ $job->orderItem?->paper }}</div>
                            <div class="mt-1 flex justify-between text-xs text-zinc-500">
                                <span>{{ $job->machine?->code ?? 'Machine ?' }}</span>
                                <span @class(['font-medium text-red-600' => $job->order->isOverdue()])>{{ $job->order->due_at?->format('d/m H:i') }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="job" class="w-full max-w-3xl">
        @if ($this->job)
            @php($job = $this->job)
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <flux:heading size="lg">{{ $job->number }} · {{ $job->order->number }}</flux:heading>
                        <flux:text>{{ $job->order->client->displayName() }} · délai {{ $job->order->due_at?->translatedFormat('d M H:i') ?? '—' }}</flux:text>
                    </div>
                    <x-status-badge :status="$job->status" size="lg" />
                </div>

                <div class="grid gap-3 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800 md:grid-cols-2">
                    <div><span class="text-zinc-500">Produit :</span> {{ $job->orderItem?->description }}</div>
                    <div><span class="text-zinc-500">Quantité prévue :</span> {{ $job->planned_quantity }}</div>
                    <div class="md:col-span-2"><span class="text-zinc-500">Spécifications :</span> {{ $job->orderItem?->specSummary() }}</div>
                    <div><span class="text-zinc-500">Graphiste :</span> {{ $job->proof?->designJob?->designer?->name ?? '—' }}</div>
                    <div><span class="text-zinc-500">Opérateur :</span> {{ $job->operator?->name ?? '—' }}</div>
                    <div><span class="text-zinc-500">Début :</span> {{ $job->started_at?->format('d/m H:i') ?? '—' }}</div>
                    <div><span class="text-zinc-500">Fin :</span> {{ $job->finished_at?->format('d/m H:i') ?? '—' }}</div>
                </div>

                @if ($job->proof)
                    <div class="flex items-center gap-2 text-sm">
                        <flux:badge color="emerald" icon="lock-closed">BAT {{ $job->proof->number }} · v{{ $job->proof->version->version }}</flux:badge>
                        <flux:button size="xs" icon="arrow-down-tray" :href="route('files.proof', $job->proof)">Fichier BAT verrouillé</flux:button>
                    </div>
                @elseif ($job->order->requires_design)
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="Aucun BAT validé rattaché à ce travail." />
                @endif

                @can('production.update')
                    @if (in_array($job->status->value, ['queued', 'rework']))
                        <div class="flex items-end gap-2">
                            <flux:select wire:model="machineId" label="Machine" class="flex-1">
                                <flux:select.option value="">Choisir…</flux:select.option>
                                @foreach ($this->machines as $machine)
                                    <flux:select.option :value="$machine->id" :disabled="$machine->status !== $operationalStatus">{{ $machine->code }} — {{ $machine->name }} ({{ $machine->status->label() }})</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button variant="primary" icon="play" wire:click="start">Démarrer</flux:button>
                        </div>
                    @elseif (in_array($job->status->value, ['preparing', 'printing', 'finishing']))
                        <flux:button variant="primary" icon="forward" wire:click="advance">
                            Passer à « {{ ['preparing' => 'Impression', 'printing' => 'Finition', 'finishing' => 'Contrôle qualité'][$job->status->value] }} »
                        </flux:button>
                    @endif

                    @if (! in_array($job->status->value, ['queued', 'ready']))
                        <form wire:submit="saveOutput" class="grid gap-3 md:grid-cols-4">
                            <flux:input wire:model="produced" type="number" label="Quantité produite" />
                            <flux:input wire:model="waste" type="number" label="Pertes / déchets" />
                            <flux:input wire:model="observations" label="Observations" class="md:col-span-2" />
                            <div class="md:col-span-4"><flux:button type="submit" size="sm" icon="check">Enregistrer la production</flux:button></div>
                        </form>

                        <div class="space-y-2">
                            <flux:heading size="sm">Matières consommées</flux:heading>
                            <ul class="text-sm">
                                @foreach ($job->consumptions as $consumption)
                                    <li wire:key="cons-{{ $consumption->id }}">{{ $consumption->product->name }} : {{ (float) $consumption->quantity }} (+ {{ (float) $consumption->waste_quantity }} perte)</li>
                                @endforeach
                            </ul>
                            <form wire:submit="consume" class="grid gap-2 md:grid-cols-5">
                                <flux:select wire:model="consumption.product_id" class="md:col-span-2" placeholder="Papier, encre…">
                                    <flux:select.option value="">Matière…</flux:select.option>
                                    @foreach ($materials as $material)
                                        <flux:select.option :value="$material->id">{{ $material->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="consumption.warehouse_id">
                                    @foreach ($warehouses as $warehouse)
                                        <flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="consumption.quantity" type="number" step="any" placeholder="Qté" />
                                <flux:input wire:model="consumption.waste" type="number" step="any" placeholder="Perte" />
                                <div class="md:col-span-5"><flux:button type="submit" size="sm" icon="minus-circle">Déstocker</flux:button></div>
                            </form>
                        </div>
                    @endif

                    @if ($job->status->value === 'quality_check')
                        <div class="flex gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:button variant="primary" icon="check-badge" wire:click="quality(true)">Conforme — prête à livrer</flux:button>
                            <flux:button variant="danger" icon="arrow-path" wire:click="quality(false)">Non conforme — à refaire</flux:button>
                        </div>
                    @endif
                @endcan
            </div>
        @endif
    </flux:modal>
</div>
