<div>
    @php $canManage = auth()->user()->canManage('planning'); @endphp
    <x-ui.page-header title="Planification" eyebrow="Services" subtitle="Affectation hebdomadaire des équipes aux chantiers, à l’atelier et aux projets. Touchez une case pour affecter.">
        <x-slot:actions>
            <select wire:model.live="departmentId" class="input w-56"><option value="">Tous les départements</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
            <x-ui.button variant="secondary" square icon="chevron-left" wire:click="shift(-1)" />
            <x-ui.button variant="secondary" wire:click="$set('week', '{{ now()->startOfWeek()->toDateString() }}')">Cette semaine</x-ui.button>
            <x-ui.button variant="secondary" square icon="chevron-right" wire:click="shift(1)" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="w-full min-w-[1000px] border-collapse text-[12.5px]">
                <thead>
                    <tr class="bg-surface-2">
                        <th class="sticky left-0 z-10 w-60 bg-surface-2 px-4 py-3 text-left text-[11px] font-semibold tracking-wider text-fg-subtle uppercase">Semaine du {{ $days->first()->translatedFormat('j M') }}</th>
                        @foreach ($days as $d)
                            <th @class(['px-2 py-3 text-center font-semibold', 'text-sun-700 dark:text-sun-300' => $d->isToday()])>
                                <span class="block text-[11px] tracking-wider uppercase">{{ $d->translatedFormat('l') }}</span>
                                <span class="block text-[15px]">{{ $d->format('d') }}</span>
                                <span class="block text-[11px] font-normal text-fg-subtle">{{ $load[$d->toDateString()] }} affecté(s)</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $e)
                        <tr class="border-t border-line">
                            <td class="sticky left-0 z-10 bg-surface px-4 py-2"><p class="font-semibold whitespace-nowrap">{{ $e->full_name }}</p><p class="text-[11.5px] text-fg-subtle">{{ $e->position?->title }} @if ($e->isDaily())· journalier @endif</p></td>
                            @foreach ($days as $d)
                                @php $cell = $assignments->get($e->id.'|'.$d->toDateString(), collect()); @endphp
                                <td class="h-16 border-l border-line/60 p-1 align-top">
                                    @foreach ($cell as $s)
                                        <button type="button" @if ($canManage) wire:click="openSlot({{ $e->id }}, '{{ $d->toDateString() }}', {{ $s->id }})" @endif
                                                @class(['mb-1 block w-full truncate rounded-md px-2 py-1 text-left text-[11.5px] font-semibold', 'bg-sun-100 text-sun-800 dark:bg-sun-400/15 dark:text-sun-300' => $s->site_id, 'bg-lagoon-50 text-lagoon-700 dark:bg-lagoon-400/12 dark:text-lagoon-300' => $s->manufacturing_order_id, 'bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-brand-100' => $s->project_id])
                                                title="{{ $s->assignmentLabel() }}">
                                            {{ $s->shift !== 'journee' ? (\App\Models\PlanningSlot::SHIFTS[$s->shift].' · ') : '' }}{{ $s->site?->code ?? $s->manufacturingOrder?->number ?? $s->project?->code }}
                                        </button>
                                    @endforeach
                                    @if ($canManage && $cell->isEmpty())
                                        <button type="button" wire:click="openSlot({{ $e->id }}, '{{ $d->toDateString() }}')" class="grid size-full min-h-12 place-items-center rounded-md text-fg-subtle opacity-0 transition hover:bg-surface-2 hover:opacity-100 focus:opacity-100 [@media(pointer:coarse)]:opacity-40"><x-ui.icon name="plus" class="size-4" /></button>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-4 border-t border-line px-4 py-3 text-[12px] text-fg-muted">
            <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-sun-100 ring-1 ring-sun-300"></span>Chantier</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-lagoon-50 ring-1 ring-lagoon-300"></span>Atelier</span>
            <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand-50 ring-1 ring-brand-200"></span>Projet</span>
        </div>
    </div>

    <x-ui.drawer wire:model="showSlot" :title="$editing ? 'Modifier l’affectation' : 'Nouvelle affectation'" :subtitle="isset($assignment['slot_date']) ? \Illuminate\Support\Carbon::parse($assignment['slot_date'])->translatedFormat('l j F') : null" icon="calendar-days">
        <div class="space-y-4">
            <x-ui.select label="Affectation" wire:model="assignment.target" placeholder="Choisir…" :options="$targets" required />
            <x-ui.select label="Créneau" wire:model="assignment.shift" :options="\App\Models\PlanningSlot::SHIFTS" />
            <x-ui.input label="Rôle" wire:model="assignment.role" placeholder="Ex. : Chef d’équipe, coffreur…" />
            <x-ui.input label="Note" wire:model="assignment.notes" />
            @unless ($editing)<x-ui.toggle wire:model="assignment.repeat" label="Répéter jusqu’à samedi" description="Crée la même affectation pour les jours suivants de la semaine." />@endunless
        </div>
        <x-slot:footer>
            @if ($editing)<x-ui.button variant="ghost" icon="trash" class="mr-auto text-flame-600" wire:click="deleteSlot">Retirer</x-ui.button>@endif
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveSlot">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
