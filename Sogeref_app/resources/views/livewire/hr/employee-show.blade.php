<div>
    @php $e = $employee; @endphp
    {{-- ═══ 1. PROFIL ════════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$e->full_name" :eyebrow="$e->matricule.' · '.\App\Models\Employee::CATEGORIES[$e->category]" :back="route('employees')">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[13px] text-fg-muted">
                <x-ui.status map="employee" :value="$e->status" />
                <span>{{ $e->position?->title ?? 'Poste non défini' }} · {{ $e->department?->name ?? '—' }}</span>
                @if ($e->user)<x-ui.badge tone="brand" icon="key">Compte {{ $e->user->email }}</x-ui.badge>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            @if (auth()->user()->canManage('employees'))<x-ui.button variant="secondary" icon="pencil-square" :href="route('employees', ['modifier' => $e])">Modifier la fiche</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat :label="$e->isDaily() ? 'Taux journalier' : 'Salaire de base'" :value="$e->isDaily() ? money($e->daily_rate) : money($e->base_salary)" icon="banknotes" />
        <x-ui.stat :label="$e->isDaily() ? 'Jours pointés ce mois' : 'Solde de congés'" :value="$e->isDaily() ? qty($monthDays).' j' : qty($e->leave_balance).' j'" icon="calendar-days" tone="lagoon" />
        <x-ui.stat label="Ancienneté" :value="$e->hire_date ? $e->hire_date->diffForHumans(null, true) : '—'" icon="clock" tone="sun" />
        <x-ui.stat label="Avances non retenues" :value="money($advances->where('status', 'open')->sum('amount'))" icon="arrow-uturn-left" tone="flame" />
    </div>

    <div class="card">
        <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['infos' => ['label' => 'Informations', 'icon' => 'identification'], 'presences' => ['label' => 'Présences', 'icon' => 'clipboard-document-check'], 'conges' => ['label' => 'Congés', 'icon' => 'sun', 'count' => $leaves->where('status', 'pending')->count()], 'paie' => ['label' => 'Bulletins', 'icon' => 'wallet', 'count' => $payslips->count()], 'avances' => ['label' => 'Avances', 'icon' => 'arrow-uturn-left'], 'chantiers' => ['label' => 'Chantiers', 'icon' => 'home-modern', 'count' => $e->sites->count()]]" /></div>
        <div class="p-5">
            @if ($tab === 'infos')
                <div class="grid gap-6 md:grid-cols-[auto_1fr]">
                    <x-ui.avatar :name="$e->full_name" size="xl" :src="$e->photo_path ? asset('storage/'.$e->photo_path) : null" />
                    <dl class="grid gap-x-8 gap-y-4 text-[13.5px] sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Téléphone' => $e->phone, 'E-mail' => $e->email, 'Adresse' => $e->address,
                            'Date de naissance' => $e->birth_date?->format('d/m/Y'), 'Pièce d’identité' => $e->national_id, 'N° CNSS' => $e->cnss_number,
                            'Personnes à charge' => $e->dependents, 'Date d’embauche' => $e->hire_date?->format('d/m/Y'), 'Mode de paiement' => ['cash' => 'Espèces', 'bank' => 'Virement', 'mobile_money' => 'Mobile money'][$e->payment_method] ?? '—',
                            'Compte / n° mobile' => $e->bank_account, 'Indemnité transport' => money($e->transport_allowance), 'Indemnité logement' => money($e->housing_allowance),
                        ] as $label => $value)
                            <div><dt class="text-fg-subtle">{{ $label }}</dt><dd class="font-medium">{{ $value ?: '—' }}</dd></div>
                        @endforeach
                    </dl>
                </div>
                @if ($e->notes)<p class="mt-6 rounded-xl bg-surface-2 p-4 text-[13.5px] text-fg-muted">{{ $e->notes }}</p>@endif

            @elseif ($tab === 'presences')
                <table class="table">
                    <thead><tr><th>Date</th><th>Chantier</th><th>Statut</th><th>Horaires</th><th class="num">H. sup</th><th class="num">Montant</th></tr></thead>
                    <tbody>
                        @forelse ($attendance as $a)
                            <tr>
                                <td class="font-medium">{{ ucfirst($a->sheet->work_date->translatedFormat('D j M')) }}</td>
                                <td class="text-fg-muted">{{ $a->sheet->site->code }}</td>
                                <td><x-ui.status map="attendance" :value="$a->status" /></td>
                                <td class="text-fg-muted">{{ $a->check_in ? substr($a->check_in, 0, 5).' – '.substr((string) $a->check_out, 0, 5) : '—' }}</td>
                                <td class="num">{{ (float) $a->overtime_hours ? qty($a->overtime_hours) : '' }}</td>
                                <td class="num font-semibold">{{ money($a->dayFraction() * (float) $a->daily_rate) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-ui.empty icon="clipboard-document-check" title="Aucun pointage sur 45 jours" /></td></tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif ($tab === 'conges')
                @if (auth()->user()->canManage('leaves'))
                    <div class="mb-5 grid gap-3 rounded-xl bg-surface-2 p-4 md:grid-cols-5 md:items-end">
                        <x-ui.select label="Type" wire:model="leaveTypeId" :options="$leaveTypes->pluck('name', 'id')" />
                        <x-ui.input label="Du" type="date" wire:model="leaveFrom" />
                        <x-ui.input label="Au" type="date" wire:model="leaveTo" />
                        <x-ui.input label="Motif" wire:model="leaveReason" />
                        <x-ui.button variant="primary" icon="plus" wire:click="requestLeave">Demander</x-ui.button>
                    </div>
                @endif
                <table class="table">
                    <thead><tr><th>Référence</th><th>Type</th><th>Période</th><th class="num">Jours</th><th>Statut</th><th>Décision</th></tr></thead>
                    <tbody>
                        @forelse ($leaves as $l)
                            <tr>
                                <td class="font-semibold">{{ $l->reference }}</td>
                                <td><span class="inline-flex items-center gap-2"><span class="size-2 rounded-full" style="background: {{ $l->type->color }}"></span>{{ $l->type->name }}</span></td>
                                <td class="text-fg-muted">{{ $l->start_date->format('d/m/Y') }} → {{ $l->end_date->format('d/m/Y') }}</td>
                                <td class="num">{{ qty($l->days) }}</td>
                                <td><x-ui.status map="leave" :value="$l->status" /></td>
                                <td class="text-[12.5px] text-fg-muted">{{ $l->approver?->name }} {{ $l->decided_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-ui.empty icon="sun" title="Aucune demande de congé" /></td></tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif ($tab === 'paie')
                <table class="table">
                    <thead><tr><th>Bulletin</th><th>Période</th><th class="num">Brut</th><th class="num">Retenues</th><th class="num">Net</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($payslips as $s)
                            <tr>
                                <td class="font-semibold">{{ $s->reference }}</td>
                                <td class="text-fg-muted">{{ $s->period->name }}</td>
                                <td class="num">{{ money($s->gross) }}</td>
                                <td class="num text-flame-600">{{ money((float) $s->cnss_employee + (float) $s->ipr + (float) $s->other_deductions) }}</td>
                                <td class="num font-semibold">{{ money($s->net) }}</td>
                                <td><x-ui.status map="payroll" :value="$s->status === 'draft' ? 'computed' : $s->status" /></td>
                                <td class="text-right"><a href="{{ route('pdf.payslip', $s) }}" target="_blank" class="btn btn-ghost btn-sm"><x-ui.icon name="printer" class="size-4" /> Fiche</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-ui.empty icon="wallet" title="Aucun bulletin" /></td></tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif ($tab === 'avances')
                @if (auth()->user()->canManage('payroll'))
                    <div class="mb-5 flex flex-col gap-2 rounded-xl bg-surface-2 p-3 sm:flex-row">
                        <input type="number" step="0.01" wire:model="advanceAmount" class="input sm:w-40" placeholder="Montant USD">
                        <input wire:model="advanceNote" class="input flex-1" placeholder="Motif de l’avance">
                        <x-ui.button variant="primary" icon="plus" wire:click="addAdvance">Accorder</x-ui.button>
                    </div>
                @endif
                <table class="table">
                    <thead><tr><th>Date</th><th>Motif</th><th class="num">Montant</th><th>État</th></tr></thead>
                    <tbody>
                        @forelse ($advances as $a)
                            <tr><td>{{ $a->granted_at->format('d/m/Y') }}</td><td class="text-fg-muted">{{ $a->note }}</td><td class="num font-semibold">{{ money($a->amount) }}</td><td>@if ($a->status === 'open')<x-ui.badge tone="warning" dot>À retenir</x-ui.badge>@else<x-ui.badge tone="success" dot>Retenue</x-ui.badge>@endif</td></tr>
                        @empty
                            <tr><td colspan="4"><x-ui.empty icon="arrow-uturn-left" title="Aucune avance" /></td></tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                <ul class="grid gap-3 md:grid-cols-2">
                    @forelse ($e->sites as $site)
                        <li><a href="{{ route('sites.show', $site) }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-line p-4 transition hover:border-brand-200"><x-ui.icon name="home-modern" class="text-fg-subtle" /><div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $site->name }}</p><p class="text-[12px] text-fg-subtle">{{ $site->pivot->role }} · depuis le {{ \Illuminate\Support\Carbon::parse($site->pivot->joined_at)->format('d/m/Y') }}{{ $site->pivot->left_at ? ' · libéré' : '' }}</p></div><x-ui.status map="site" :value="$site->status" /></a></li>
                    @empty
                        <li class="md:col-span-2"><x-ui.empty icon="home-modern" title="Aucune affectation de chantier" /></li>
                    @endforelse
                </ul>
            @endif
        </div>
    </div>
</div>
