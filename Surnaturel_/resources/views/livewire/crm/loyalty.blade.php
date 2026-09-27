<div class="space-y-6">
    <x-page-header title="Fidélisation" subtitle="Points, niveaux, récompenses, promotions et cartes clients." />

    <div class="grid gap-4 md:grid-cols-4">
        @foreach ($tiers as $tier)
            <div class="kpi-card" wire:key="t-{{ $tier->id }}">
                <flux:badge :color="$tier->color">{{ $tier->name }}</flux:badge>
                <div class="mt-2 text-2xl font-semibold">{{ $tier->accounts_count }}</div>
                <div class="text-xs text-zinc-500">dès {{ $tier->min_points }} pts · remise {{ (float) $tier->discount_rate }} % · ×{{ (float) $tier->points_multiplier }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel p-4 space-y-3">
            <flux:heading>Règles</flux:heading>
            <form wire:submit="saveRules" class="space-y-3">
                <flux:input wire:model="rules.amount_per_point" type="number" step="any" label="Montant dépensé pour 1 point" />
                <flux:input wire:model="rules.point_value" type="number" step="any" label="Valeur d’un point (remise)" />
                <flux:input wire:model="rules.card_validity_years" type="number" label="Validité de la carte (années, facultatif)" />
                @can('loyalty.update')<flux:button type="submit" size="sm">Enregistrer</flux:button>@endcan
            </form>
        </div>

        <div class="panel p-4 space-y-3">
            <flux:heading>Promotions</flux:heading>
            <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @foreach ($promotions as $promo)
                    <li class="flex items-center justify-between py-2" wire:key="pr-{{ $promo->id }}">
                        <div>
                            <div class="font-medium">{{ $promo->name }} @if ($promo->code)<flux:badge size="sm">{{ $promo->code }}</flux:badge>@endif</div>
                            <div class="text-xs text-zinc-500">{{ $promo->type === \App\Enums\PromotionType::Percentage ? (float) $promo->value.' %' : \App\Services\Support\Money::format($promo->value) }} · {{ $promo->starts_at?->format('d/m') }} → {{ $promo->ends_at?->format('d/m') }}</div>
                        </div>
                        @can('loyalty.update')<flux:switch :checked="$promo->is_active" wire:click="togglePromotion({{ $promo->id }})" />@endcan
                    </li>
                @endforeach
            </ul>
            @can('loyalty.create')
                <form wire:submit="addPromotion" class="grid grid-cols-2 gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:input wire:model="promotion.name" placeholder="Nom" class="col-span-2" />
                    <flux:input wire:model="promotion.code" placeholder="Code (facultatif)" />
                    <flux:select wire:model="promotion.type">@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="promotion.value" type="number" step="any" placeholder="Valeur" />
                    <flux:input wire:model="promotion.min_amount" type="number" step="any" placeholder="Achat minimum" />
                    <flux:input wire:model="promotion.starts_at" type="date" />
                    <flux:input wire:model="promotion.ends_at" type="date" />
                    <flux:button type="submit" size="sm" icon="plus" class="col-span-2">Créer la promotion</flux:button>
                </form>
            @endcan
        </div>

        <div class="panel p-4 space-y-3">
            <flux:heading>Récompenses</flux:heading>
            <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @foreach ($rewards as $reward)
                    <li class="flex justify-between py-2" wire:key="rw-{{ $reward->id }}"><span>{{ $reward->name }}</span><flux:badge size="sm" color="purple">{{ $reward->points_cost }} pts</flux:badge></li>
                @endforeach
            </ul>
            @can('loyalty.create')
                <form wire:submit="addReward" class="grid grid-cols-3 gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:input wire:model="reward.name" placeholder="Récompense" class="col-span-2" />
                    <flux:input wire:model="reward.points_cost" type="number" placeholder="Points" />
                    <flux:button type="submit" size="sm" icon="plus" class="col-span-3">Ajouter</flux:button>
                </form>
            @endcan
            <flux:heading class="pt-2">Meilleurs clients</flux:heading>
            <ul class="text-sm">
                @foreach ($topAccounts as $account)
                    <li class="flex justify-between py-1" wire:key="ta-{{ $account->id }}"><flux:link :href="route('clients.show', $account->client)" wire:navigate>{{ $account->client->displayName() }}</flux:link><span>{{ $account->lifetime_points }} pts</span></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
