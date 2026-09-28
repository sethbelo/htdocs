<div class="space-y-6">
    <x-page-header title="Sessions de caisse" subtitle="Ouvertures, clôtures, soldes théoriques et réels, écarts." />

    <div class="panel p-4 space-y-4">
        <div class="flex flex-wrap gap-3">
            <flux:select wire:model.live="status" class="max-w-48">
                <flux:select.option value="">Toutes</flux:select.option>
                <flux:select.option value="open">Ouvertes</flux:select.option>
                <flux:select.option value="closed">Clôturées</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="registerFilter" class="max-w-48">
                <flux:select.option value="">Toutes les caisses</flux:select.option>
                @foreach ($registers as $register)<flux:select.option :value="$register->id">{{ $register->code }} — {{ $register->name }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model.live="siteId" class="max-w-xs">
                <flux:select.option value="">Tous les sièges</flux:select.option>
                @foreach ($this->accessibleSites() as $site)
                    <flux:select.option :value="$site->id">{{ $site->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <x-filter-bar class="border-0 p-0 shadow-none" placeholder="N° de session, caissier, caisse…" period-label="Ouverture" />
        <flux:table :paginate="$sessions">
            <flux:table.columns>
                <flux:table.column>Session</flux:table.column>
                <flux:table.column>Caisse</flux:table.column>
                <flux:table.column>Caissier</flux:table.column>
                <flux:table.column>Ouverture</flux:table.column>
                <flux:table.column>Clôture</flux:table.column>
                <flux:table.column align="end">Théorique</flux:table.column>
                <flux:table.column align="end">Réel</flux:table.column>
                <flux:table.column align="end">Écart</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($sessions as $session)
                    <flux:table.row :key="$session->id">
                        <flux:table.cell><button wire:click="show({{ $session->id }})" class="font-medium text-brand-600">{{ $session->number }}</button></flux:table.cell>
                        <flux:table.cell>{{ $session->register->code }} <span class="text-zinc-500">· {{ $session->register->site->name }}</span></flux:table.cell>
                        <flux:table.cell>{{ $session->cashier->name }}</flux:table.cell>
                        <flux:table.cell>{{ $session->opened_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{!! $session->closed_at?->format('d/m/Y H:i') ?? '<span class="text-emerald-600">En cours</span>' !!}</flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$session->expected_balance ?? $session->theoreticalCashBalance()" /></flux:table.cell>
                        <flux:table.cell align="end">{!! $session->counted_balance !== null ? e(\App\Services\Support\Money::format($session->counted_balance)) : '—' !!}</flux:table.cell>
                        <flux:table.cell align="end" @class(['font-semibold', 'text-red-600' => (float) $session->difference < 0, 'text-emerald-600' => (float) $session->difference > 0])>{{ $session->difference !== null ? \App\Services\Support\Money::format($session->difference) : '—' }}</flux:table.cell>
                        <flux:table.cell><flux:button size="xs" icon="printer" variant="ghost" :href="route('print.cash-session', $session)" target="_blank" /></flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="session-detail" class="w-full max-w-2xl">
        @if ($selected)
            <flux:heading size="lg">Mouvements — {{ $selected->number }}</flux:heading>
            <flux:table class="mt-3">
                <flux:table.columns>
                    <flux:table.column>Heure</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Moyen</flux:table.column>
                    <flux:table.column>Libellé</flux:table.column>
                    <flux:table.column align="end">Montant</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($selected->movements as $movement)
                        <flux:table.row :key="$movement->id">
                            <flux:table.cell>{{ $movement->created_at->format('H:i') }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->type->label() }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->payment_method->label() }}</flux:table.cell>
                            <flux:table.cell class="max-w-56 truncate">{{ $movement->description }} @if ($movement->authorizer)<flux:badge size="sm">Validé {{ $movement->authorizer->name }}</flux:badge>@endif</flux:table.cell>
                            <flux:table.cell align="end" @class(['text-red-600' => $movement->direction === 'out'])>{{ $movement->direction === 'out' ? '−' : '+' }} <x-money :amount="$movement->amount" /></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:modal>
</div>
