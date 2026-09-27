<div>
    <x-ui.page-header title="Paie" eyebrow="Ressources humaines" subtitle="Salariés mensuels et journaliers de chantier — CNSS 5 %, IPR au barème progressif RDC, avances et charges patronales.">
        <x-slot:actions>
            @if (auth()->user()->canManage('payroll'))
                <x-ui.button variant="secondary" icon="wrench" wire:click="create('daily')">Paie des journaliers</x-ui.button>
                <x-ui.button variant="accent" icon="plus" wire:click="create('monthly')">Paie mensuelle</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat :label="'Net versé '.now()->year" :value="money($stats['net'], 'USD', 0)" icon="banknotes" />
        <x-ui.stat :label="'Charges patronales '.now()->year" :value="money($stats['employer'], 'USD', 0)" icon="building-library" tone="lagoon" />
        <x-ui.stat label="Périodes à valider" :value="$stats['pending']" icon="clock" tone="sun" />
        <x-ui.stat label="Validées, à payer" :value="money($stats['toPay'], 'USD', 0)" icon="wallet" tone="flame" />
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Période</th><th>Type</th><th>Dates</th><th class="num">Bulletins</th><th class="num">Brut</th><th class="num">Net à payer</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @forelse ($periods as $p)
                        <tr>
                            <td><a href="{{ route('payroll.show', $p) }}" wire:navigate class="link">{{ $p->reference }}</a><p class="max-w-[320px] truncate text-[12px] text-fg-subtle">{{ $p->name }}</p></td>
                            <td><x-ui.badge :tone="$p->pay_type === 'daily' ? 'warning' : 'brand'">{{ $p->pay_type === 'daily' ? 'Journaliers' : 'Mensuelle' }}</x-ui.badge></td>
                            <td class="whitespace-nowrap text-fg-muted">{{ $p->start_date->format('d/m') }} → {{ $p->end_date->format('d/m/Y') }}</td>
                            <td class="num">{{ $p->payslips_count }}</td>
                            <td class="num">{{ money($p->total_gross) }}</td>
                            <td class="num font-semibold">{{ money($p->total_net) }}</td>
                            <td><x-ui.status map="payroll" :value="$p->status" /></td>
                            <td class="text-right"><x-ui.button variant="ghost" size="sm" square icon="chevron-right" :href="route('payroll.show', $p)" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty icon="wallet" title="Aucune période de paie" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-ui.drawer wire:model="showForm" :title="$payType === 'daily' ? 'Paie des journaliers' : 'Paie mensuelle'" icon="calculator">
        <div class="space-y-4">
            <div class="segmented">
                <button type="button" wire:click="$set('payType', 'monthly')" aria-pressed="{{ $payType === 'monthly' ? 'true' : 'false' }}">Salariés mensuels</button>
                <button type="button" wire:click="$set('payType', 'daily')" aria-pressed="{{ $payType === 'daily' ? 'true' : 'false' }}">Journaliers</button>
            </div>
            @if ($payType === 'monthly')
                <x-ui.input label="Mois" type="month" wire:model="month" />
                @error('month')<p class="field-error">{{ $message }}</p>@enderror
            @else
                <x-ui.select label="Chantier" wire:model="siteId" placeholder="Tous les chantiers" :options="$sites->mapWithKeys(fn ($s) => [$s->id => $s->code.' · '.$s->name])" />
                <div class="grid grid-cols-2 gap-3"><x-ui.input label="Du" type="date" wire:model="from" /><x-ui.input label="Au" type="date" wire:model="to" /></div>
                <p class="rounded-xl bg-sun-50 p-3 text-[12.5px] text-sun-800 dark:bg-sun-400/10 dark:text-sun-300">Seules les listes de présence <strong>validées</strong> sont prises en compte.</p>
            @endif
            <x-ui.input label="Taux de change (CDF pour 1 USD)" type="number" step="1" wire:model="exchangeRate" hint="Sert à appliquer le barème IPR exprimé en francs congolais." />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="calculator" wire:click="save">Calculer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
