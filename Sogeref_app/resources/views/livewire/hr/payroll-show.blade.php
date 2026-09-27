<div>
    <x-ui.page-header :title="$period->name" :eyebrow="$period->reference.' · '.($period->pay_type === 'daily' ? 'Journaliers' : 'Paie mensuelle')" :back="route('payroll')">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[13px] text-fg-muted">
                <x-ui.status map="payroll" :value="$period->status" />
                <span>{{ $period->start_date->format('d/m/Y') }} → {{ $period->end_date->format('d/m/Y') }}</span>
                <span>· 1 USD = {{ number_format($period->exchange_rate, 0, ',', ' ') }} CDF</span>
                @if ($period->site)<a href="{{ route('sites.show', $period->site) }}" wire:navigate class="hover:text-fg">· {{ $period->site->code }}</a>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('pdf.payroll', ['period' => $period, 'format' => 'etat']) }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="table-cells" class="size-[18px]" /> État de paie</a>
            <a href="{{ route('pdf.payroll', $period) }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="printer" class="size-[18px]" /> Tous les bulletins</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card mb-6 p-5">
        <x-ui.stepper :steps="collect(config('erp.statuses.payroll'))->map(fn ($s) => $s[0])->all()" :current="$period->status" />
        <div class="mt-5 flex flex-col gap-3 rounded-xl border border-sun-300 bg-sun-50 p-4 md:flex-row md:items-center dark:border-sun-400/30 dark:bg-sun-400/8">
            <div class="flex-1 text-[13.5px]">
                @switch($period->status)
                    @case('draft')@case('computed') Vérifiez les bulletins, recalculez si nécessaire (présences, avances), puis faites valider par la direction. La validation génère l’écriture comptable de paie. @break
                    @case('validated') Paie validée : procédez au paiement ({{ money($period->total_net) }}). Le décaissement est enregistré en trésorerie. @break
                    @default Paie payée le {{ $period->paid_at?->format('d/m/Y') }}. Les bulletins sont définitifs.
                @endswitch
            </div>
            <div class="flex flex-wrap gap-2">
                @if (in_array($period->status, ['draft', 'computed']) && auth()->user()->canManage('payroll'))
                    <x-ui.button variant="secondary" icon="arrow-path" wire:click="recompute">Recalculer</x-ui.button>
                @endif
                @if ($period->status === 'computed' && (auth()->user()->isMaster() || auth()->user()->can('payroll.validate')))
                    <x-ui.button variant="primary" icon="check-badge" wire:click="validatePeriod" wire:confirm="Valider définitivement cette paie ?">Valider la paie</x-ui.button>
                @endif
                @if ($period->status === 'validated' && auth()->user()->canManage('payroll'))
                    <select wire:model="accountId" class="input w-60">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ money($a->balance, 'USD', 0) }})</option>@endforeach</select>
                    <x-ui.button variant="success" icon="banknotes" wire:click="pay" wire:confirm="Enregistrer le paiement de {{ money($period->total_net) }} ?">Payer</x-ui.button>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-5">
        <x-ui.stat label="Masse brute" :value="money($slips->sum('gross'))" icon="banknotes" />
        <x-ui.stat label="CNSS ouvrière" :value="money($slips->sum('cnss_employee'))" icon="shield-check" tone="lagoon" />
        <x-ui.stat label="IPR retenu" :value="money($slips->sum('ipr'))" icon="building-library" tone="sun" />
        <x-ui.stat label="Net à payer" :value="money($slips->sum('net'))" icon="wallet" tone="emerald" :hint="$byMethod->map(fn ($v, $k) => (['cash' => 'Espèces', 'bank' => 'Banque', 'mobile_money' => 'Mobile'][$k] ?? $k).' '.money($v, 'USD', 0))->implode(' · ')" />
        <x-ui.stat label="Charges patronales" :value="money($slips->sum('employer_charges'))" icon="building-office" tone="flame" />
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Employé</th><th class="num">{{ $period->pay_type === 'daily' ? 'Jours' : 'Base' }}</th><th class="num">Brut</th><th class="num">CNSS</th><th class="num">IPR</th><th class="num">Autres ret.</th><th class="num">Net</th><th></th></tr></thead>
                <tbody>
                    @forelse ($slips as $s)
                        <tr wire:key="slip-{{ $s->id }}">
                            <td><div class="flex items-center gap-2.5"><x-ui.avatar :name="$s->employee->full_name" size="sm" /><div><p class="font-semibold">{{ $s->employee->full_name }}</p><p class="text-[12px] text-fg-subtle">{{ $s->employee->matricule }} · {{ $s->employee->position?->title }}</p></div></div></td>
                            <td class="num">{{ $period->pay_type === 'daily' ? qty($s->days_worked).' j' : money($s->employee->base_salary) }}</td>
                            <td class="num">{{ money($s->gross) }}</td>
                            <td class="num text-fg-muted">{{ money($s->cnss_employee) }}</td>
                            <td class="num text-fg-muted">{{ money($s->ipr) }}</td>
                            <td class="num text-fg-muted">{{ (float) $s->other_deductions ? money($s->other_deductions) : '—' }}</td>
                            <td class="num font-semibold">{{ money($s->net) }}</td>
                            <td class="text-right whitespace-nowrap">
                                <x-ui.button variant="ghost" size="sm" square icon="eye" wire:click="$set('detail', {{ $s->id }})" title="Détail" />
                                <a href="{{ route('pdf.payslip', $s) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Fiche de paie"><x-ui.icon name="printer" class="size-4" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty icon="wallet" title="Aucun bulletin" :text="$period->pay_type === 'daily' ? 'Aucune présence validée sur la période : validez les listes de présence puis recalculez.' : 'Aucun salarié mensuel actif.'" /></td></tr>
                    @endforelse
                </tbody>
                @if ($slips->isNotEmpty())
                    <tfoot><tr class="bg-surface-2 font-semibold"><td class="px-4 py-3">Total ({{ $slips->count() }})</td><td></td><td class="num px-4">{{ money($slips->sum('gross')) }}</td><td class="num px-4">{{ money($slips->sum('cnss_employee')) }}</td><td class="num px-4">{{ money($slips->sum('ipr')) }}</td><td class="num px-4">{{ money($slips->sum('other_deductions')) }}</td><td class="num px-4">{{ money($slips->sum('net')) }}</td><td></td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    @if ($detailSlip)
        <div x-data="{ open: true }" x-init="$watch('open', v => v || $wire.set('detail', null))">
            <template x-teleport="body">
                <div x-show="open" class="fixed inset-0 z-50 grid place-items-center p-4">
                    <div class="overlay" x-on:click="open = false"></div>
                    <div class="modal max-w-lg" x-transition>
                        <div class="border-b border-line p-5">
                            <p class="eyebrow">Bulletin {{ $detailSlip->reference }}</p>
                            <p class="font-display text-[18px] font-semibold">{{ $detailSlip->employee->full_name }}</p>
                        </div>
                        <table class="table min-w-0">
                            <thead><tr><th>Rubrique</th><th class="num">Gains</th><th class="num">Retenues</th></tr></thead>
                            <tbody>
                                @foreach ($detailSlip->lines as $line)
                                    <tr @class(['text-fg-subtle' => $line['type'] === 'employer'])>
                                        <td>{{ $line['label'] }}</td>
                                        <td class="num text-emerald-600">{{ in_array($line['type'], ['gain', 'allowance']) ? money($line['amount']) : '' }}</td>
                                        <td class="num {{ $line['type'] === 'deduction' ? 'text-flame-600' : '' }}">{{ $line['type'] === 'deduction' ? money($line['amount']) : ($line['type'] === 'employer' ? '('.money($line['amount']).')' : '') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="flex items-center justify-between rounded-b-2xl bg-surface-2 p-5"><span class="font-semibold">Net à payer</span><span class="font-display text-[22px] font-semibold">{{ money($detailSlip->net) }}</span></div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
