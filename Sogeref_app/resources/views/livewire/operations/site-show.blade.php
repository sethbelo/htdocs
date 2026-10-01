<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$site->name" :eyebrow="$site->code.' · Chantier'" :back="route('sites')">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-fg-muted">
                <x-ui.status map="site" :value="$site->status" />
                @if ($site->isDelayed())<x-ui.badge tone="danger" icon="exclamation-triangle">En retard</x-ui.badge>@endif
                <span class="flex items-center gap-1"><x-ui.icon name="map-pin" class="size-4" />{{ $site->address }}{{ $site->city ? ', '.$site->city : '' }}</span>
                <span class="flex items-center gap-1"><x-ui.icon name="user" class="size-4" />{{ $site->contact?->display_name ?? 'Chantier interne' }}</span>
                @if ($site->salesOrder)<a href="{{ route('orders.show', $site->salesOrder) }}" wire:navigate class="flex items-center gap-1 hover:text-fg"><x-ui.icon name="arrow-path-rounded-square" class="size-4" />{{ $site->salesOrder->number }}</a>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="printer" :href="route('pdf.site', $site)" :navigate="false" target="_blank">Rapport PDF</x-ui.button>
            <x-ui.button variant="primary" icon="clipboard-document-check" :href="route('attendance', ['chantier' => $site])">Faire l’appel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. INDICATEURS ═══════════════════════════════════════════════════ --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-ui.stat label="Avancement" :value="$site->progress.' %'" icon="chart-pie" tone="sun" />
        <x-ui.stat label="Budget" :value="money($site->budget, 'USD', 0)" icon="banknotes" />
        <x-ui.stat label="Main-d’œuvre pointée" :value="money($labour, 'USD', 0)" icon="users" tone="lagoon" :hint="$sheets->where('status', 'validated')->count().' journées validées'" />
        <x-ui.stat label="Bons exécutés" :value="money($spent, 'USD', 0)" icon="ticket" tone="flame" />
        <x-ui.stat label="Équipe" :value="$site->members->whereNull('pivot.left_at')->count()" icon="user-group" tone="emerald" hint="personnes affectées" />
    </div>

    <div class="card">
        <div class="px-4">
            <x-ui.tabs :current="$tab" :tabs="[
                'apercu' => ['label' => 'Aperçu', 'icon' => 'squares-2x2'],
                'equipe' => ['label' => 'Équipe', 'icon' => 'user-group', 'count' => $site->members->count()],
                'presences' => ['label' => 'Présences', 'icon' => 'clipboard-document-check', 'count' => $sheets->count()],
                'materiel' => ['label' => 'Matériel', 'icon' => 'wrench', 'count' => $assignments->whereNull('returned_at')->count()],
                'rapports' => ['label' => 'Rapports', 'icon' => 'document-text', 'count' => $reports->count()],
                'bons' => ['label' => 'Bons', 'icon' => 'ticket', 'count' => $vouchers->count()],
                'paie' => ['label' => 'Paie', 'icon' => 'wallet'],
                'evaluation' => ['label' => 'Évaluation', 'icon' => 'star'],
            ]" />
        </div>

        <div class="p-4 sm:p-5">
            {{-- ─── APERÇU ─────────────────────────────────────────────────── --}}
            @if ($tab === 'apercu')
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-5">
                        <div>
                            <p class="label">Description des travaux</p>
                            <p class="text-[14px] text-fg-muted">{{ $site->description ?: 'Aucune description.' }}</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-4 text-[13.5px]">
                            <div><dt class="text-fg-subtle">Responsable</dt><dd class="font-semibold">{{ $site->manager?->full_name ?? '—' }}</dd></div>
                            <div><dt class="text-fg-subtle">Projet</dt><dd>@if ($site->project)<a href="{{ route('projects.show', $site->project) }}" wire:navigate class="link">{{ $site->project->code }}</a>@else — @endif</dd></div>
                            <div><dt class="text-fg-subtle">Début</dt><dd class="font-semibold">{{ $site->start_date?->format('d/m/Y') ?? '—' }}</dd></div>
                            <div><dt class="text-fg-subtle">Fin prévue</dt><dd @class(['font-semibold', 'text-flame-600' => $site->isDelayed()])>{{ $site->planned_end_date?->format('d/m/Y') ?? '—' }}</dd></div>
                            <div><dt class="text-fg-subtle">Consommé / budget</dt><dd class="font-semibold">{{ (float) $site->budget ? round(($labour + $spent) / (float) $site->budget * 100) : 0 }} %</dd></div>
                            <div><dt class="text-fg-subtle">Réception</dt><dd class="font-semibold">{{ $site->actual_end_date?->format('d/m/Y') ?? '—' }}</dd></div>
                        </dl>
                        @if ($canManage)
                            <div class="rounded-xl border border-line p-4">
                                <p class="mb-3 font-semibold">Mettre à jour l’état du chantier</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-ui.select label="Statut" wire:model="status" :options="collect(config('erp.statuses.site'))->map(fn ($s) => $s[0])" />
                                    <div><label class="label">Avancement : {{ $progress }} %</label><input type="range" min="0" max="100" step="5" wire:model.live="progress" class="mt-3 w-full accent-brand-600"></div>
                                </div>
                                <x-ui.button variant="primary" size="sm" icon="check" class="mt-3" wire:click="saveStatus">Enregistrer</x-ui.button>
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="label">Derniers rapports</p>
                        <ol class="space-y-3">
                            @forelse ($reports->take(4) as $report)
                                <li class="rounded-xl border border-line p-3.5">
                                    <div class="flex items-center justify-between text-[12px] text-fg-subtle"><span class="font-semibold text-fg">{{ $report->report_date->translatedFormat('l j M') }}</span><span>{{ \App\Models\SiteReport::WEATHER[$report->weather] ?? '' }} · {{ $report->workforce }} ouvriers · {{ $report->progress }} %</span></div>
                                    <p class="mt-1.5 text-[13.5px]">{{ $report->work_done }}</p>
                                    @if ($report->issues)<p class="mt-1 text-[12.5px] text-flame-600">⚠ {{ $report->issues }}</p>@endif
                                </li>
                            @empty
                                <li><x-ui.empty icon="document-text" title="Aucun rapport" /></li>
                            @endforelse
                        </ol>
                    </div>
                </div>

            {{-- ─── ÉQUIPE ─────────────────────────────────────────────────── --}}
            @elseif ($tab === 'equipe')
                @if ($canManage)
                    <div class="mb-4 grid gap-2 rounded-xl bg-surface-2 p-3 sm:grid-cols-[1fr_200px_140px_auto]">
                        <select wire:model="memberId" class="input"><option value="">Affecter un employé…</option>@foreach ($availableEmployees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} — {{ $e->position?->title }} ({{ $e->isDaily() ? money($e->daily_rate).'/j' : 'mensuel' }})</option>@endforeach</select>
                        <input wire:model="memberRole" class="input" placeholder="Rôle sur le chantier">
                        <input wire:model="memberRate" type="number" step="0.5" class="input" placeholder="Taux/j (option)">
                        <x-ui.button variant="primary" icon="plus" wire:click="addMember">Affecter</x-ui.button>
                    </div>
                @endif
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Employé</th><th>Rôle</th><th>Catégorie</th><th class="num">Taux / jour</th><th class="num">Jours pointés</th><th>Période</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($site->members as $m)
                                <tr @class(['opacity-60' => $m->pivot->left_at])>
                                    <td><div class="flex items-center gap-2.5"><x-ui.avatar :name="$m->full_name" size="sm" /><div><a href="{{ route('employees.show', $m) }}" wire:navigate class="font-semibold hover:text-brand-600">{{ $m->full_name }}</a><p class="text-[12px] text-fg-subtle">{{ $m->matricule }}</p></div></div></td>
                                    <td>{{ $m->pivot->role }}</td>
                                    <td><x-ui.badge :tone="$m->isDaily() ? 'warning' : 'brand'">{{ \App\Models\Employee::CATEGORIES[$m->category] }}</x-ui.badge></td>
                                    <td class="num">{{ $m->isDaily() ? money($m->pivot->daily_rate ?: $m->daily_rate) : '—' }}</td>
                                    <td class="num font-semibold">{{ qty($daysWorked[$m->id] ?? 0) }}</td>
                                    <td class="text-[12.5px] whitespace-nowrap text-fg-muted">{{ \Illuminate\Support\Carbon::parse($m->pivot->joined_at)->format('d/m/Y') }} → {{ $m->pivot->left_at ? \Illuminate\Support\Carbon::parse($m->pivot->left_at)->format('d/m/Y') : 'en cours' }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        @if ($canManage)
                                            @if (! $m->pivot->left_at)<x-ui.button variant="ghost" size="sm" icon="arrow-right-start-on-rectangle" wire:click="releaseMember({{ $m->id }})" wire:confirm="Mettre fin à l’affectation de {{ $m->full_name }} ?">Libérer</x-ui.button>@endif
                                            <x-ui.button variant="ghost" size="sm" square icon="trash" wire:click="removeMember({{ $m->id }})" wire:confirm="Retirer {{ $m->full_name }} de l’équipe ?" aria-label="Retirer" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            {{-- ─── PRÉSENCES ──────────────────────────────────────────────── --}}
            @elseif ($tab === 'presences')
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th class="num">Présents</th><th class="num">Absents</th><th class="num">Montant journée</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($sheets as $sheet)
                                <tr>
                                    <td class="font-semibold">{{ ucfirst($sheet->work_date->translatedFormat('l j M Y')) }}</td>
                                    <td class="num">{{ $sheet->entries->whereIn('status', ['present', 'late', 'half'])->count() }}</td>
                                    <td class="num text-flame-600">{{ $sheet->entries->where('status', 'absent')->count() }}</td>
                                    <td class="num font-semibold">{{ money($sheet->entries->sum(fn ($e) => $e->dayFraction() * (float) $e->daily_rate)) }}</td>
                                    <td>@if ($sheet->status === 'validated')<x-ui.badge tone="success" dot>Validée</x-ui.badge>@else<x-ui.badge tone="warning" dot>À valider</x-ui.badge>@endif</td>
                                    <td class="text-right whitespace-nowrap">
                                        <x-ui.button variant="ghost" size="sm" icon="pencil-square" :href="route('attendance', ['chantier' => $site, 'date' => $sheet->work_date->toDateString()])">Ouvrir</x-ui.button>
                                        <a href="{{ route('pdf.attendance', $sheet) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon"><x-ui.icon name="printer" class="size-4" /></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="clipboard-document-check" title="Aucune liste de présence" text="Faites l’appel quotidien pour calculer la paie des journaliers." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- ─── MATÉRIEL ───────────────────────────────────────────────── --}}
            @elseif ($tab === 'materiel')
                @if ($canManage)
                    <div class="mb-4 flex flex-col gap-2 rounded-xl bg-surface-2 p-3 sm:flex-row">
                        <select wire:model="equipmentId" class="input flex-1"><option value="">Affecter un équipement disponible…</option>@foreach ($availableEquipment as $eq)<option value="{{ $eq->id }}">{{ $eq->code }} · {{ $eq->name }}</option>@endforeach</select>
                        <x-ui.button variant="primary" icon="plus" wire:click="assignEquipment">Affecter</x-ui.button>
                    </div>
                @endif
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Équipement</th><th>Catégorie</th><th>Affecté le</th><th>Retour</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($assignments as $a)
                                <tr>
                                    <td><p class="font-semibold">{{ $a->equipment->name }}</p><p class="text-[12px] text-fg-subtle">{{ $a->equipment->code }} · N° série {{ $a->equipment->serial_number }}</p></td>
                                    <td class="text-fg-muted">{{ $a->equipment->category }}</td>
                                    <td class="whitespace-nowrap">{{ $a->assigned_at->format('d/m/Y') }}</td>
                                    <td>@if ($a->returned_at){{ $a->returned_at->format('d/m/Y') }}@else<x-ui.badge tone="info" dot>Sur site</x-ui.badge>@endif</td>
                                    <td class="text-right">@if ($canManage && ! $a->returned_at)<x-ui.button variant="ghost" size="sm" icon="arrow-uturn-left" wire:click="returnEquipment({{ $a->id }})">Retour au dépôt</x-ui.button>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-ui.empty icon="wrench" title="Aucun matériel affecté" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- ─── RAPPORTS ───────────────────────────────────────────────── --}}
            @elseif ($tab === 'rapports')
                @if ($canManage)<div class="mb-4 flex justify-end"><x-ui.button variant="accent" icon="plus" wire:click="newReport">Rapport du jour</x-ui.button></div>@endif
                <ol class="relative space-y-4 border-l-2 border-line pl-6">
                    @forelse ($reports as $report)
                        <li class="relative">
                            <span class="absolute top-1 -left-[33px] size-4 rounded-full bg-sun-400 ring-4 ring-surface"></span>
                            <div class="rounded-xl border border-line p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-semibold">{{ ucfirst($report->report_date->translatedFormat('l j F Y')) }}</p>
                                    <div class="flex flex-wrap gap-1.5 text-[12px]"><x-ui.badge>{{ \App\Models\SiteReport::WEATHER[$report->weather] ?? '—' }}</x-ui.badge><x-ui.badge tone="info">{{ $report->workforce }} ouvriers</x-ui.badge><x-ui.badge tone="warning">{{ $report->progress }} %</x-ui.badge></div>
                                </div>
                                <p class="mt-2 text-[14px]">{{ $report->work_done }}</p>
                                @if ($report->issues)<p class="mt-2 rounded-lg bg-flame-50 px-3 py-2 text-[13px] text-flame-700 dark:bg-flame-400/10 dark:text-flame-300"><strong>Difficultés :</strong> {{ $report->issues }}</p>@endif
                                @if ($report->next_steps)<p class="mt-2 text-[13px] text-fg-muted"><strong>Suite :</strong> {{ $report->next_steps }}</p>@endif
                                @if (! empty($report->photos))
                                    <div class="mt-3 flex gap-2 overflow-x-auto">@foreach ($report->photos as $photo)<a href="{{ asset('storage/'.$photo) }}" target="_blank"><img src="{{ asset('storage/'.$photo) }}" class="h-20 w-28 rounded-lg object-cover" alt=""></a>@endforeach</div>
                                @endif
                                <p class="mt-2 text-[12px] text-fg-subtle">Par {{ $report->author?->name }}</p>
                            </div>
                        </li>
                    @empty
                        <li><x-ui.empty icon="document-text" title="Aucun rapport journalier" /></li>
                    @endforelse
                </ol>

            {{-- ─── BONS ───────────────────────────────────────────────────── --}}
            @elseif ($tab === 'bons')
                @if (auth()->user()->canManage('vouchers'))<div class="mb-4 flex justify-end"><x-ui.button variant="accent" icon="plus" :href="route('vouchers', ['chantier' => $site, 'nouveau' => 1])">Nouveau bon</x-ui.button></div>@endif
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>N°</th><th>Type</th><th>Date</th><th>Objet</th><th>Demandeur</th><th>Statut</th><th class="num">Montant</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($vouchers as $v)
                                <tr>
                                    <td class="font-semibold">{{ $v->number }}</td>
                                    <td>{{ config('erp.voucher_types.'.$v->type.'.short') }}</td>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $v->voucher_date->format('d/m/Y') }}</td>
                                    <td class="max-w-[280px] truncate">{{ $v->purpose }}</td>
                                    <td class="text-fg-muted">{{ $v->requester?->name }}</td>
                                    <td><x-ui.status map="voucher" :value="$v->status" /></td>
                                    <td class="num font-semibold">{{ money($v->amount) }}</td>
                                    <td class="text-right"><a href="{{ route('pdf.voucher', $v) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon"><x-ui.icon name="printer" class="size-4" /></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8"><x-ui.empty icon="ticket" title="Aucun bon pour ce chantier" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- ─── PAIE ───────────────────────────────────────────────────── --}}
            @elseif ($tab === 'paie')
                @if (auth()->user()->canManage('payroll'))
                    <div class="mb-5 rounded-xl border border-sun-300 bg-sun-50 p-4 dark:border-sun-400/30 dark:bg-sun-400/8">
                        <p class="font-semibold">Préparer la paie des journaliers</p>
                        <p class="text-[13px] text-fg-muted">Seules les listes de présence <strong>validées</strong> de la période sont prises en compte (jours, demi-journées, heures supplémentaires).</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                            <x-ui.input label="Du" type="date" wire:model="payFrom" />
                            <x-ui.input label="Au" type="date" wire:model="payTo" />
                            <x-ui.button variant="primary" icon="calculator" wire:click="preparePayroll">Calculer la paie</x-ui.button>
                        </div>
                    </div>
                @endif
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Période</th><th>Dates</th><th>Statut</th><th class="num">Net à payer</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($periods as $p)
                                <tr>
                                    <td class="font-semibold">{{ $p->reference }}</td>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $p->start_date->format('d/m') }} → {{ $p->end_date->format('d/m/Y') }}</td>
                                    <td><x-ui.status map="payroll" :value="$p->status" /></td>
                                    <td class="num font-semibold">{{ money($p->total_net) }}</td>
                                    <td class="text-right"><x-ui.button variant="ghost" size="sm" icon="chevron-right" :href="route('payroll.show', $p)">Ouvrir</x-ui.button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-ui.empty icon="wallet" title="Aucune paie calculée pour ce chantier" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- ─── ÉVALUATION ─────────────────────────────────────────────── --}}
            @else
                @if ($canManage)<div class="mb-4 flex justify-end"><x-ui.button variant="accent" icon="star" wire:click="$set('showEvaluation', true)">Nouvelle évaluation</x-ui.button></div>@endif
                <div class="grid gap-4 md:grid-cols-2">
                    @forelse ($evaluations as $evaluation)
                        <div class="rounded-xl border border-line p-4">
                            <div class="flex items-center justify-between">
                                <div><p class="font-semibold">{{ $evaluation->evaluated_at->format('d/m/Y') }}</p><p class="text-[12px] text-fg-subtle">par {{ $evaluation->evaluator?->name }}</p></div>
                                <p class="font-display text-[26px] font-semibold text-brand-600 dark:text-lagoon-300">{{ number_format($evaluation->score, 1, ',', ' ') }}<span class="text-[14px] text-fg-subtle">/5</span></p>
                            </div>
                            <ul class="mt-3 space-y-2">
                                @foreach (\App\Models\SiteEvaluation::CRITERIA as $key => $label)
                                    @php $note = (int) ($evaluation->scores[$key] ?? 0); @endphp
                                    <li class="flex items-center justify-between gap-3 text-[13px]"><span class="text-fg-muted">{{ $label }}</span><span class="flex gap-0.5">@for ($i = 1; $i <= 5; $i++)<x-ui.icon name="star" variant="s" @class(['size-4', 'text-sun-400' => $i <= $note, 'text-ink-200 dark:text-white/10' => $i > $note]) />@endfor</span></li>
                                @endforeach
                            </ul>
                            @if ($evaluation->comment)<p class="mt-3 border-t border-line pt-3 text-[13px] text-fg-muted">{{ $evaluation->comment }}</p>@endif
                        </div>
                    @empty
                        <div class="md:col-span-2"><x-ui.empty icon="star" title="Aucune évaluation" text="Évaluez la qualité, les délais, la sécurité, le budget et la propreté du chantier." /></div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ 3. FENÊTRES ══════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showReport" title="Rapport journalier" :subtitle="$site->name" icon="document-text" size="lg">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.input label="Date" type="date" wire:model="reportDate" />
            <x-ui.select label="Météo" wire:model="weather" :options="\App\Models\SiteReport::WEATHER" />
            <x-ui.input label="Effectif présent" type="number" wire:model="workforce" />
            <div class="sm:col-span-3"><label class="label">Avancement global : {{ $reportProgress }} %</label><input type="range" min="0" max="100" step="5" wire:model.live="reportProgress" class="w-full accent-brand-600"></div>
            <div class="sm:col-span-3"><x-ui.textarea label="Travaux réalisés" wire:model="workDone" rows="3" required /></div>
            <div class="sm:col-span-3"><x-ui.textarea label="Difficultés / incidents" wire:model="issues" rows="2" /></div>
            <div class="sm:col-span-3"><x-ui.textarea label="Prochaines étapes" wire:model="nextSteps" rows="2" /></div>
            <div class="sm:col-span-3">
                <label class="label">Photos du chantier</label>
                <input type="file" wire:model="photos" multiple accept="image/*" capture="environment" class="block w-full text-[13px] file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700">
                <p wire:loading wire:target="photos" class="mt-1 text-[12px] text-fg-subtle">Téléversement…</p>
                @error('photos.*')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveReport">Enregistrer le rapport</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showEvaluation" title="Évaluation du chantier" icon="star">
        <div class="space-y-4">
            @foreach (\App\Models\SiteEvaluation::CRITERIA as $key => $label)
                <div>
                    <p class="label">{{ $label }}</p>
                    <div class="flex gap-1.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" wire:click="$set('scores.{{ $key }}', {{ $i }})" @class(['grid size-11 place-items-center rounded-xl border text-[14px] font-bold transition', 'border-sun-400 bg-sun-400 text-brand-900' => ($scores[$key] ?? 0) >= $i, 'border-line text-fg-subtle' => ($scores[$key] ?? 0) < $i])>{{ $i }}</button>
                        @endfor
                    </div>
                </div>
            @endforeach
            <x-ui.textarea label="Commentaire" wire:model="evaluationComment" rows="3" />
            <p class="rounded-xl bg-surface-2 px-4 py-3 text-[13px]">Note globale : <strong>{{ number_format(array_sum($scores) / max(1, count($scores)), 1, ',', ' ') }} / 5</strong></p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveEvaluation">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
