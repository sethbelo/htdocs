@php($money = fn ($v) => \App\Services\Support\Money::format($v))
<div class="space-y-6">
    <x-page-header title="Visa des fiches de paie" eyebrow="Direction générale" subtitle="Contrôlez et visez la fiche de paie de chaque agent. La paie ne peut être validée et payée qu’après votre visa.">
        <x-slot:actions>
            <flux:select wire:model.live="periodId" size="sm" class="min-w-48">
                @foreach ($periods as $p)<flux:select.option :value="$p->id">{{ $p->starts_on->translatedFormat('F Y') }} · {{ $p->status->label() }}</flux:select.option>@endforeach
            </flux:select>
        </x-slot:actions>
    </x-page-header>

    @if ($this->period)
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <x-kpi label="Fiches" :value="$this->stats['total']" icon="document-text" />
            <x-kpi label="À viser" :value="$this->stats['pending']" icon="clock" color="amber" />
            <x-kpi label="Visées" :value="$this->stats['approved']" icon="check-badge" color="emerald" />
            <x-kpi label="Rejetées" :value="$this->stats['rejected']" icon="x-circle" color="red" />
            <x-kpi label="Masse brute" :value="$money($this->stats['gross'])" icon="banknotes" color="indigo" />
            <x-kpi label="Net à payer" :value="$money($this->stats['net'])" icon="wallet" color="purple" />
        </div>

        @if ($this->stats['total'] > 0)
            <div class="panel p-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">Avancement du visa</span>
                    <span class="text-fg-muted">{{ $this->stats['approved'] }} / {{ $this->stats['total'] }}</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/8">
                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ round($this->stats['approved'] / max(1, $this->stats['total']) * 100) }}%"></div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-5">
            <div class="panel xl:col-span-3">
                <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
                    <div class="segmented">
                        @foreach (['pending' => 'À viser', 'approved' => 'Visées', 'rejected' => 'Rejetées', 'all' => 'Toutes'] as $key => $label)
                            <button type="button" wire:click="$set('filter', '{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <flux:spacer />
                    @if ($period = $this->period)
                        @unless (in_array($period->status->value, ['validated', 'paid']))
                            <flux:button size="sm" wire:click="toggleAll" icon="check">Tout sélectionner</flux:button>
                            <flux:button size="sm" variant="primary" icon="check-badge" wire:click="approveSelected" :disabled="count($selected) === 0" wire:confirm="Apposer votre visa sur les fiches sélectionnées ?">Viser la sélection ({{ count($selected) }})</flux:button>
                        @endunless
                    @endif
                </div>
                <div class="divide-y divide-line">
                    @forelse ($this->payslips as $slip)
                        <div @class(['flex items-center gap-3 px-3 py-2.5 transition', 'bg-brand-50/60 dark:bg-white/4' => $detailId === $slip->id]) wire:key="ps-{{ $slip->id }}">
                            @if ($slip->dg_status !== 'approved' && ! in_array($this->period->status->value, ['validated', 'paid']))
                                <input type="checkbox" wire:model.live="selected" value="{{ $slip->id }}" class="size-4 rounded border-zinc-300 text-brand-600">
                            @else
                                <span class="size-4"></span>
                            @endif
                            <button type="button" wire:click="$set('detailId', {{ $slip->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                                <span class="avatar size-9 bg-brand-100 text-[12px] text-brand-700 dark:bg-white/10 dark:text-white">{{ $slip->employee->initials() }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $slip->employee->fullName() }}</span>
                                    <span class="block truncate text-xs text-fg-subtle">{{ $slip->employee->matricule }} · {{ $slip->employee->department?->name ?? '—' }}</span>
                                </span>
                                <span class="text-right">
                                    <span class="block text-sm font-semibold tabular-nums">{{ $money($slip->net_pay) }}</span>
                                    <span class="block text-xs text-fg-subtle">brut {{ $money($slip->gross_earnings) }}</span>
                                </span>
                            </button>
                            @switch($slip->dg_status)
                                @case('approved')<flux:badge size="sm" color="emerald" icon="check-badge">Visée</flux:badge>@break
                                @case('rejected')<flux:badge size="sm" color="red" icon="x-circle">Rejetée</flux:badge>@break
                                @default<flux:badge size="sm" color="amber" icon="clock">À viser</flux:badge>
                            @endswitch
                        </div>
                    @empty
                        <x-empty-state icon="document-check" title="Aucune fiche dans cette vue" text="Les fiches apparaissent ici après le calcul de la paie par les RH." />
                    @endforelse
                </div>
            </div>

            <div class="xl:col-span-2">
                @if ($detail)
                    <div class="panel space-y-4 p-5 xl:sticky xl:top-20">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="eyebrow">{{ $detail->number }}</p>
                                <flux:heading size="lg">{{ $detail->employee->fullName() }}</flux:heading>
                                <flux:text>{{ $detail->employee->job_title ?? '—' }} · {{ (float) $detail->days_worked }} j · HS {{ (float) $detail->overtime_hours }} h</flux:text>
                            </div>
                            <flux:button size="sm" icon="document-arrow-down" :href="route('print.payslip', $detail)" target="_blank">PDF</flux:button>
                        </div>
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-xs text-fg-subtle uppercase"><th class="py-1">Rubrique</th><th class="py-1 text-right">Gains</th><th class="py-1 text-right">Retenues</th></tr></thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($detail->lines as $line)
                                    <tr><td class="py-1.5">{{ $line['label'] }}</td><td class="py-1.5 text-right text-emerald-600 tabular-nums">{{ $line['direction'] === 'earning' ? $money($line['amount']) : '' }}</td><td class="py-1.5 text-right text-carmin-600 tabular-nums">{{ $line['direction'] === 'deduction' ? $money($line['amount']) : '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="flex items-center justify-between rounded-xl bg-zinc-900 px-4 py-3 text-white dark:bg-white/10">
                            <span class="text-sm font-semibold uppercase">Net à payer</span>
                            <span class="font-display text-xl font-bold tabular-nums">{{ $money($detail->net_pay) }}</span>
                        </div>
                        @if ($detail->dg_comment)
                            <flux:callout variant="danger" icon="exclamation-triangle" heading="Motif du rejet" :text="$detail->dg_comment" />
                        @endif
                        @if ($detail->dg_status === 'approved')
                            <flux:callout variant="success" icon="check-badge" :heading="'Visée le '.$detail->dg_approved_at?->format('d/m/Y à H:i')" />
                        @elseif (! in_array($this->period->status->value, ['validated', 'paid']))
                            <div class="flex gap-2">
                                <flux:button class="flex-1" variant="primary" icon="check-badge" wire:click="approve({{ $detail->id }})">Viser</flux:button>
                                <flux:button class="flex-1" variant="danger" icon="x-mark" wire:click="openReject({{ $detail->id }})">Rejeter</flux:button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="panel"><x-empty-state icon="cursor-arrow-rays" title="Sélectionnez une fiche" text="Le détail des rubriques s’affiche ici." /></div>
                @endif
            </div>
        </div>
    @else
        <div class="panel"><x-empty-state icon="wallet" title="Aucune période de paie" /></div>
    @endif

    <flux:modal name="reject-payslip" class="w-full max-w-md">
        <form wire:submit="reject" class="space-y-4">
            <flux:heading size="lg">Rejeter la fiche de paie</flux:heading>
            <flux:text>La fiche est renvoyée aux ressources humaines pour correction puis nouveau calcul.</flux:text>
            <flux:textarea wire:model="comment" label="Motif" rows="3" required />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="danger">Rejeter</flux:button></div>
        </form>
    </flux:modal>
</div>
