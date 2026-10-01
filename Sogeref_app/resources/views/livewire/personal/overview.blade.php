<div>
    @php
        $hour = (int) now()->format('H');
        $hello = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
        $done = $tasks->where('is_done', true)->count();
        $progress = $tasks->count() ? (int) round($done / $tasks->count() * 100) : 0;
    @endphp

    <x-ui.page-header :title="$hello.', '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" :eyebrow="'Espace personnel · '.ucfirst(now()->translatedFormat('l j F Y'))"
        subtitle="Votre journée en un coup d’œil : tâches, congés, bons à signer et notes.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="pencil-square" :href="route('personal.notes', ['nouvelle' => 1])">Nouvelle note</x-ui.button>
            <x-ui.button variant="accent" icon="ticket" :href="route('personal.vouchers', ['nouveau' => 1])">Émettre un bon</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>


    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Tâches du jour" :value="$done.' / '.$tasks->count()" icon="check-circle" tone="brand" :href="route('personal.tasks')" :hint="$overdue ? $overdue.' en retard à reporter' : 'Aucun retard'" />
        <x-ui.stat label="Solde de congés" :value="$employee ? qty($employee->leave_balance).' j' : '—'" icon="sun" tone="lagoon" :href="route('personal.leaves')" :hint="$employee ? $myLeaves->where('status', 'pending')->count().' demande(s) en attente' : 'Aucune fiche employé liée'" />
        <x-ui.stat label="Bons à signer" :value="$toSign !== null ? $toSignCount : $myVouchers->where('status', 'submitted')->count()" icon="ticket" tone="sun" :href="route('personal.vouchers')" :hint="$toSign !== null ? 'En attente de votre approbation' : 'Vos bons en attente de signature'" />
        <x-ui.stat label="Notes" :value="$notesCount" icon="pencil-square" tone="emerald" :href="route('personal.notes')" hint="Privées, visibles par vous seul" />
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        {{-- ═══ TÂCHES DU JOUR ═══════════════════════════════════════════════ --}}
        <x-ui.card class="xl:col-span-2" title="Mes tâches du jour" :subtitle="$tasks->count() ? $progress.' % accompli' : 'Planifiez votre journée'" icon="check-circle" :padded="false">
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" iconRight="arrow-right" :href="route('personal.tasks')">Tout voir</x-ui.button>
            </x-slot:actions>
            @if ($tasks->count())
                <div class="h-1 bg-ink-100 dark:bg-white/6"><div class="h-1 bg-emerald-500 transition-all" style="width: {{ $progress }}%"></div></div>
            @endif
            <ul class="divide-y divide-line">
                @forelse ($tasks->take(8) as $task)
                    <li wire:key="ov-task-{{ $task->id }}" class="flex items-center gap-3 px-5 py-2.5">
                        <button type="button" wire:click="toggleTask({{ $task->id }})" @class(['grid size-5 shrink-0 place-items-center rounded-md border-2 transition', 'border-emerald-500 bg-emerald-500 text-white' => $task->is_done, 'border-line-strong hover:border-brand-400' => ! $task->is_done]) aria-label="{{ $task->is_done ? 'Marquer à faire' : 'Marquer terminée' }}">
                            @if ($task->is_done)<x-ui.icon name="check" class="size-3.5" />@endif
                        </button>
                        <span @class(['min-w-0 flex-1 truncate', 'text-fg-subtle line-through' => $task->is_done])>{{ $task->title }}</span>
                        @if ($task->timeSlot())<span class="hidden text-[12px] text-fg-subtle tabular-nums sm:inline">{{ $task->timeSlot() }}</span>@endif
                        @if ($task->priority !== 'normale')<x-ui.status map="priority" :value="$task->priority" />@endif
                    </li>
                @empty
                    <li><x-ui.empty icon="check-circle" title="Aucune tâche aujourd’hui" text="Ajoutez votre première tâche ci-dessous." /></li>
                @endforelse
            </ul>
            <form wire:submit="addTask" class="flex gap-2 border-t border-line p-3">
                <input type="text" wire:model="quickTask" class="input" placeholder="Ajouter une tâche pour aujourd’hui…" maxlength="255">
                <x-ui.button type="submit" variant="primary" icon="plus" square aria-label="Ajouter" />
            </form>
            @error('quickTask')<p class="field-error px-4 pb-3">{{ $message }}</p>@enderror
        </x-ui.card>

        {{-- ═══ CONGÉS ═══════════════════════════════════════════════════════ --}}
        <x-ui.card title="Mes congés" icon="sun" :padded="false">
            <x-slot:actions><x-ui.button variant="ghost" size="sm" iconRight="arrow-right" :href="route('personal.leaves')">Gérer</x-ui.button></x-slot:actions>
            @if (! $employee)
                <x-ui.empty icon="identification" title="Aucune fiche employé" text="Votre compte n’est lié à aucune fiche employé. Rapprochez-vous des RH." />
            @else
                <ul class="divide-y divide-line">
                    @forelse ($myLeaves as $leave)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $leave->type->color }}"></span>
                            <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $leave->type->name }}</p><p class="text-[12px] text-fg-subtle">{{ $leave->start_date->format('d/m') }} → {{ $leave->end_date->format('d/m/Y') }} · {{ qty($leave->days) }} j</p></div>
                            <x-ui.status map="leave" :value="$leave->status" />
                        </li>
                    @empty
                        <li><x-ui.empty icon="sun" title="Aucun congé à venir" text="Solde disponible : {{ qty($employee->leave_balance) }} jours." /></li>
                    @endforelse
                </ul>
            @endif
            @if ($leavesToApprove)
                <a href="{{ route('leaves') }}" wire:navigate class="flex items-center justify-between gap-2 border-t border-line bg-sun-50 px-5 py-3 text-[13px] font-semibold text-sun-800 dark:bg-sun-400/10 dark:text-sun-300">
                    <span>{{ $leavesToApprove }} demande(s) de congé à valider</span><x-ui.icon name="arrow-right" class="size-4" />
                </a>
            @endif
        </x-ui.card>

        {{-- ═══ BONS ═════════════════════════════════════════════════════════ --}}
        <x-ui.card :title="$toSign !== null ? 'Bons en attente de signature' : 'Mes bons en circuit'" icon="ticket" :padded="false">
            <x-slot:actions>
                @if ($toSign !== null)
                    <x-ui.button variant="ghost" size="sm" iconRight="arrow-right" :href="route('vouchers', ['statut' => 'submitted'])">Signer</x-ui.button>
                @else
                    <x-ui.button variant="ghost" size="sm" iconRight="arrow-right" :href="route('personal.vouchers')">Mes bons</x-ui.button>
                @endif
            </x-slot:actions>
            <ul class="divide-y divide-line">
                @forelse (($toSign ?? $myVouchers) as $v)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-sun-100 text-sun-800 dark:bg-sun-400/15 dark:text-sun-300"><x-ui.icon :name="config('erp.voucher_types.'.$v->type.'.icon', 'ticket')" class="size-[18px]" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $v->number }} · {{ $v->purpose }}</p>
                            <p class="text-[12px] text-fg-subtle">{{ $v->voucher_date->format('d/m/Y') }}@if ($toSign !== null) · {{ $v->requester?->name }}@endif · {{ money($v->amount, $v->currency_code) }}</p>
                        </div>
                        <x-ui.status map="voucher" :value="$v->status" />
                    </li>
                @empty
                    <li><x-ui.empty icon="ticket" title="Aucun bon en attente" /></li>
                @endforelse
            </ul>
        </x-ui.card>

        {{-- ═══ NOTES RÉCENTES ═══════════════════════════════════════════════ --}}
        <x-ui.card class="xl:col-span-2" title="Notes récentes" icon="pencil-square">
            <x-slot:actions><x-ui.button variant="ghost" size="sm" iconRight="arrow-right" :href="route('personal.notes')">Toutes les notes</x-ui.button></x-slot:actions>
            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($notes as $note)
                    <a href="{{ route('personal.notes', ['note' => $note->getRouteKey()]) }}" wire:navigate class="group rounded-xl border border-line bg-surface-2 p-4 transition hover:border-brand-200 hover:shadow-sm">
                        <p class="flex items-center gap-1.5 truncate font-semibold text-fg">@if ($note->is_pinned)<x-ui.icon name="bookmark" variant="s" class="size-4 text-sun-500" />@endif{{ $note->title }}</p>
                        <p class="mt-1 line-clamp-2 text-[13px] text-fg-muted">{{ $note->excerpt() ?: 'Note vide' }}</p>
                        <p class="mt-2 text-[11.5px] text-fg-subtle">Modifiée {{ $note->updated_at->diffForHumans() }}</p>
                    </a>
                @empty
                    <div class="sm:col-span-2"><x-ui.empty icon="pencil-square" title="Aucune note" text="Prenez des notes de réunion, des listes de contrôle ou des idées."><x-ui.button variant="primary" icon="plus" :href="route('personal.notes', ['nouvelle' => 1])">Créer une note</x-ui.button></x-ui.empty></div>
                @endforelse
            </div>
            @if ($sheets->count())
                <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                    @foreach ($sheets as $sheet)
                        <a href="{{ route('personal.sheets', ['classeur' => $sheet->getRouteKey()]) }}" wire:navigate class="btn btn-secondary btn-sm"><x-ui.icon name="table-cells" class="size-4 text-emerald-600" />{{ $sheet->title }}</a>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>
</div>
