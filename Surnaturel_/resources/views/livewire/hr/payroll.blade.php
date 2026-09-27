<div class="space-y-6">
    <x-page-header title="Paie" subtitle="Base + primes + heures sup. + commissions + indemnités − avances − absences − retenues = net à payer">
        <x-slot:actions>
            <flux:select wire:model.live="periodId" size="sm" class="min-w-40">@foreach ($periods as $p)<flux:select.option :value="$p->id">{{ $p->code }} · {{ $p->status->label() }}</flux:select.option>@endforeach</flux:select>
            @can('payroll.create')
                <flux:input wire:model="newPeriod" type="month" size="sm" />
                <flux:button size="sm" icon="plus" wire:click="createPeriod">Ouvrir</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($period)
        <div class="flex flex-wrap items-center gap-2">
            <x-status-badge :status="$period->status" size="lg" />
            <span class="text-sm text-zinc-500">{{ $period->starts_on->format('d/m/Y') }} → {{ $period->ends_on->format('d/m/Y') }}</span>
            <flux:spacer />
            @unless ($locked)
                @can('payroll.update')<flux:button size="sm" icon="calculator" wire:click="compute">Calculer la paie</flux:button>@endcan
                @can('payroll.approve')<flux:button size="sm" variant="primary" icon="check-badge" wire:click="validatePeriod" wire:confirm="Valider et verrouiller la paie de cette période ?">Valider & comptabiliser</flux:button>@endcan
            @endunless
            @can('payroll.export')<flux:button size="sm" icon="arrow-down-tray" wire:click="export">Fichier de paie (Excel)</flux:button>@endcan
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <x-kpi label="Bulletins" :value="$payslips->count()" icon="document-text" />
            <x-kpi label="Masse brute" :value="\App\Services\Support\Money::format($payslips->sum('gross_earnings'))" icon="banknotes" color="indigo" />
            <x-kpi label="Retenues" :value="\App\Services\Support\Money::format($payslips->sum('total_deductions'))" icon="minus-circle" color="amber" />
            <x-kpi label="Net à payer" :value="\App\Services\Support\Money::format($payslips->sum('net_pay'))" icon="wallet" color="emerald" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="panel p-4 xl:col-span-2">
                <flux:heading>État mensuel</flux:heading>
                <flux:table class="mt-2">
                    <flux:table.columns><flux:table.column>Employé</flux:table.column><flux:table.column align="end">Jours</flux:table.column><flux:table.column align="end">HS</flux:table.column><flux:table.column align="end">Brut</flux:table.column><flux:table.column align="end">Retenues</flux:table.column><flux:table.column align="end">Net</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                    <flux:table.rows>
                        @forelse ($payslips as $payslip)
                            <flux:table.row :key="$payslip->id">
                                <flux:table.cell class="font-medium">{{ $payslip->employee->fullName() }}<div class="text-xs text-zinc-500">{{ $payslip->employee->department?->name }}</div></flux:table.cell>
                                <flux:table.cell align="end">{{ (float) $payslip->days_worked }}</flux:table.cell>
                                <flux:table.cell align="end">{{ (float) $payslip->overtime_hours }}</flux:table.cell>
                                <flux:table.cell align="end"><x-money :amount="$payslip->gross_earnings" /></flux:table.cell>
                                <flux:table.cell align="end"><x-money :amount="$payslip->total_deductions" /></flux:table.cell>
                                <flux:table.cell align="end" class="font-semibold"><x-money :amount="$payslip->net_pay" /></flux:table.cell>
                                <flux:table.cell><flux:button size="xs" variant="ghost" icon="document-arrow-down" :href="route('print.payslip', $payslip)" target="_blank" /></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="wallet" title="Paie non calculée" text="Saisissez les éléments variables puis lancez le calcul." /></flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="space-y-6">
                <div class="panel p-4 space-y-3">
                    <flux:heading>Éléments variables</flux:heading>
                    <ul class="max-h-56 divide-y divide-zinc-100 overflow-y-auto text-sm dark:divide-zinc-800">
                        @foreach ($period->items as $item)
                            <li class="flex justify-between py-1.5" wire:key="pi-{{ $item->id }}"><span>{{ $item->employee->fullName() }} · {{ $item->label }}</span><span @class(['text-emerald-600' => in_array($item->type, \App\Services\Hr\PayrollService::EARNINGS), 'text-red-600' => ! in_array($item->type, \App\Services\Hr\PayrollService::EARNINGS)])><x-money :amount="$item->amount" /></span></li>
                        @endforeach
                    </ul>
                    @if (! $locked)
                        @can('payroll.update')
                            <form wire:submit="addItem" class="grid grid-cols-2 gap-2">
                                <flux:select wire:model="item.employee_id" class="col-span-2"><flux:select.option value="">Employé…</flux:select.option>@foreach ($employees as $e)<flux:select.option :value="$e->id">{{ $e->first_name }} {{ $e->last_name }}</flux:select.option>@endforeach</flux:select>
                                <flux:select wire:model="item.type">@foreach ($types as $v => $l)@if (! in_array($v, ['advance', 'loan_repayment']))<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endif @endforeach</flux:select>
                                <flux:input wire:model="item.amount" type="number" step="0.01" placeholder="Montant" />
                                <flux:input wire:model="item.label" placeholder="Libellé" class="col-span-2" />
                                <flux:button type="submit" size="sm" class="col-span-2">Ajouter</flux:button>
                            </form>
                        @endcan
                    @endif
                </div>
                <div class="panel p-4 space-y-3">
                    <flux:heading>Avances & prêts en cours</flux:heading>
                    <ul class="text-sm">
                        @foreach ($loans as $loan)<li class="flex justify-between py-1" wire:key="ln-{{ $loan->id }}"><span>{{ $loan->employee->fullName() }} · {{ $loan->type === 'advance' ? 'Avance' : 'Prêt' }}</span><span>reste <x-money :amount="$loan->balance" /></span></li>@endforeach
                    </ul>
                    @can('payroll.update')
                        <form wire:submit="grantLoan" class="grid grid-cols-2 gap-2">
                            <flux:select wire:model="loan.employee_id" class="col-span-2"><flux:select.option value="">Employé…</flux:select.option>@foreach ($employees as $e)<flux:select.option :value="$e->id">{{ $e->first_name }} {{ $e->last_name }}</flux:select.option>@endforeach</flux:select>
                            <flux:select wire:model="loan.type"><flux:select.option value="advance">Avance</flux:select.option><flux:select.option value="loan">Prêt</flux:select.option></flux:select>
                            <flux:input wire:model="loan.principal" type="number" step="0.01" placeholder="Montant" />
                            <flux:input wire:model="loan.installment" type="number" step="0.01" placeholder="Mensualité" class="col-span-2" />
                            <flux:button type="submit" size="sm" class="col-span-2">Accorder</flux:button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    @else
        <div class="panel"><x-empty-state icon="wallet" title="Aucune période de paie" text="Ouvrez une première période." /></div>
    @endif
</div>
