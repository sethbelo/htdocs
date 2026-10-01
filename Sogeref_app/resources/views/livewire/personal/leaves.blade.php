<div>
    <x-ui.page-header title="Mes congés" eyebrow="Espace personnel" subtitle="Demandez un congé, suivez sa validation par les RH et consultez votre solde (jours ouvrables, dimanches exclus).">
        <x-slot:actions>
            @if ($employee)<x-ui.button variant="accent" icon="plus" wire:click="create">Demander un congé</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>


    @if (! $employee)
        <div class="card">
            <x-ui.empty icon="identification" title="Aucune fiche employé liée à votre compte" text="Les demandes de congé sont rattachées à une fiche employé. Demandez au service RH de lier votre compte utilisateur à votre fiche pour accéder à vos congés." />
        </div>
    @else
        <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
            <x-ui.stat label="Solde disponible" :value="qty($employee->leave_balance).' j'" icon="sun" tone="lagoon" hint="Congé annuel" />
            <x-ui.stat label="En attente de validation" :value="qty($pendingDays).' j'" icon="clock" tone="sun" :hint="$history->where('status', 'pending')->count().' demande(s)'" />
            <x-ui.stat label="Pris cette année" :value="qty($takenThisYear).' j'" icon="check-badge" tone="emerald" />
            <x-ui.stat label="Prochain congé" :value="$upcoming ? $upcoming->start_date->format('d/m') : '—'" icon="calendar-days" tone="brand" :hint="$upcoming ? $upcoming->type->name.' · '.qty($upcoming->days).' j' : 'Aucun congé planifié'" />
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-3">
            <x-ui.card class="xl:col-span-2" title="Historique de mes demandes" icon="sun" :padded="false">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Réf.</th><th>Type</th><th>Période</th><th class="num">Jours</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($history as $r)
                                <tr wire:key="ml-{{ $r->id }}">
                                    <td class="text-fg-muted">{{ $r->reference }}</td>
                                    <td><span class="inline-flex items-center gap-2"><span class="size-2 rounded-full" style="background: {{ $r->type->color }}"></span>{{ $r->type->name }}</span>@if ($r->reason)<p class="max-w-[240px] truncate text-[12px] text-fg-subtle">{{ $r->reason }}</p>@endif</td>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $r->start_date->format('d/m') }} → {{ $r->end_date->format('d/m/Y') }}</td>
                                    <td class="num font-semibold">{{ qty($r->days) }}</td>
                                    <td>
                                        <x-ui.status map="leave" :value="$r->status" />
                                        @if ($r->decision_comment && $r->status !== 'cancelled')<p class="mt-1 max-w-[220px] truncate text-[11.5px] text-fg-subtle" title="{{ $r->decision_comment }}">{{ $r->approver?->name }} : {{ $r->decision_comment }}</p>@endif
                                    </td>
                                    <td class="text-right">
                                        @if ($r->status === 'pending')
                                            <x-ui.button variant="ghost" size="sm" icon="x-mark" wire:click="cancel({{ $r->id }})" wire:confirm="Annuler cette demande de congé ?">Annuler</x-ui.button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="sun" title="Aucune demande" text="Vos demandes de congé apparaîtront ici."><x-ui.button variant="primary" icon="plus" wire:click="create">Demander un congé</x-ui.button></x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="space-y-5">
                <x-ui.card title="Ma fiche" icon="identification">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$employee->full_name" />
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $employee->full_name }}</p><p class="truncate text-[12.5px] text-fg-subtle">{{ $employee->position?->title ?? '—' }} · {{ $employee->department?->name ?? '—' }}</p></div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-[13px]">
                        <div><dt class="text-fg-subtle">Matricule</dt><dd class="font-medium">{{ $employee->matricule }}</dd></div>
                        <div><dt class="text-fg-subtle">Embauche</dt><dd class="font-medium">{{ $employee->hire_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    </dl>
                </x-ui.card>
                <x-ui.card title="Types de congé" icon="squares-2x2" :padded="false">
                    <ul class="divide-y divide-line">
                        @foreach ($types as $t)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-[13px]">
                                <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-full" style="background: {{ $t->color }}"></span>{{ $t->name }}</span>
                                <span class="text-fg-subtle">{{ $t->days_per_year ? $t->days_per_year.' j/an' : '—' }}@if ($t->deducts_balance) · décompté @endif</span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            </div>
        </div>

        <x-ui.drawer wire:model="showForm" title="Demande de congé" icon="sun" :subtitle="'Solde disponible : '.qty($employee->leave_balance).' jours'">
            <div class="space-y-4">
                <x-ui.select label="Type de congé" wire:model="leaveTypeId" :options="$types->pluck('name', 'id')" required />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input label="Du" type="date" wire:model.live="from" required />
                    <x-ui.input label="Au (inclus)" type="date" wire:model.live="to" required />
                </div>
                @if ($preview !== null)
                    <p class="flex items-center gap-2 rounded-xl bg-lagoon-50 p-3 text-[13px] text-lagoon-800 dark:bg-lagoon-400/10 dark:text-lagoon-200"><x-ui.icon name="calendar-days" class="size-4" />Durée : <strong>{{ qty($preview) }} jour(s) ouvrable(s)</strong></p>
                @endif
                <x-ui.textarea label="Motif" wire:model="reason" rows="3" placeholder="Facultatif" />
            </div>
            <x-slot:footer>
                <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
                <x-ui.button variant="primary" icon="paper-airplane" wire:click="save">Envoyer la demande</x-ui.button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif
</div>
