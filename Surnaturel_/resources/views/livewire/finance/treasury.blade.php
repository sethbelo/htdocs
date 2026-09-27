<div class="space-y-6">
    <x-page-header title="Trésorerie" subtitle="Caisses, banques, Mobile Money, transferts internes et prévisions.">
        <x-slot:actions>
            @can('treasury.create')<flux:modal.trigger name="account"><flux:button size="sm" icon="plus">Compte</flux:button></flux:modal.trigger>@endcan
            @can('treasury.disburse')<flux:modal.trigger name="transfer"><flux:button size="sm" variant="primary" icon="arrows-right-left">Transfert</flux:button></flux:modal.trigger>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($accounts as $account)
            <div class="kpi-card" wire:key="ta-{{ $account->id }}">
                <div class="flex items-center justify-between">
                    <flux:badge size="sm" :color="['cash' => 'emerald', 'bank' => 'indigo', 'mobile_money' => 'amber'][$account->type->value]">{{ $account->type->label() }}</flux:badge>
                    <span class="text-xs text-zinc-500">{{ $account->code }}</span>
                </div>
                <div class="mt-2 font-medium">{{ $account->name }}</div>
                <div class="text-xl font-semibold tabular-nums"><x-money :amount="$account->balance" /></div>
                <div class="text-xs text-zinc-500">{{ $account->site?->name ?? 'Tous sièges' }} {{ $account->bank_name ? '· '.$account->bank_name : '' }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel p-4 xl:col-span-2">
            <flux:heading>Flux des 30 derniers jours</flux:heading>
            <div class="mt-3 h-64" x-data="chart(@js($flowChart))"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="panel p-4 space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading>Prévisions</flux:heading>
                <span class="text-sm">Solde projeté : <strong><x-money :amount="$projected" /></strong></span>
            </div>
            <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @forelse ($forecasts as $forecast)
                    <li class="flex justify-between py-1.5" wire:key="fc-{{ $forecast->id }}"><span>{{ $forecast->expected_on->format('d/m') }} · {{ $forecast->label }}</span><span @class(['text-emerald-600' => $forecast->direction === 'in', 'text-red-600' => $forecast->direction === 'out'])>{{ $forecast->direction === 'in' ? '+' : '−' }}<x-money :amount="$forecast->amount" /></span></li>
                @empty
                    <li class="py-2 text-zinc-500">Aucune prévision.</li>
                @endforelse
            </ul>
            @can('treasury.create')
                <form wire:submit="saveForecast" class="grid grid-cols-2 gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:input wire:model="forecast.label" placeholder="Libellé" class="col-span-2" />
                    <flux:select wire:model="forecast.direction"><flux:select.option value="in">Entrée</flux:select.option><flux:select.option value="out">Sortie</flux:select.option></flux:select>
                    <flux:input wire:model="forecast.amount" type="number" step="0.01" placeholder="Montant" />
                    <flux:input wire:model="forecast.expected_on" type="date" class="col-span-2" />
                    <flux:button type="submit" size="sm" class="col-span-2">Ajouter</flux:button>
                </form>
            @endcan
        </div>
    </div>

    <div class="panel p-4">
        <flux:heading>Transferts récents</flux:heading>
        <flux:table class="mt-2">
            <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Date</flux:table.column><flux:table.column>De → vers</flux:table.column><flux:table.column>Référence</flux:table.column><flux:table.column align="end">Montant</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @foreach ($transfers as $transfer)
                    <flux:table.row :key="$transfer->id">
                        <flux:table.cell>{{ $transfer->number }}</flux:table.cell>
                        <flux:table.cell>{{ $transfer->transferred_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $transfer->from->name }} → {{ $transfer->to->name }}</flux:table.cell>
                        <flux:table.cell>{{ $transfer->reference }} {{ $transfer->description }}</flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$transfer->amount" /></flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="transfer" class="w-full max-w-lg">
        <form wire:submit="saveTransfer" class="space-y-4">
            <flux:heading size="lg">Transfert interne</flux:heading>
            <flux:select wire:model="transfer.from" label="De"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($accounts as $a)<flux:select.option :value="$a->id">{{ $a->name }} ({{ \App\Services\Support\Money::format($a->balance) }})</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="transfer.to" label="Vers"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($accounts as $a)<flux:select.option :value="$a->id">{{ $a->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="transfer.amount" type="number" step="0.01" label="Montant" />
            <flux:input wire:model="transfer.reference" label="Référence (bordereau…)" />
            <flux:input wire:model="transfer.description" label="Libellé" />
            <flux:button type="submit" variant="primary" class="w-full">Transférer</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="account" class="w-full max-w-lg">
        <form wire:submit="saveAccount" class="space-y-4">
            <flux:heading size="lg">Nouveau compte de trésorerie</flux:heading>
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="account.code" label="Code" />
                <flux:select wire:model="account.type" label="Type">@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            </div>
            <flux:input wire:model="account.name" label="Nom" />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="account.bank_name" label="Banque / opérateur" />
                <flux:input wire:model="account.account_number" label="N° de compte" />
            </div>
            <flux:select wire:model="account.account_id" label="Compte comptable (classe 5)"><flux:select.option value="">—</flux:select.option>@foreach ($ledgerAccounts as $l)<flux:select.option :value="$l->id">{{ $l->code }} — {{ $l->name }}</flux:select.option>@endforeach</flux:select>
            <flux:button type="submit" variant="primary" class="w-full">Créer</flux:button>
        </form>
    </flux:modal>
</div>
