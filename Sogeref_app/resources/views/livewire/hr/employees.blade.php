<div>
    @php $canManage = auth()->user()->canManage('employees'); @endphp
    <x-ui.page-header title="Employés" eyebrow="Ressources humaines" subtitle="Personnel permanent, contractuel, journalier et stagiaire.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="building-office" wire:click="$set('showStructure', true)">Départements & postes</x-ui.button>
                <x-ui.button variant="accent" icon="user-plus" wire:click="create">Nouvel employé</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Effectif actif" :value="$stats['total']" icon="user-group" />
        <x-ui.stat label="Salariés mensuels" :value="$stats['permanent']" icon="identification" tone="lagoon" />
        <x-ui.stat label="Journaliers" :value="$stats['daily']" icon="wrench" tone="sun" :href="route('attendance')" />
        <x-ui.stat label="Masse salariale de base" :value="money($stats['payroll'], 'USD', 0)" icon="wallet" tone="emerald" hint="mensuels, hors journaliers" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line p-3 lg:flex-row lg:items-center">
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Nom ou matricule…" />
            <select wire:model.live="category" class="input lg:w-48"><option value="">Toutes catégories</option>@foreach (\App\Models\Employee::CATEGORIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <select wire:model.live="departmentId" class="input lg:w-56"><option value="">Tous les départements</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
            <select wire:model.live="status" class="input lg:w-40"><option value="">Tous statuts</option>@foreach (config('erp.statuses.employee') as $k => [$l])<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Employé</th><th>Département / poste</th><th>Catégorie</th><th class="num">Rémunération</th><th>Embauche</th><th class="num">Congés</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @forelse ($employees as $e)
                        <tr wire:key="emp-{{ $e->id }}">
                            <td>
                                <a href="{{ route('employees.show', $e) }}" wire:navigate class="flex items-center gap-3">
                                    <x-ui.avatar :name="$e->full_name" :src="$e->photo_path ? asset('storage/'.$e->photo_path) : null" />
                                    <div class="min-w-0"><p class="truncate font-semibold hover:text-brand-600">{{ $e->full_name }}</p><p class="text-[12px] text-fg-subtle">{{ $e->matricule }} · {{ $e->phone }}</p></div>
                                </a>
                            </td>
                            <td><p>{{ $e->department?->name ?? '—' }}</p><p class="text-[12px] text-fg-subtle">{{ $e->position?->title }}</p></td>
                            <td><x-ui.badge :tone="$e->isDaily() ? 'warning' : 'brand'">{{ \App\Models\Employee::CATEGORIES[$e->category] }}</x-ui.badge></td>
                            <td class="num font-semibold">{{ $e->isDaily() ? money($e->daily_rate).' /j' : money($e->base_salary).' /mois' }}</td>
                            <td class="whitespace-nowrap text-fg-muted">{{ $e->hire_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="num">{{ $e->isDaily() ? '—' : qty($e->leave_balance).' j' }}</td>
                            <td><x-ui.status map="employee" :value="$e->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                @if ($canManage)<x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="edit({{ $e->id }})" />@endif
                                <x-ui.button variant="ghost" size="sm" square icon="chevron-right" :href="route('employees.show', $e)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty icon="identification" title="Aucun employé" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())<div class="border-t border-line px-4 py-3">{{ $employees->links('components.ui.pagination') }}</div>@endif
    </div>

    {{-- ═══ FICHE EMPLOYÉ ════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showForm" :title="$editing ? 'Modifier la fiche' : 'Nouvel employé'" icon="identification" size="xl">
        <div class="space-y-6">
            <section>
                <p class="eyebrow mb-3">Identité</p>
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input label="Prénom" wire:model="form.first_name" required />
                    <x-ui.input label="Nom" wire:model="form.last_name" required />
                    <x-ui.select label="Genre" wire:model="form.gender" :options="['M' => 'Masculin', 'F' => 'Féminin']" />
                    <x-ui.input label="Date de naissance" type="date" wire:model="form.birth_date" />
                    <x-ui.input label="Téléphone" wire:model="form.phone" />
                    <x-ui.input label="E-mail" type="email" wire:model="form.email" />
                    <x-ui.input label="Adresse" wire:model="form.address" class="md:col-span-2" />
                    <x-ui.input label="Pièce d’identité" wire:model="form.national_id" />
                    <div class="md:col-span-3">
                        <label class="label">Photo</label>
                        <input type="file" wire:model="photo" accept="image/*" class="block w-full text-[13px] file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700">
                    </div>
                </div>
            </section>
            <section>
                <p class="eyebrow mb-3">Contrat & affectation</p>
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.select label="Catégorie" wire:model.live="form.category" :options="\App\Models\Employee::CATEGORIES" />
                    <x-ui.select label="Département" wire:model="form.department_id" placeholder="—" :options="$departments->pluck('name', 'id')" />
                    <x-ui.select label="Poste" wire:model="form.job_position_id" placeholder="—" :options="$positions->mapWithKeys(fn ($p) => [$p->id => $p->title.($p->department ? ' ('.$p->department->code.')' : '')])" />
                    <x-ui.input label="Date d’embauche" type="date" wire:model="form.hire_date" />
                    <x-ui.select label="Statut" wire:model="form.status" :options="collect(config('erp.statuses.employee'))->map(fn ($s) => $s[0])" />
                    <x-ui.input label="Solde de congés" type="number" step="0.5" wire:model="form.leave_balance" suffix="jours" />
                </div>
            </section>
            <section>
                <p class="eyebrow mb-3">Rémunération & déclarations</p>
                <div class="grid gap-4 md:grid-cols-3">
                    @if (($form['pay_type'] ?? 'monthly') === 'daily')
                        <x-ui.input label="Taux journalier" type="number" step="0.5" wire:model="form.daily_rate" suffix="USD/j" required />
                    @else
                        <x-ui.input label="Salaire de base mensuel" type="number" step="0.01" wire:model="form.base_salary" suffix="USD" required />
                    @endif
                    <x-ui.input label="Indemnité de transport" type="number" step="0.01" wire:model="form.transport_allowance" suffix="USD" />
                    <x-ui.input label="Indemnité de logement" type="number" step="0.01" wire:model="form.housing_allowance" suffix="USD" />
                    <x-ui.input label="N° CNSS" wire:model="form.cnss_number" />
                    <x-ui.input label="Personnes à charge" type="number" min="0" wire:model="form.dependents" hint="Réduction IPR de 2 % par personne (max 9)." />
                    <x-ui.select label="Mode de paiement" wire:model="form.payment_method" :options="['cash' => 'Espèces', 'bank' => 'Virement bancaire', 'mobile_money' => 'Mobile money']" />
                    <x-ui.input label="Compte bancaire / n° mobile" wire:model="form.bank_account" class="md:col-span-3" />
                </div>
            </section>
            <x-ui.textarea label="Notes RH" wire:model="form.notes" rows="2" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    {{-- ═══ STRUCTURE ════════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showStructure" title="Départements & postes" icon="building-office" size="lg">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <p class="label">Départements</p>
                <ul class="divide-y divide-line rounded-xl border border-line">
                    @foreach ($departments as $d)
                        <li class="flex items-center justify-between px-3 py-2 text-[13.5px]"><span class="flex items-center gap-2"><span class="size-2.5 rounded-full" style="background: {{ $d->color }}"></span>{{ $d->name }} <span class="text-fg-subtle">{{ $d->code }}</span></span><span class="text-fg-subtle">{{ $d->employees_count }}</span></li>
                    @endforeach
                </ul>
                <div class="mt-3 flex gap-2"><input wire:model="newDepartment" class="input" placeholder="Nom"><input wire:model="newDepartmentCode" class="input w-24" placeholder="Code"><x-ui.button variant="secondary" square icon="plus" wire:click="addDepartment" /></div>
                @error('newDepartmentCode')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <p class="label">Postes</p>
                <ul class="max-h-80 divide-y divide-line overflow-y-auto rounded-xl border border-line">
                    @foreach ($positions as $p)<li class="flex justify-between px-3 py-2 text-[13.5px]"><span>{{ $p->title }}</span><span class="text-fg-subtle">{{ $p->department?->code }}</span></li>@endforeach
                </ul>
                <div class="mt-3 flex gap-2"><input wire:model="newPosition" class="input" placeholder="Intitulé du poste"><select wire:model="positionDepartmentId" class="input w-28"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->code }}</option>@endforeach</select><x-ui.button variant="secondary" square icon="plus" wire:click="addPosition" /></div>
            </div>
        </div>
    </x-ui.drawer>
</div>
