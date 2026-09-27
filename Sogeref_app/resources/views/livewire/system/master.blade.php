<div>
    <x-ui.page-header title="Master système" eyebrow="Administration" subtitle="Pilotage global de l’ERP : identité, documents, modules, numérotation, règles métier, utilisateurs, droits et maintenance.">
        <x-slot:actions>
            @unless ($canEdit)<x-ui.badge tone="warning" icon="lock-closed">Lecture seule</x-ui.badge>@endunless
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-[230px_1fr]">
        {{-- ═══ NAVIGATION ═══════════════════════════════════════════════════ --}}
        <nav class="card h-fit p-2 lg:sticky lg:top-20">
            @foreach ([
                'societe' => ['Société', 'building-office-2'], 'documents' => ['Documents & impressions', 'document-text'], 'modules' => ['Modules', 'squares-plus'],
                'numerotation' => ['Numérotation', 'hashtag'], 'regles' => ['Règles métier & paie', 'adjustments-horizontal'], 'utilisateurs' => ['Utilisateurs', 'users'],
                'roles' => ['Rôles & droits', 'shield-check'], 'audit' => ['Journal d’audit', 'finger-print'], 'systeme' => ['Système', 'server-stack'],
            ] as $key => [$label, $icon])
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-[13.5px] font-medium transition', 'bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-white' => $tab === $key, 'text-fg-muted hover:bg-surface-2 hover:text-fg' => $tab !== $key])>
                    <x-ui.icon :name="$icon" class="size-[18px]" />{{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="min-w-0">
            {{-- ─── SOCIÉTÉ ────────────────────────────────────────────────── --}}
            @if ($tab === 'societe')
                <x-ui.card title="Identité de la société" subtitle="Reprise sur toutes les impressions (en-têtes, pieds de page, bulletins)" icon="building-office-2">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Nom commercial" wire:model="company.name" required />
                        <x-ui.input label="Raison sociale" wire:model="company.legal_name" />
                        <x-ui.input label="N° RCCM" wire:model="company.registration_number" />
                        <x-ui.input label="ID.NAT" wire:model="company.id_nat" />
                        <x-ui.input label="N° Impôt (NIF)" wire:model="company.tax_number" />
                        <x-ui.input label="N° TVA" wire:model="company.vat_number" />
                        <x-ui.input label="Téléphone" wire:model="company.phone" />
                        <x-ui.input label="E-mail" wire:model="company.email" />
                        <x-ui.input label="Adresse" wire:model="company.line1" />
                        <x-ui.input label="Commune" wire:model="company.line2" />
                        <x-ui.input label="Ville" wire:model="company.city" />
                        <x-ui.input label="Site web" wire:model="company.website" />
                        <x-ui.input label="Slogan" wire:model="company.slogan" class="md:col-span-2" />
                        <div class="md:col-span-2"><x-ui.textarea label="Coordonnées bancaires" wire:model="company.bank_details" rows="2" /></div>
                        <div class="md:col-span-2"><x-ui.textarea label="Pied de page légal des documents" wire:model="company.document_footer" rows="2" /></div>
                        <div>
                            <label class="label">Logo (impressions)</label>
                            <div class="flex items-center gap-3"><img src="{{ company()->logo_path ? asset('storage/'.company()->logo_path) : asset('brand/logo-horizontal.png') }}" class="h-12 rounded bg-white p-1 ring-1 ring-line" alt="Logo"><input type="file" wire:model="logo" accept="image/*" class="text-[12.5px]"></div>
                        </div>
                        <div>
                            <label class="label">Cachet / signature (apposé sur les devis et factures)</label>
                            <div class="flex items-center gap-3">@if (company()->seal_path)<img src="{{ asset('storage/'.company()->seal_path) }}" class="h-12 rounded bg-white p-1 ring-1 ring-line" alt="Cachet">@endif<input type="file" wire:model="seal" accept="image/*" class="text-[12.5px]"></div>
                        </div>
                    </div>
                    @if ($canEdit)<div class="mt-5 flex justify-end"><x-ui.button variant="primary" icon="check" wire:click="saveCompany">Enregistrer</x-ui.button></div>@endif
                </x-ui.card>

            {{-- ─── DOCUMENTS ──────────────────────────────────────────────── --}}
            @elseif ($tab === 'documents')
                <x-ui.card title="Modèles par défaut" subtitle="Chaque document reste modifiable individuellement" icon="swatch">
                    <div class="space-y-5">
                        @foreach (['quote' => 'Devis', 'invoice' => 'Factures', 'purchase_order' => 'Bons de commande fournisseurs'] as $type => $label)
                            <div>
                                <p class="label">{{ $label }}</p>
                                <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                    @foreach (config('erp.document_templates') as $key => $tpl)
                                        <button type="button" wire:click="$set('templates.{{ $type }}', '{{ $key }}')" @class(['rounded-xl border p-3 text-left transition', 'border-brand-600 ring-2 ring-sun-400' => ($templates[$type] ?? '') === $key, 'border-line hover:border-line-strong' => ($templates[$type] ?? '') !== $key])>
                                            <span class="block text-[13.5px] font-semibold">{{ $tpl['label'] }}</span><span class="block text-[11.5px] text-fg-subtle">{{ $tpl['description'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-ui.textarea label="Conditions par défaut des devis" wire:model="quoteTerms" rows="4" />
                            <x-ui.textarea label="Conditions par défaut des factures" wire:model="invoiceTerms" rows="4" />
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-surface-2 p-4">
                            <p class="text-[13px] text-fg-muted">Aperçus : <a href="{{ route('pdf.letterhead') }}" target="_blank" class="link">papier à en-tête</a> · @foreach (config('erp.document_templates') as $key => $tpl)<a href="{{ route('pdf.document', ['document' => \App\Models\Document::where('company_id', company_id())->where('type', 'invoice')->value('id') ?? 0, 'modele' => $key]) }}" target="_blank" class="link">{{ $tpl['label'] }}</a>{{ $loop->last ? '' : ' · ' }}@endforeach</p>
                            @if ($canEdit)<x-ui.button variant="primary" icon="check" wire:click="saveDocuments">Enregistrer</x-ui.button>@endif
                        </div>
                    </div>
                </x-ui.card>

            {{-- ─── MODULES ────────────────────────────────────────────────── --}}
            @elseif ($tab === 'modules')
                <x-ui.card title="Modules de l’ERP" subtitle="Un module désactivé disparaît du menu pour tous les utilisateurs" icon="squares-plus" :padded="false">
                    @foreach (collect(config('erp.modules'))->groupBy('group', true) as $group => $modules)
                        <p class="border-b border-line bg-surface-2 px-5 py-2 text-[11px] font-semibold tracking-wider text-fg-subtle uppercase">{{ config('erp.groups.'.$group) }}</p>
                        @foreach ($modules as $key => $module)
                            <div class="flex items-center gap-4 border-b border-line px-5 py-3 last:border-0">
                                <span class="grid size-9 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300"><x-ui.icon :name="$module['icon']" class="size-[18px]" /></span>
                                <p class="flex-1 font-medium">{{ $module['label'] }}</p>
                                @if ($module['core'] ?? false)
                                    <x-ui.badge>Essentiel</x-ui.badge>
                                @else
                                    <button type="button" @if ($canEdit) wire:click="toggleModule('{{ $key }}')" @endif role="switch" aria-checked="{{ in_array($key, $disabled) ? 'false' : 'true' }}" @class(['relative h-6 w-11 rounded-full transition', 'bg-brand-600' => ! in_array($key, $disabled), 'bg-ink-300 dark:bg-white/15' => in_array($key, $disabled)])>
                                        <span @class(['absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition', 'translate-x-5' => ! in_array($key, $disabled)])></span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </x-ui.card>

            {{-- ─── NUMÉROTATION ───────────────────────────────────────────── --}}
            @elseif ($tab === 'numerotation')
                <x-ui.card title="Préfixes de numérotation" subtitle="Format : PRÉFIXE-{{ now()->year }}-00001, remis à zéro chaque année" icon="hashtag">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ([
                            'sales_order' => 'Dossiers de commande', 'manufacturing_order' => 'Ordres de fabrication', 'site' => 'Chantiers', 'project' => 'Projets',
                            'voucher_achat' => 'Bons d’achat', 'voucher_decaissement' => 'Bons de décaissement', 'voucher_sortie' => 'Bons de sortie', 'rental' => 'Locations',
                            'pos_order' => 'Tickets de caisse', 'cash_session' => 'Sessions de caisse', 'expense_claim' => 'Notes de frais', 'leave' => 'Congés',
                            'payroll' => 'Périodes de paie', 'payslip' => 'Bulletins', 'inventory' => 'Inventaires', 'mail_in' => 'Courrier entrant', 'mail_out' => 'Courrier sortant', 'loyalty_card' => 'Cartes de fidélité',
                        ] as $key => $label)
                            <div class="flex items-center gap-3 rounded-xl border border-line p-3">
                                <div class="min-w-0 flex-1"><p class="truncate text-[13px] font-medium">{{ $label }}</p><p class="text-[11.5px] text-fg-subtle">Prochain : {{ $numbering[$key] ?? '' }}-{{ now()->year }}-{{ str_pad((string) ($sequences[$key] ?? 1), 5, '0', STR_PAD_LEFT) }}</p></div>
                                <input wire:model.live.debounce.500ms="numbering.{{ $key }}" class="input h-9 w-24 text-center font-semibold uppercase" @disabled(! $canEdit)>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-[12.5px] text-fg-subtle">Les devis (DEV), factures (FAC), avoirs (AVR) et bons de commande (ACH) suivent la numérotation comptable légale.</p>
                    @error('numbering.*')<p class="field-error">{{ $message }}</p>@enderror
                    @if ($canEdit)<div class="mt-4 flex justify-end"><x-ui.button variant="primary" icon="check" wire:click="saveNumbering">Enregistrer</x-ui.button></div>@endif
                </x-ui.card>

            {{-- ─── RÈGLES MÉTIER ──────────────────────────────────────────── --}}
            @elseif ($tab === 'regles')
                <div class="space-y-6">
                    <x-ui.card title="Commandes" icon="arrow-path-rounded-square">
                        <x-ui.input label="Acompte minimum pour lancer la production / le chantier" type="number" step="5" wire:model="depositRate" suffix="%" class="max-w-sm" hint="Le dossier passe à « Encaissé » dès que ce pourcentage de la facture est payé." />
                    </x-ui.card>
                    <x-ui.card title="Paie — paramètres RDC" icon="wallet">
                        <div class="grid gap-4 md:grid-cols-3">
                            <x-ui.input label="CNSS part ouvrière" type="number" step="0.1" wire:model="payroll.cnss_employee_rate" suffix="%" />
                            <x-ui.input label="CNSS part patronale" type="number" step="0.1" wire:model="payroll.cnss_employer_rate" suffix="%" />
                            <x-ui.input label="INPP" type="number" step="0.1" wire:model="payroll.inpp_rate" suffix="%" />
                            <x-ui.input label="ONEM" type="number" step="0.1" wire:model="payroll.onem_rate" suffix="%" />
                            <x-ui.input label="Plafond IPR" type="number" step="1" wire:model="payroll.ipr_cap_rate" suffix="% du net imposable" />
                            <x-ui.input label="Réduction IPR par personne à charge" type="number" step="0.5" wire:model="payroll.ipr_dependent_reduction" suffix="%" />
                            <x-ui.input label="Majoration heures sup." type="number" step="0.05" wire:model="payroll.overtime_multiplier" suffix="×" />
                            <x-ui.input label="Jours ouvrables / mois" type="number" wire:model="payroll.working_days" />
                            <x-ui.input label="Taux de paie (CDF pour 1 USD)" type="number" wire:model="payroll.exchange_rate" />
                        </div>
                        <p class="label mt-5">Barème mensuel progressif de l’IPR (en CDF)</p>
                        <table class="table min-w-0 rounded-xl border border-line">
                            <thead><tr><th>Tranche jusqu’à (CDF)</th><th class="w-40">Taux</th></tr></thead>
                            <tbody>
                                @foreach ($payroll['ipr_brackets'] ?? [] as $i => $b)
                                    <tr><td><input type="number" wire:model="payroll.ipr_brackets.{{ $i }}.up_to" class="input h-9" placeholder="Au-delà (illimité)" @disabled(! $canEdit)></td><td><input type="number" step="0.5" wire:model="payroll.ipr_brackets.{{ $i }}.rate" class="input h-9 text-right" @disabled(! $canEdit)></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="mt-2 text-[12px] text-fg-subtle">Contrôle : un salaire de 1 000 $ (CNSS 50 $) au taux de {{ number_format($payroll['exchange_rate'] ?? 2847, 0, ',', ' ') }} CDF donne un IPR de <strong>{{ money(app(\App\Services\PayrollService::class)->ipr(950, (float) ($payroll['exchange_rate'] ?? 2847), 0, array_replace(config('erp.payroll'), $payroll))) }}</strong>.</p>
                        @if ($canEdit)<div class="mt-4 flex justify-end"><x-ui.button variant="primary" icon="check" wire:click="saveRules">Enregistrer les règles</x-ui.button></div>@endif
                    </x-ui.card>
                    <x-ui.card title="Taux de change" subtitle="Base USD" icon="currency-dollar">
                        @if ($canEdit)
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row">
                                <select wire:model="rate.quote_currency" class="input sm:w-32"><option value="CDF">CDF</option><option value="EUR">EUR</option></select>
                                <input type="number" step="0.0001" wire:model="rate.rate" class="input sm:w-40" placeholder="Taux">
                                <input type="date" wire:model="rate.effective_date" class="input sm:w-44">
                                <x-ui.button variant="secondary" icon="plus" wire:click="addRate">Ajouter</x-ui.button>
                            </div>
                        @endif
                        <table class="table min-w-0"><thead><tr><th>Date d’effet</th><th>Devise</th><th class="num">1 USD =</th></tr></thead><tbody>@foreach ($rates as $r)<tr><td>{{ $r->effective_date->format('d/m/Y') }}</td><td>{{ $r->quote_currency }}</td><td class="num font-semibold">{{ number_format($r->rate, $r->rate > 10 ? 0 : 4, ',', ' ') }} {{ $r->quote_currency }}</td></tr>@endforeach</tbody></table>
                    </x-ui.card>
                </div>

            {{-- ─── UTILISATEURS ───────────────────────────────────────────── --}}
            @elseif ($tab === 'utilisateurs')
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between gap-2 border-b border-line p-3">
                        <x-ui.search wire:model.live.debounce.300ms="userSearch" placeholder="Nom ou e-mail…" />
                        @if ($canEdit)<x-ui.button variant="accent" icon="user-plus" wire:click="editUser">Nouvel utilisateur</x-ui.button>@endif
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Fiche employé</th><th>Dernière connexion</th><th>Statut</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($users as $u)
                                    <tr>
                                        <td><div class="flex items-center gap-3"><x-ui.avatar :name="$u->name" size="sm" /><div><p class="font-semibold">{{ $u->name }}</p><p class="text-[12px] text-fg-subtle">{{ $u->email }} · {{ $u->job_title }}</p></div></div></td>
                                        <td><x-ui.badge :tone="$u->isMaster() ? 'danger' : 'brand'">{{ $u->roleLabel() }}</x-ui.badge></td>
                                        <td class="text-fg-muted">{{ $u->employee?->matricule ?? '—' }}</td>
                                        <td class="text-fg-muted">{{ $u->last_login_at?->diffForHumans() ?? 'Jamais' }}</td>
                                        <td>@if ($u->is_active)<x-ui.badge tone="success" dot>Actif</x-ui.badge>@else<x-ui.badge dot>Désactivé</x-ui.badge>@endif</td>
                                        <td class="text-right">@if ($canEdit)<x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="editUser({{ $u->id }})" />@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            {{-- ─── RÔLES & DROITS ─────────────────────────────────────────── --}}
            @elseif ($tab === 'roles')
                <x-ui.card title="Rôles & droits" subtitle="Voir = consulter le module · Gérer = créer / modifier · + actions spécifiques (approuver, exécuter…)" icon="shield-check" :padded="false">
                    <div class="flex flex-col gap-2 border-b border-line p-4 sm:flex-row sm:items-center">
                        <select wire:model.live="role" class="input sm:w-64">@foreach ($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                        @if ($canEdit)
                            <div class="flex gap-2 sm:ml-auto"><input wire:model="newRole" class="input sm:w-52" placeholder="Nouveau rôle…"><x-ui.button variant="secondary" icon="plus" wire:click="createRole">Créer</x-ui.button></div>
                        @endif
                    </div>
                    @error('newRole')<p class="field-error px-4">{{ $message }}</p>@enderror
                    @if ($role === 'Super Admin')
                        <p class="p-5 text-[13.5px] text-fg-muted">Le Super Admin dispose de tous les droits sur tous les modules, y compris le Master système.</p>
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead><tr><th>Module</th><th class="text-center">Voir</th><th class="text-center">Gérer</th><th>Actions spécifiques</th></tr></thead>
                                <tbody>
                                    @foreach (config('erp.modules') as $key => $module)
                                        <tr>
                                            <td><span class="flex items-center gap-2.5"><x-ui.icon :name="$module['icon']" class="size-[18px] text-fg-subtle" /><span class="font-medium">{{ $module['label'] }}</span></span></td>
                                            <td class="text-center"><input type="checkbox" wire:model="grants.{{ $key }}__view" class="checkbox" @disabled(! $canEdit)></td>
                                            <td class="text-center"><input type="checkbox" wire:model="grants.{{ $key }}__manage" class="checkbox" @disabled(! $canEdit)></td>
                                            <td><div class="flex flex-wrap gap-3">
                                                @foreach ($module['actions'] ?? [] as $action)
                                                    <label class="flex items-center gap-1.5 text-[12.5px]"><input type="checkbox" wire:model="grants.{{ $key }}__{{ $action }}" class="checkbox" @disabled(! $canEdit)> {{ ['validate' => 'Valider', 'approve' => 'Approuver', 'execute' => 'Exécuter', 'post' => 'Comptabiliser'][$action] ?? $action }}</label>
                                                @endforeach
                                            </div></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($canEdit)<div class="flex justify-end border-t border-line p-4"><x-ui.button variant="primary" icon="check" wire:click="saveGrants">Enregistrer les droits</x-ui.button></div>@endif
                    @endif
                </x-ui.card>

            {{-- ─── AUDIT ──────────────────────────────────────────────────── --}}
            @elseif ($tab === 'audit')
                <div class="card overflow-hidden">
                    <div class="border-b border-line p-3"><x-ui.search wire:model.live.debounce.300ms="auditSearch" placeholder="Type d’objet ou événement…" /></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Date</th><th>Utilisateur</th><th>Événement</th><th>Objet</th><th>Modifications</th><th>IP</th></tr></thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="whitespace-nowrap text-fg-muted">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $log->user?->name ?? 'Système' }}</td>
                                        <td><x-ui.badge :tone="['created' => 'success', 'updated' => 'info', 'deleted' => 'danger'][$log->event] ?? 'neutral'">{{ ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$log->event] ?? $log->event }}</x-ui.badge></td>
                                        <td class="whitespace-nowrap">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                                        <td class="max-w-[360px] truncate text-[12px] text-fg-muted" title="{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}">{{ collect($log->new_values)->except(['created_at', 'updated_at'])->keys()->take(6)->implode(', ') }}</td>
                                        <td class="text-[12px] text-fg-subtle">{{ $log->ip_address }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><x-ui.empty icon="finger-print" title="Journal vide" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($logs->hasPages())<div class="border-t border-line px-4 py-3">{{ $logs->links('components.ui.pagination') }}</div>@endif
                </div>

            {{-- ─── SYSTÈME ────────────────────────────────────────────────── --}}
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    <x-ui.card title="Informations" icon="server-stack">
                        <dl class="divide-y divide-line text-[13.5px]">@foreach ($info as $label => $value)<div class="flex justify-between gap-3 py-2"><dt class="text-fg-muted">{{ $label }}</dt><dd class="text-right font-medium">{{ $value }}</dd></div>@endforeach</dl>
                    </x-ui.card>
                    <x-ui.card title="Maintenance" icon="wrench-screwdriver">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line p-4"><div><p class="font-semibold">Vider les caches</p><p class="text-[12.5px] text-fg-subtle">Configuration, vues, routes et permissions.</p></div>@if ($canEdit)<x-ui.button variant="secondary" icon="arrow-path" wire:click="clearCaches">Vider</x-ui.button>@endif</div>
                            @if (auth()->user()->isMaster())
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-line p-4"><div><p class="font-semibold">Sauvegarde de la base</p><p class="text-[12.5px] text-fg-subtle">Téléchargez une copie complète (SQLite).</p></div><x-ui.button variant="primary" icon="arrow-down-tray" wire:click="backup">Télécharger</x-ui.button></div>
                            @endif
                        </div>
                    </x-ui.card>
                </div>
            @endif
        </div>
    </div>

    <x-ui.drawer wire:model="showUser" :title="$userId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur'" icon="user">
        <div class="space-y-4">
            <x-ui.input label="Nom complet" wire:model="user.name" required />
            <x-ui.input label="E-mail (identifiant)" type="email" wire:model="user.email" required />
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Téléphone" wire:model="user.phone" /><x-ui.input label="Fonction" wire:model="user.job_title" /></div>
            <x-ui.select label="Rôle" wire:model="user.role" :options="$roles->mapWithKeys(fn ($r) => [$r => $r])" />
            <x-ui.select label="Fiche employé liée" wire:model="user.employee_id" placeholder="Aucune" :options="($employees ?? collect())->mapWithKeys(fn ($e) => [$e->id => $e->matricule.' · '.$e->full_name])" />
            <x-ui.input :label="$userId ? 'Nouveau mot de passe (laisser vide pour conserver)' : 'Mot de passe'" type="password" wire:model="user.password" autocomplete="new-password" />
            <x-ui.toggle wire:model="user.is_active" label="Compte actif" description="Un compte désactivé est déconnecté immédiatement." />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveUser">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
