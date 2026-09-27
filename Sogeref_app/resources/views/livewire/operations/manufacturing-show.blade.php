<div>
    @php $canManage = auth()->user()->canManage('manufacturing') && ! $locked; @endphp
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$mo->title" :eyebrow="$mo->number.' · '.($mo->type === 'construction' ? 'Dossier de construction' : 'Ordre de fabrication')" :back="route('manufacturing')">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[13px] text-fg-muted">
                <x-ui.status map="manufacturing" :value="$mo->status" />
                <x-ui.status map="priority" :value="$mo->priority" />
                @if ($mo->isDelayed())<x-ui.badge tone="danger" icon="exclamation-triangle">En retard</x-ui.badge>@endif
                @if ($mo->salesOrder)<a href="{{ route('orders.show', $mo->salesOrder) }}" wire:navigate class="flex items-center gap-1 hover:text-fg"><x-ui.icon name="arrow-path-rounded-square" class="size-4" /> {{ $mo->salesOrder->number }} · {{ $mo->salesOrder->contact?->display_name }}</a>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="printer" :href="route('pdf.manufacturing', $mo)" :navigate="false" target="_blank">{{ $mo->type === 'construction' ? 'Dossier de construction' : 'Fiche de fabrication' }}</x-ui.button>
            @if ($canManage)
                @if (in_array($mo->status, ['draft', 'confirmed']))<x-ui.button variant="primary" icon="play" wire:click="start">Démarrer</x-ui.button>@endif
                @if ($mo->status === 'in_progress')<x-ui.button variant="secondary" icon="shield-check" wire:click="toQuality">Contrôle qualité</x-ui.button>@endif
                @if (in_array($mo->status, ['in_progress', 'quality']))<x-ui.button variant="success" icon="check-circle" wire:click="finish" wire:confirm="Déclarer la fabrication terminée ?">Terminer</x-ui.button>@endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="min-w-0 space-y-6">
            {{-- ═══ 2. AVANCEMENT ════════════════════════════════════════════ --}}
            <div class="card p-5">
                <x-ui.stepper :steps="collect(\App\Models\ManufacturingOrder::FLOW)->mapWithKeys(fn ($s) => [$s => erp_status('manufacturing', $s)[0]])->all()" :current="$mo->status" :cancelled="$mo->status === 'cancelled'" />
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div><p class="text-[12px] text-fg-subtle">Avancement</p><p class="font-display text-[26px] font-semibold">{{ $mo->progress }} %</p><x-ui.progress :value="$mo->progress" tone="sun" /></div>
                    <div><p class="text-[12px] text-fg-subtle">Coût matières estimé</p><p class="font-display text-[26px] font-semibold tabular-nums">{{ money($mo->materialCost()) }}</p><p class="text-[12px] text-fg-subtle">{{ $mo->materials->count() }} matière(s)</p></div>
                    <div><p class="text-[12px] text-fg-subtle">Main-d’œuvre prévue</p><p class="font-display text-[26px] font-semibold tabular-nums">{{ qty($labourHours) }} h</p><p class="text-[12px] text-fg-subtle">{{ $mo->operations->where('status', 'done')->count() }}/{{ $mo->operations->count() }} opérations terminées</p></div>
                </div>
            </div>

            {{-- ═══ 3. ONGLETS ═══════════════════════════════════════════════ --}}
            <div class="card">
                <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['materiel' => ['label' => 'Liste de matériel', 'icon' => 'cube', 'count' => $mo->materials->count()], 'operations' => ['label' => 'Gamme opératoire', 'icon' => 'queue-list', 'count' => $mo->operations->count()], 'specifications' => ['label' => 'Spécifications', 'icon' => 'document-text'], 'bons' => ['label' => 'Bons de sortie', 'icon' => 'ticket', 'count' => $mo->vouchers->count()]]" /></div>

                @if ($tab === 'materiel')
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-3">
                        <p class="text-[13px] text-fg-muted">Quantités issues des nomenclatures / du métré. Le disponible cumule tous les entrepôts.</p>
                        @if ($canManage)<x-ui.button variant="soft" size="sm" icon="arrow-up-tray" wire:click="requestMaterials">Demander la sortie magasin</x-ui.button>@endif
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Matière / fourniture</th><th class="num w-32">Prévu</th><th class="num">Sorti</th><th class="num">Disponible</th><th class="num">Coût</th><th class="w-10"></th></tr></thead>
                            <tbody>
                                @forelse ($mo->materials as $m)
                                    @php
                                        $available = (float) ($stock[$m->product_id] ?? 0);
                                        $missing = (float) $m->quantity_planned - (float) $m->quantity_consumed > $available;
                                    @endphp
                                    <tr wire:key="mat-{{ $m->id }}">
                                        <td><p class="font-medium">{{ $m->description }}</p><p class="text-[12px] text-fg-subtle">{{ $m->product?->sku }}</p></td>
                                        <td class="num">
                                            @if ($canManage)
                                                <input type="number" step="0.01" value="{{ (float) $m->quantity_planned }}" wire:change="updateMaterial({{ $m->id }}, $event.target.value)" class="input h-9 text-right">
                                            @else {{ qty($m->quantity_planned) }} @endif
                                            <span class="text-[11px] text-fg-subtle">{{ $m->unit }}</span>
                                        </td>
                                        <td class="num"><span @class(['font-semibold', 'text-emerald-600' => (float) $m->quantity_consumed >= (float) $m->quantity_planned])>{{ qty($m->quantity_consumed) }}</span></td>
                                        <td class="num"><span @class(['font-semibold', 'text-flame-600' => $missing])>{{ qty($available) }}</span>@if ($missing)<p class="text-[11px] text-flame-600">à commander</p>@endif</td>
                                        <td class="num">{{ money((float) $m->quantity_planned * (float) $m->unit_cost) }}</td>
                                        <td>@if ($canManage)<button type="button" wire:click="removeMaterial({{ $m->id }})" class="btn btn-ghost btn-sm btn-icon text-fg-subtle hover:text-flame-600" aria-label="Retirer"><x-ui.icon name="trash" class="size-4" /></button>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><x-ui.empty icon="cube" title="Aucune matière" text="Ajoutez les matières nécessaires ci-dessous." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($canManage)
                        <div class="flex flex-col gap-2 border-t border-line p-3 sm:flex-row">
                            <select wire:model="newProductId" class="input flex-1"><option value="">Ajouter une matière…</option>@foreach ($materialsCatalog as $p)<option value="{{ $p->id }}">{{ $p->sku }} · {{ $p->name }} ({{ $p->unit }})</option>@endforeach</select>
                            <input type="number" step="0.01" wire:model="newQuantity" class="input sm:w-28" placeholder="Qté">
                            <x-ui.button variant="secondary" icon="plus" wire:click="addMaterial">Ajouter</x-ui.button>
                        </div>
                    @endif

                @elseif ($tab === 'operations')
                    <ul>
                        @foreach ($mo->operations as $op)
                            <li class="flex flex-col gap-3 border-b border-line px-4 py-3.5 sm:flex-row sm:items-center" wire:key="op-{{ $op->id }}">
                                <button type="button" @if ($canManage) wire:click="cycleOperation({{ $op->id }})" @endif title="Changer l’état"
                                        @class(['grid size-9 shrink-0 place-items-center rounded-full border-2 transition', 'border-line-strong text-fg-subtle' => $op->status === 'todo', 'border-sun-400 bg-sun-100 text-sun-800' => $op->status === 'in_progress', 'border-emerald-500 bg-emerald-500 text-white' => $op->status === 'done'])>
                                    @if ($op->status === 'done')<x-ui.icon name="check" variant="s" class="size-4" />@elseif ($op->status === 'in_progress')<x-ui.icon name="play" variant="s" class="size-4" />@else<span class="text-[12px] font-bold">{{ $loop->iteration }}</span>@endif
                                </button>
                                <div class="min-w-0 flex-1">
                                    <p @class(['font-semibold', 'line-through text-fg-subtle' => $op->status === 'done'])>{{ $op->name }}</p>
                                    <p class="text-[12px] text-fg-subtle">{{ $op->workstation ?? 'Poste libre' }} · {{ qty($op->planned_hours) }} h prévues @if ((float) $op->actual_hours) · {{ qty($op->actual_hours) }} h réelles @endif</p>
                                </div>
                                @if ($canManage)
                                    <select wire:change="assignOperation({{ $op->id }}, $event.target.value)" class="input h-9 sm:w-52">
                                        <option value="">Opérateur…</option>
                                        @foreach ($employees as $e)<option value="{{ $e->id }}" @selected($op->employee_id === $e->id)>{{ $e->full_name }}</option>@endforeach
                                    </select>
                                @elseif ($op->employee)
                                    <span class="text-[13px]">{{ $op->employee->full_name }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($canManage)
                        <div class="flex flex-col gap-2 p-3 sm:flex-row">
                            <input wire:model="newOperation" class="input flex-1" placeholder="Nouvelle opération (ex. : Laquage des pieds)">
                            <input type="number" step="0.5" wire:model="newOperationHours" class="input sm:w-24" placeholder="Heures">
                            <x-ui.button variant="secondary" icon="plus" wire:click="addOperation">Ajouter</x-ui.button>
                        </div>
                    @endif

                @elseif ($tab === 'specifications')
                    <div class="space-y-4 p-4">
                        <x-ui.textarea label="Description / cahier des charges" wire:model="description" rows="3" :disabled="! $canManage" />
                        <div>
                            <p class="label">Caractéristiques techniques</p>
                            <div class="space-y-2">
                                @foreach ($specs as $i => $spec)
                                    <div class="flex gap-2" wire:key="spec-{{ $i }}">
                                        <input wire:model="specs.{{ $i }}.label" class="input" placeholder="Élément (ex. : Dimensions)" @disabled(! $canManage)>
                                        <input wire:model="specs.{{ $i }}.value" class="input" placeholder="Valeur (ex. : 0,62 × 0,43 × 1,01 m)" @disabled(! $canManage)>
                                        @if ($canManage)<button type="button" wire:click="removeSpec({{ $i }})" class="btn btn-ghost btn-icon text-fg-subtle"><x-ui.icon name="x-mark" /></button>@endif
                                    </div>
                                @endforeach
                            </div>
                            @if ($canManage)<x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addSpec" class="mt-2">Ajouter une caractéristique</x-ui.button>@endif
                        </div>
                        @if ($canManage)<div class="flex justify-end"><x-ui.button variant="primary" icon="check" wire:click="saveDetails">Enregistrer</x-ui.button></div>@endif
                    </div>

                @else
                    <ul>
                        @forelse ($mo->vouchers as $v)
                            <li class="flex items-center gap-3 border-b border-line px-4 py-3 last:border-0">
                                <x-ui.icon name="ticket" class="text-fg-subtle" />
                                <div class="min-w-0 flex-1"><p class="font-semibold">{{ $v->number }}</p><p class="truncate text-[12px] text-fg-subtle">{{ $v->purpose }}</p></div>
                                <x-ui.status map="voucher" :value="$v->status" />
                                <a href="{{ route('pdf.voucher', $v) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon"><x-ui.icon name="printer" class="size-4" /></a>
                            </li>
                        @empty
                            <li><x-ui.empty icon="ticket" title="Aucun bon de sortie" text="Utilisez « Demander la sortie magasin » depuis la liste de matériel." /></li>
                        @endforelse
                    </ul>
                @endif
            </div>
        </div>

        {{-- ═══ 4. COLONNE LATÉRALE ══════════════════════════════════════════ --}}
        <aside class="space-y-6">
            <x-ui.card title="Planification" icon="calendar-days">
                <div class="space-y-3">
                    <x-ui.select label="Responsable" wire:model="responsibleId" placeholder="À désigner" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name])" :disabled="! $canManage" />
                    <x-ui.select label="Lieu de production" wire:model="warehouseId" placeholder="—" :options="$warehouses->pluck('name', 'id')" :disabled="! $canManage" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input label="Début" type="date" wire:model="plannedStart" :disabled="! $canManage" />
                        <x-ui.input label="Fin prévue" type="date" wire:model="plannedEnd" :disabled="! $canManage" />
                    </div>
                    <div>
                        <label class="label">Avancement : {{ $progress }} %</label>
                        <input type="range" min="0" max="100" step="5" wire:model.live="progress" class="w-full accent-brand-600" @disabled(! $canManage)>
                    </div>
                    @if ($canManage)<x-ui.button variant="primary" icon="check" class="w-full" wire:click="saveDetails">Enregistrer</x-ui.button>@endif
                </div>
            </x-ui.card>
            <x-ui.card title="Informations" icon="information-circle">
                <dl class="space-y-2 text-[13px]">
                    <div class="flex justify-between gap-2"><dt class="text-fg-muted">Quantité</dt><dd class="font-semibold">{{ qty($mo->quantity) }} {{ $mo->product?->unit ?? 'ouvrage' }}</dd></div>
                    @if ($mo->product)<div class="flex justify-between gap-2"><dt class="text-fg-muted">Produit</dt><dd class="text-right font-medium">{{ $mo->product->sku }}</dd></div>@endif
                    <div class="flex justify-between gap-2"><dt class="text-fg-muted">Démarré le</dt><dd>{{ $mo->started_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-fg-muted">Terminé le</dt><dd>{{ $mo->finished_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                </dl>
                @if ($canManage)
                    <x-ui.button variant="ghost" size="sm" icon="x-circle" class="mt-4 w-full text-flame-600" wire:click="cancel" wire:confirm="Annuler cet ordre de fabrication ?">Annuler l’ordre</x-ui.button>
                @endif
            </x-ui.card>
        </aside>
    </div>
</div>
