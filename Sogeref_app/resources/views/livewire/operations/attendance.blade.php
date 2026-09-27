<div>
    {{-- ═══ 1. EN-TÊTE & SÉLECTION ═══════════════════════════════════════════ --}}
    <x-ui.page-header title="Listes de présence" eyebrow="Chantiers" subtitle="Appel quotidien des équipes : base de calcul de la paie des journaliers.">
        <x-slot:actions>
            @if ($sheet)<x-ui.button variant="secondary" icon="printer" :href="route('pdf.attendance', $sheet)" :navigate="false" target="_blank">Imprimer</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card mb-5 flex flex-col gap-3 p-3 md:flex-row md:flex-wrap md:items-center">
        <select wire:model.live="siteId" class="input md:w-96">
            @foreach ($sites as $s)<option value="{{ $s->id }}">{{ $s->code }} · {{ $s->name }}</option>@endforeach
        </select>
        <div class="flex items-center gap-2">
            <x-ui.button variant="secondary" square icon="chevron-left" wire:click="shiftDay(-1)" aria-label="Jour précédent" />
            <input type="date" wire:model.live="date" class="input w-44">
            <x-ui.button variant="secondary" square icon="chevron-right" wire:click="shiftDay(1)" aria-label="Jour suivant" />
            <x-ui.button variant="ghost" size="sm" wire:click="$set('date', '{{ now()->toDateString() }}')">Aujourd’hui</x-ui.button>
        </div>
        <p class="font-display text-[16px] font-semibold md:ml-auto">{{ ucfirst(\Illuminate\Support\Carbon::parse($date)->translatedFormat('l j F Y')) }}</p>
    </div>

    @if (! $sheet)
        <div class="card"><x-ui.empty icon="clipboard-document-check" title="Aucune liste pour cette date" text="Sélectionnez un chantier en cours et une date passée ou du jour." /></div>
    @else
        @php
            $present = $entries->whereIn('status', ['present', 'late', 'half'])->count();
            $statusMap = config('erp.statuses.attendance');
            $short = ['present' => 'P', 'late' => 'R', 'half' => '½', 'absent' => 'A', 'leave' => 'C'];
        @endphp
        {{-- ═══ 2. SYNTHÈSE ══════════════════════════════════════════════════ --}}
        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-ui.stat label="Présents" :value="$present.' / '.$entries->count()" icon="user-group" tone="emerald" />
            <x-ui.stat label="Absents" :value="$entries->where('status', 'absent')->count()" icon="user-minus" tone="flame" />
            <x-ui.stat label="Heures supplémentaires" :value="qty($entries->sum('overtime_hours')).' h'" icon="clock" tone="sun" />
            <x-ui.stat label="Coût de la journée" :value="money($total)" icon="banknotes" tone="lagoon" />
        </div>

        {{-- ═══ 3. APPEL ══════════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-3">
                <div class="flex items-center gap-2">
                    @if ($sheet->status === 'validated')
                        <x-ui.badge tone="success" icon="lock-closed">Validée le {{ $sheet->validated_at?->format('d/m/Y H:i') }} par {{ $sheet->validator?->name }}</x-ui.badge>
                    @else
                        <x-ui.badge tone="warning" dot>Brouillon — à valider par le chef de chantier</x-ui.badge>
                    @endif
                </div>
                @if ($canEdit)
                    <div class="flex gap-2">
                        <x-ui.button variant="secondary" size="sm" icon="check" wire:click="markAll('present')">Tous présents</x-ui.button>
                    </div>
                @endif
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Ouvrier</th><th>Présence</th><th class="w-28">Arrivée</th><th class="w-28">Départ</th><th class="w-24">H. sup</th><th class="num">Montant</th></tr></thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr wire:key="e-{{ $entry->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$entry->employee->full_name" size="sm" />
                                        <div class="min-w-0"><p class="truncate font-semibold">{{ $entry->employee->full_name }}</p><p class="text-[12px] text-fg-subtle">{{ $entry->employee->matricule }} · {{ $entry->employee->position?->title }} · {{ money($entry->daily_rate) }}/j</p></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        @foreach ($short as $status => $letter)
                                            <button type="button" class="presence-btn" data-status="{{ $status }}" aria-pressed="{{ $entry->status === $status ? 'true' : 'false' }}" title="{{ $statusMap[$status][0] }}" @if ($canEdit) wire:click="setStatus({{ $entry->id }}, '{{ $status }}')" @else disabled @endif>{{ $letter }}</button>
                                        @endforeach
                                    </div>
                                </td>
                                <td><input type="time" value="{{ $entry->check_in ? substr($entry->check_in, 0, 5) : '' }}" wire:change="setField({{ $entry->id }}, 'check_in', $event.target.value)" class="input h-10" @disabled(! $canEdit || in_array($entry->status, ['absent', 'leave']))></td>
                                <td><input type="time" value="{{ $entry->check_out ? substr($entry->check_out, 0, 5) : '' }}" wire:change="setField({{ $entry->id }}, 'check_out', $event.target.value)" class="input h-10" @disabled(! $canEdit || in_array($entry->status, ['absent', 'leave']))></td>
                                <td><input type="number" step="0.5" min="0" max="8" value="{{ (float) $entry->overtime_hours }}" wire:change="setField({{ $entry->id }}, 'overtime_hours', $event.target.value)" class="input h-10 text-right" @disabled(! $canEdit)></td>
                                <td class="num font-semibold">{{ money($entry->dayFraction() * (float) $entry->daily_rate) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-line bg-surface-2 p-4 md:flex-row md:items-center">
                <p class="text-[12.5px] text-fg-subtle">P = présent · R = retard · ½ = demi-journée · A = absent · C = congé. Heures sup. majorées de {{ round((config('erp.payroll.overtime_multiplier') - 1) * 100) }} %.</p>
                <div class="flex gap-2 md:ml-auto">
                    @if ($canEdit)
                        <input wire:model="notes" class="input md:w-72" placeholder="Observation (pluie, arrêt, incident…)">
                        <x-ui.button variant="primary" icon="lock-closed" wire:click="validateSheet" wire:confirm="Valider définitivement la liste du {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }} ?">Valider la liste</x-ui.button>
                    @elseif ($sheet->status === 'validated' && (auth()->user()->isMaster() || auth()->user()->can('payroll.validate')))
                        <x-ui.button variant="ghost" icon="lock-open" wire:click="reopen" wire:confirm="Rouvrir cette liste validée ?">Rouvrir</x-ui.button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ 4. SEMAINE ════════════════════════════════════════════════════ --}}
        <x-ui.card title="Vue sur 7 jours" subtitle="Présences des 7 derniers jours" icon="calendar-days" :padded="false" class="mt-6">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Ouvrier</th>@foreach ($week as $day)<th class="text-center">{{ ucfirst($day->translatedFormat('D d')) }}</th>@endforeach<th class="num">Jours</th></tr></thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            @php $days = 0; @endphp
                            <tr>
                                <td class="font-medium whitespace-nowrap">{{ $entry->employee->full_name }}</td>
                                @foreach ($week as $day)
                                    @php
                                        $cell = $weekSheets->get($day->toDateString())?->entries->firstWhere('employee_id', $entry->employee_id);
                                        $days += $cell?->dayFraction() ?? 0;
                                    @endphp
                                    <td class="text-center">
                                        @if ($cell)
                                            <span class="badge tone-{{ $statusMap[$cell->status][1] }} min-w-7 justify-center px-1.5">{{ $short[$cell->status] }}</span>
                                        @else
                                            <span class="text-fg-subtle">·</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="num font-semibold">{{ qty($days) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>
