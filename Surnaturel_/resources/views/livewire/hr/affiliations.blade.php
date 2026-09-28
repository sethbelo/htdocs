@php($money = fn ($v) => \App\Services\Support\Money::format($v))
<div class="space-y-6">
    <x-page-header title="Santé & assurance" eyebrow="Ressources humaines" subtitle="Affiliation des agents et de leurs familles aux soins médicaux et à l’assurance vie, bons de prise en charge et attestations.">
        <x-slot:actions>
            @can('affiliations.create')<flux:button size="sm" icon="plus" wire:click="editPolicy">Nouvelle police</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
        <x-kpi label="Agents couverts" :value="$this->stats['employees']" icon="user" color="indigo" />
        <x-kpi label="Ayants droit couverts" :value="$this->stats['dependents']" icon="users" color="sky" />
        <x-kpi label="Polices actives" :value="$this->stats['policies']" icon="shield-check" color="purple" />
        <x-kpi label="Cotisations / mois" :value="$money($this->stats['premium'])" icon="banknotes" color="amber" />
        <x-kpi label="Prises en charge (mois)" :value="$this->stats['vouchers']" icon="heart" color="red" />
    </div>

    <div class="tabs">
        @foreach (['members' => 'Agents & familles', 'policies' => 'Polices', 'care' => 'Prises en charge'] as $key => $label)
            <button type="button" class="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'members')
        <div class="grid gap-6 xl:grid-cols-5">
            <div class="space-y-3 xl:col-span-2">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher un agent…" />
                <div class="panel max-h-[680px] divide-y divide-line overflow-y-auto">
                    @forelse ($this->employees as $employee)
                        <button type="button" wire:click="selectEmployee({{ $employee->id }})" wire:key="ae-{{ $employee->id }}" @class(['flex w-full items-center gap-3 p-3 text-left transition hover:bg-zinc-50 dark:hover:bg-white/4', 'bg-brand-50/60 dark:bg-white/6' => $employeeId === $employee->id])>
                            <span class="avatar size-10 bg-brand-100 text-[12px] text-brand-700 dark:bg-white/10 dark:text-white">{{ $employee->initials() }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold">{{ $employee->fullName() }}</span>
                                <span class="block truncate text-xs text-fg-subtle">{{ $employee->matricule }} · {{ $employee->dependents_count }} ayant(s) droit</span>
                            </span>
                            @if ($employee->affiliations_count)
                                <flux:badge size="sm" color="emerald" icon="shield-check">{{ $employee->affiliations_count }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Non couvert</flux:badge>
                            @endif
                        </button>
                    @empty
                        <x-empty-state icon="users" title="Aucun agent" />
                    @endforelse
                </div>
            </div>

            <div class="space-y-6 xl:col-span-3">
                @if ($employee = $this->employee)
                    <div class="panel p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="eyebrow">{{ $employee->matricule }}</p>
                                <flux:heading size="lg">{{ $employee->fullName() }}</flux:heading>
                                <flux:text>{{ $employee->job_title ?? '—' }} · groupe sanguin {{ $employee->blood_group ?? 'non renseigné' }}</flux:text>
                            </div>
                            @can('affiliations.create')
                                <div class="flex flex-wrap gap-2">
                                    <flux:button size="sm" icon="user-plus" x-on:click="$flux.modal('dependent').show()">Ayant droit</flux:button>
                                    <flux:button size="sm" variant="primary" icon="shield-check" wire:click="openAffiliation">Affilier</flux:button>
                                </div>
                            @endcan
                        </div>

                        <div class="mt-5">
                            <div class="mb-2 text-sm font-semibold">Famille & ayants droit</div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-3 dark:border-white/10 dark:bg-white/4">
                                    <div class="flex items-center gap-2 text-sm font-semibold"><flux:icon.user variant="mini" class="text-brand-500" />{{ $employee->fullName() }}</div>
                                    <div class="text-xs text-fg-subtle">Agent titulaire{{ $employee->birth_date ? ' · né(e) le '.$employee->birth_date->format('d/m/Y') : '' }}</div>
                                </div>
                                @foreach ($employee->dependents as $dependent)
                                    <div class="group rounded-xl border border-line p-3" wire:key="dep-{{ $dependent->id }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 text-sm font-semibold"><flux:icon :icon="$dependent->relationship->value === 'child' ? 'face-smile' : 'heart'" variant="mini" class="text-carmin-400" />{{ $dependent->fullName() }}</div>
                                            @can('affiliations.delete')<button type="button" wire:click="removeDependent({{ $dependent->id }})" wire:confirm="Retirer cet ayant droit ? Ses affiliations seront résiliées." class="text-fg-subtle opacity-0 transition group-hover:opacity-100 hover:text-carmin-600"><flux:icon.trash variant="micro" /></button>@endcan
                                        </div>
                                        <div class="text-xs text-fg-subtle">{{ $dependent->relationship->label() }}{{ $dependent->birth_date ? ' · '.$dependent->birth_date->age.' ans' : '' }}{{ $dependent->is_student ? ' · étudiant(e)' : '' }}</div>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @forelse ($dependent->affiliations->where('status', \App\Enums\AffiliationStatus::Active) as $a)
                                                <flux:badge size="sm" :color="$a->policy->type->color()">{{ $a->policy->name }}</flux:badge>
                                            @empty
                                                <span class="text-xs text-carmin-600">Non couvert</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="border-b border-line p-4"><flux:heading>Affiliations</flux:heading></div>
                        <ul class="divide-y divide-line">
                            @forelse ($employee->affiliations as $a)
                                <li class="flex flex-wrap items-center gap-3 p-4" wire:key="aff-{{ $a->id }}">
                                    <span @class(['grid size-10 shrink-0 place-items-center rounded-xl', 'bg-cyan2-50 text-cyan2-600 dark:bg-cyan2-400/10' => $a->policy->type->value === 'medical', 'bg-brand-50 text-brand-600 dark:bg-brand-400/10' => $a->policy->type->value === 'life'])><flux:icon :icon="$a->policy->type->icon()" variant="mini" /></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ $a->policy->name }}</span><flux:badge size="sm" :color="$a->status->color()">{{ $a->status->label() }}</flux:badge></div>
                                        <div class="text-xs text-fg-subtle">{{ $a->memberName() }} ({{ $a->memberRelationship() }}) · n° {{ $a->member_number }} · depuis le {{ $a->starts_on->format('d/m/Y') }} · {{ $a->policy->provider }}</div>
                                        @if ($a->beneficiary_name)<div class="text-xs">Bénéficiaire : <span class="font-medium">{{ $a->beneficiary_name }}</span> ({{ $a->beneficiary_relationship }}, {{ (float) $a->beneficiary_share }} %)</div>@endif
                                        @if ($a->careVouchers->isNotEmpty())<div class="text-xs text-fg-subtle">{{ $a->careVouchers->count() }} prise(s) en charge, dernière le {{ $a->careVouchers->first()->issued_on->format('d/m/Y') }}</div>@endif
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        @if ($a->policy->type->value === 'medical' && $a->isActive())
                                            @can('affiliations.create')<flux:button size="xs" icon="plus" wire:click="openCare({{ $a->id }})">Prise en charge</flux:button>@endcan
                                        @endif
                                        <flux:button size="xs" icon="document-text" :href="route('print.affiliation', $a)" target="_blank">Attestation</flux:button>
                                        <flux:button size="xs" icon="credit-card" :href="route('print.member-card', $a)" target="_blank">Carte</flux:button>
                                        @can('affiliations.update')
                                            <flux:dropdown>
                                                <flux:button size="xs" icon="ellipsis-horizontal" />
                                                <flux:menu>
                                                    @foreach (\App\Enums\AffiliationStatus::cases() as $status)
                                                        @if ($status !== $a->status)<flux:menu.item wire:click="setStatus({{ $a->id }}, '{{ $status->value }}')">{{ $status === \App\Enums\AffiliationStatus::Active ? 'Réactiver' : ($status === \App\Enums\AffiliationStatus::Suspended ? 'Suspendre' : 'Résilier') }}</flux:menu.item>@endif
                                                    @endforeach
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endcan
                                    </div>
                                </li>
                            @empty
                                <li><x-empty-state icon="shield-exclamation" title="Aucune affiliation" text="Affiliez l’agent et sa famille à une police santé ou assurance vie." /></li>
                            @endforelse
                        </ul>
                    </div>
                @else
                    <div class="panel"><x-empty-state icon="cursor-arrow-rays" title="Sélectionnez un agent" text="Sa famille, ses couvertures et ses prises en charge s’affichent ici." /></div>
                @endif
            </div>
        </div>
    @elseif ($tab === 'policies')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($policies as $p)
                <div class="panel space-y-3 p-5" wire:key="pol-{{ $p->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <flux:badge size="sm" :color="$p->type->color()" :icon="$p->type->icon()">{{ $p->type->label() }}</flux:badge>
                            <flux:heading class="mt-2">{{ $p->name }}</flux:heading>
                            <flux:text>{{ $p->provider }}{{ $p->policy_number ? ' · police n° '.$p->policy_number : '' }}</flux:text>
                        </div>
                        @if (! $p->is_active)<flux:badge size="sm" color="red">Inactive</flux:badge>@endif
                    </div>
                    @if ($p->coverage)<p class="line-clamp-3 text-sm text-fg-muted">{{ $p->coverage }}</p>@endif
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <div><dt class="text-xs text-fg-subtle">Membres actifs</dt><dd class="font-semibold">{{ $p->affiliations_count }}</dd></div>
                        <div><dt class="text-xs text-fg-subtle">Cotisation / membre</dt><dd class="font-semibold">{{ $money($p->monthly_premium) }}</dd></div>
                        <div><dt class="text-xs text-fg-subtle">Part employeur</dt><dd class="font-semibold">{{ (float) $p->employer_share }} %</dd></div>
                        <div><dt class="text-xs text-fg-subtle">Plafond</dt><dd class="font-semibold">{{ $p->coverage_limit ? $money($p->coverage_limit) : '—' }}</dd></div>
                    </dl>
                    @can('affiliations.update')<x-edit-button wire:click="editPolicy({{ $p->id }})" />@endcan
                </div>
            @empty
                <div class="panel md:col-span-2 xl:col-span-3"><x-empty-state icon="shield-check" title="Aucune police" text="Enregistrez vos contrats d’assurance maladie et d’assurance vie." /></div>
            @endforelse
        </div>
    @else
        <div class="panel">
            <flux:table>
                <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Bénéficiaire</flux:table.column><flux:table.column>Soin</flux:table.column><flux:table.column>Établissement</flux:table.column><flux:table.column align="end">Estimation</flux:table.column><flux:table.column>Validité</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse ($vouchers as $v)
                        <flux:table.row :key="'cv-'.$v->id">
                            <flux:table.cell class="font-mono text-xs">{{ $v->number }}</flux:table.cell>
                            <flux:table.cell><span class="font-medium">{{ $v->affiliation->memberName() }}</span><div class="text-xs text-fg-subtle">{{ $v->affiliation->memberRelationship() }} de {{ $v->affiliation->employee->fullName() }}</div></flux:table.cell>
                            <flux:table.cell>{{ $v->care_type->label() }}</flux:table.cell>
                            <flux:table.cell>{{ $v->care_provider }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $money($v->estimated_amount) }}</flux:table.cell>
                            <flux:table.cell><span @class(['text-carmin-600' => $v->valid_until->isPast()])>{{ $v->issued_on->format('d/m') }} → {{ $v->valid_until->format('d/m/Y') }}</span></flux:table.cell>
                            <flux:table.cell><flux:button size="xs" variant="ghost" icon="document-arrow-down" :href="route('print.care-voucher', $v)" target="_blank" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="heart" title="Aucune prise en charge émise" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <flux:modal name="dependent" class="w-full max-w-lg">
        <form wire:submit="saveDependent" class="space-y-4">
            <flux:heading size="lg">Ajouter un ayant droit</flux:heading>
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="dependent.first_name" label="Prénom" required />
                <flux:input wire:model="dependent.last_name" label="Nom" required />
                <flux:select wire:model="dependent.relationship" label="Lien">@foreach ($relationships as $r)<flux:select.option :value="$r->value">{{ $r->label() }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="dependent.gender" label="Sexe"><flux:select.option value="">—</flux:select.option><flux:select.option value="M">Masculin</flux:select.option><flux:select.option value="F">Féminin</flux:select.option></flux:select>
                <flux:input wire:model="dependent.birth_date" type="date" label="Date de naissance" />
                <flux:input wire:model="dependent.phone" label="Téléphone" />
            </div>
            <flux:checkbox wire:model="dependent.is_student" label="Étudiant(e) à charge" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Ajouter</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="affiliate" class="w-full max-w-lg">
        <form wire:submit="affiliate" class="space-y-4">
            <flux:heading size="lg">Nouvelle affiliation</flux:heading>
            <flux:select wire:model.live="affiliation.insurance_policy_id" label="Police">
                <flux:select.option value="">Choisir…</flux:select.option>
                @foreach ($policies->where('is_active', true) as $p)<flux:select.option :value="$p->id">{{ $p->type->label() }} — {{ $p->name }} ({{ $p->provider }})</flux:select.option>@endforeach
            </flux:select>
            @php($selectedPolicy = $policies->firstWhere('id', (int) ($affiliation['insurance_policy_id'] ?? 0)))
            <flux:select wire:model="affiliation.member" label="Membre couvert">
                <flux:select.option value="employee">{{ $this->employee?->fullName() }} (agent)</flux:select.option>
                @if ($selectedPolicy?->type->value !== 'life')
                    @foreach ($this->employee?->dependents ?? [] as $d)<flux:select.option :value="(string) $d->id">{{ $d->fullName() }} ({{ $d->relationship->label() }})</flux:select.option>@endforeach
                @endif
            </flux:select>
            <flux:input wire:model="affiliation.starts_on" type="date" label="Date d’effet" />
            @if ($selectedPolicy?->type->value === 'life')
                <div class="grid grid-cols-2 gap-3 rounded-xl border border-line p-3">
                    <flux:input wire:model="affiliation.beneficiary_name" label="Bénéficiaire désigné" class="col-span-2" />
                    <flux:input wire:model="affiliation.beneficiary_relationship" label="Lien" placeholder="Conjoint(e), enfant…" />
                    <flux:input wire:model="affiliation.beneficiary_share" type="number" label="Part (%)" />
                </div>
            @endif
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Affilier</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="care" class="w-full max-w-lg">
        <form wire:submit="issueCare" class="space-y-4">
            <flux:heading size="lg">Bon de prise en charge médicale</flux:heading>
            <div class="grid grid-cols-2 gap-3">
                <flux:select wire:model="care.care_type" label="Type de soin">@foreach ($careTypes as $c)<flux:select.option :value="$c->value">{{ $c->label() }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="care.validity_days" type="number" label="Validité (jours)" />
                <flux:input wire:model="care.care_provider" label="Établissement de soins" class="col-span-2" placeholder="Clinique, hôpital, pharmacie…" required />
                <flux:input wire:model="care.estimated_amount" type="number" step="0.01" label="Montant estimé" />
                <flux:textarea wire:model="care.description" label="Motif / observations" rows="2" class="col-span-2" />
            </div>
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary" icon="printer">Émettre et imprimer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="policy" class="w-full max-w-2xl">
        <form wire:submit="savePolicy" class="space-y-4">
            <flux:heading size="lg">{{ $editingPolicyId ? 'Modifier la police' : 'Nouvelle police' }}</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="policy.name" label="Nom du contrat" required />
                <flux:select wire:model="policy.type" label="Type">@foreach ($insuranceTypes as $t)<flux:select.option :value="$t->value">{{ $t->label() }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="policy.provider" label="Assureur / prestataire" required />
                <flux:input wire:model="policy.policy_number" label="N° de police" />
                <flux:input wire:model="policy.monthly_premium" type="number" step="0.01" label="Cotisation mensuelle / membre" />
                <flux:input wire:model="policy.employer_share" type="number" label="Part employeur (%)" />
                <flux:input wire:model="policy.coverage_limit" type="number" step="0.01" label="Plafond de couverture" />
                <flux:input wire:model="policy.contact_phone" label="Téléphone assureur" />
                <flux:input wire:model="policy.starts_on" type="date" label="Début" />
                <flux:input wire:model="policy.ends_on" type="date" label="Fin" />
                <flux:textarea wire:model="policy.coverage" label="Garanties couvertes" rows="3" class="sm:col-span-2" />
            </div>
            <flux:switch wire:model="policy.is_active" label="Police active" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>
</div>
