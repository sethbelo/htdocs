<div class="space-y-6">
    <x-page-header title="Employés" subtitle="Permanents, journaliers, contractuels, prestataires et stagiaires.">
        <x-slot:actions>@can('employees.create')<flux:button size="sm" variant="primary" icon="user-plus" wire:click="create">Nouvel employé</flux:button>@endcan</x-slot:actions>
    </x-page-header>

    <div class="panel p-4 space-y-4">
        <div class="grid gap-3 md:grid-cols-5">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, matricule…" />
            <flux:select wire:model.live="status"><flux:select.option value="">Tous statuts</flux:select.option>@foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="departmentId"><flux:select.option value="">Tous départements</flux:select.option>@foreach ($departments as $d)<flux:select.option :value="$d->id">{{ $d->name }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="jobTitle"><flux:select.option value="">Toutes fonctions</flux:select.option>@foreach ($jobTitles as $t)<flux:select.option :value="$t">{{ $t }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="siteId"><flux:select.option value="">Tous sièges</flux:select.option>@foreach ($this->accessibleSites() as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
        </div>
        <flux:table :paginate="$employees">
            <flux:table.columns><flux:table.column>Matricule</flux:table.column><flux:table.column>Employé</flux:table.column><flux:table.column>Fonction</flux:table.column><flux:table.column>Département</flux:table.column><flux:table.column>Siège</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Heures (mois)</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($employees as $employee)
                    <flux:table.row :key="$employee->id">
                        <flux:table.cell class="text-zinc-500">{{ $employee->matricule }}</flux:table.cell>
                        <flux:table.cell><button wire:click="show({{ $employee->id }})" class="font-medium hover:text-indigo-600">{{ $employee->fullName() }}</button><div class="text-xs text-zinc-500">{{ $employee->phone }}</div></flux:table.cell>
                        <flux:table.cell>{{ $employee->job_title }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->department?->name }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->site?->name }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $employee->employment_status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end">{{ round(($employee->minutes_month ?? 0) / 60, 1) }} h</flux:table.cell>
                        <flux:table.cell>@can('update', $employee)<flux:button size="xs" variant="ghost" icon="pencil" wire:click="edit({{ $employee->id }})" />@endcan</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="identification" title="Aucun employé" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="employee" class="w-full max-w-3xl">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? 'Modifier la fiche' : 'Nouvel employé' }}</flux:heading>
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="form.matricule" label="Matricule" />
                <flux:input wire:model="form.first_name" label="Prénom" />
                <flux:input wire:model="form.last_name" label="Nom" />
                <flux:select wire:model="form.gender" label="Sexe"><flux:select.option value="">—</flux:select.option><flux:select.option value="M">Masculin</flux:select.option><flux:select.option value="F">Féminin</flux:select.option></flux:select>
                <flux:input wire:model="form.birth_date" type="date" label="Date de naissance" />
                <flux:input wire:model="form.birth_place" label="Lieu de naissance" />
                <flux:input wire:model="form.nationality" label="Nationalité" />
                <flux:input wire:model="form.phone" label="Téléphone" />
                <flux:input wire:model="form.email" label="E-mail" />
                <flux:input wire:model="form.address" label="Adresse" class="md:col-span-2" />
                <flux:input wire:model="form.emergency_contact" label="Contact d’urgence" />
            </div>
            <flux:separator text="Affectation & contrat" />
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="form.job_title" label="Fonction" />
                <flux:select wire:model="form.department_id" label="Département"><flux:select.option value="">—</flux:select.option>@foreach ($departments as $d)<flux:select.option :value="$d->id">{{ $d->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.site_id" label="Siège">@foreach ($this->accessibleSites() as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.manager_id" label="Responsable"><flux:select.option value="">—</flux:select.option>@foreach ($managers as $m)<flux:select.option :value="$m->id">{{ $m->first_name }} {{ $m->last_name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.employment_status" label="Statut">@foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="form.hired_at" type="date" label="Date d’embauche" />
                <flux:select wire:model="form.salary_type" label="Rémunération"><flux:select.option value="monthly">Mensuelle</flux:select.option><flux:select.option value="daily">Journalière</flux:select.option><flux:select.option value="hourly">Horaire</flux:select.option></flux:select>
                <flux:input wire:model="form.badge_code" label="Badge (RFID/NFC/QR)" />
                <flux:select wire:model="form.user_id" label="Compte utilisateur"><flux:select.option value="">—</flux:select.option>@foreach ($users as $u)<flux:select.option :value="$u->id">{{ $u->name }} ({{ $u->username }})</flux:select.option>@endforeach</flux:select>
                <flux:input type="file" wire:model="photo" label="Photo" accept="image/*" />
            </div>
            @if ($canSensitive)
                <flux:separator text="Données confidentielles (chiffrées)" />
                <div class="grid gap-3 md:grid-cols-3">
                    <flux:input wire:model="form.base_salary" type="number" step="0.01" label="Salaire de base / taux" />
                    <flux:input wire:model="form.national_id" label="Pièce d’identité" />
                    <flux:input wire:model="form.mobile_money_number" label="N° Mobile Money" />
                    <flux:input wire:model="form.bank_name" label="Banque" />
                    <flux:input wire:model="form.bank_account" label="N° de compte bancaire" class="md:col-span-2" />
                </div>
            @else
                <flux:callout icon="lock-closed" variant="secondary" heading="Salaire, pièce d’identité et coordonnées bancaires réservés aux RH autorisés." />
            @endif
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="profile" class="w-full max-w-2xl">
        @if ($viewing)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ $viewing->fullName() }}</flux:heading>
                    <flux:text>{{ $viewing->matricule }} · {{ $viewing->job_title }} · {{ $viewing->employment_status->label() }} · compte {{ $viewing->user?->username ?? '—' }}</flux:text>
                </div>
                @can('viewSensitive', $viewing)
                    <div class="grid grid-cols-2 gap-2 rounded-lg bg-amber-50 p-3 text-sm dark:bg-amber-500/10">
                        <div>Salaire : <strong><x-money :amount="$viewing->baseSalary()" /></strong> ({{ $viewing->salary_type }})</div>
                        <div>Pièce : {{ $viewing->national_id ?? '—' }}</div>
                        <div>Banque : {{ $viewing->bank_name }} {{ $viewing->bank_account }}</div>
                        <div>Mobile Money : {{ $viewing->mobile_money_number ?? '—' }}</div>
                    </div>
                @endcan
                <div>
                    <flux:heading size="sm">Historique des affectations</flux:heading>
                    <ul class="mt-1 text-sm">@foreach ($viewing->assignments as $a)<li>{{ $a->starts_on->format('d/m/Y') }} → {{ $a->ends_on?->format('d/m/Y') ?? 'aujourd’hui' }} · {{ $a->job_title }} · {{ $a->department?->name }} · {{ $a->site?->name }}</li>@endforeach</ul>
                </div>
                <div>
                    <flux:heading size="sm">Congés</flux:heading>
                    <ul class="mt-1 text-sm">@forelse ($viewing->leaveRequests->take(5) as $l)<li>{{ $l->leaveType->name }} · {{ $l->starts_on->format('d/m') }}–{{ $l->ends_on->format('d/m/Y') }} · {{ $l->status->label() }}</li>@empty<li class="text-zinc-500">Aucun</li>@endforelse</ul>
                </div>
                <div>
                    <flux:heading size="sm">Bulletins de paie</flux:heading>
                    <div class="mt-1 flex flex-wrap gap-2">@foreach ($viewing->payslips as $p)<flux:button size="xs" icon="document" :href="route('print.payslip', $p)" target="_blank">{{ $p->period->code }}</flux:button>@endforeach</div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
