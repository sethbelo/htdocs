{{-- Modal de clôture : toutes les informations de la session, comptage, synchronisation comptable et versement. --}}
<flux:modal name="close-session" class="w-full max-w-[min(1100px,96vw)]" :dismissible="false">
    @if ($r = $this->closingReport)
        @php($s = $r['session'])
        @php($tolerance = (float) \App\Models\Setting::get('cash.max_difference_without_approval', 5))
        <form wire:submit="closeSession" class="space-y-5"
            x-data="{
                d: $wire.entangle('denominations'),
                counted: $wire.entangle('countedBalance'),
                expected: {{ $r['expected_cash'] }},
                tolerance: {{ $tolerance }},
                get fromNotes() { return Object.entries(this.d || {}).reduce((sum, [value, count]) => sum + parseFloat(value) * (parseInt(count) || 0), 0) },
                get difference() { return (parseFloat(this.counted) || 0) - this.expected },
                money(v) { return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v) },
            }"
            x-effect="if (fromNotes > 0) counted = fromNotes.toFixed(2)">

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="xl">Clôture de caisse — {{ $s->register->code }}</flux:heading>
                    <flux:text>
                        {{ $s->register->name }} · {{ $s->register->site->name }} · entrepôt <strong>{{ $r['warehouse']?->name ?? '—' }}</strong><br>
                        Session {{ $s->number }} · {{ $s->cashier->name }} · ouverte le {{ $s->opened_at->format('d/m/Y à H:i') }} ({{ $r['duration'] }})
                    </flux:text>
                </div>
                <div class="flex gap-2 pe-8">
                    <flux:button size="sm" icon="printer" :href="route('print.cash-session', $s)" target="_blank">Rapport X</flux:button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <x-kpi label="Ventes" :value="$r['sales_count'].' · '.\App\Services\Support\Money::format($r['sales_total'])" icon="shopping-cart" color="emerald" />
                <x-kpi label="Commandes encaissées" :value="$r['orders_count'].' · '.\App\Services\Support\Money::format($r['orders_total'])" icon="clipboard-document-list" color="indigo" />
                <x-kpi label="Panier moyen" :value="\App\Services\Support\Money::format($r['average_basket'])" icon="calculator" color="sky" />
                <x-kpi label="Remises accordées" :value="\App\Services\Support\Money::format($r['discounts'])" icon="receipt-percent" color="amber" />
                <x-kpi label="Décaissements" :value="\App\Services\Support\Money::format($r['disbursements_total'])" icon="arrow-up-right" color="red" />
                <x-kpi label="Articles vendus" :value="(float) $r['items_sold']" :hint="$r['loyalty_points'].' pts fidélité distribués'" icon="cube" color="purple" />
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="panel p-4">
                    <flux:heading size="sm">Encaissements par moyen de paiement</flux:heading>
                    <table class="mt-2 w-full text-sm">
                        <thead class="text-xs text-zinc-500"><tr><th class="text-left font-medium">Moyen</th><th class="text-right font-medium">Ventes</th><th class="text-right font-medium">Commandes</th><th class="text-right font-medium">Total</th></tr></thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($r['by_method'] as $row)
                                <tr><td class="py-1.5">{{ $row['method']->label() }}</td><td class="text-right tabular-nums">{{ number_format($row['sales'], 2, ',', ' ') }}</td><td class="text-right tabular-nums">{{ number_format($row['orders'], 2, ',', ' ') }}</td><td class="text-right font-medium tabular-nums">{{ number_format($row['total'], 2, ',', ' ') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="py-3 text-center text-zinc-500">Aucun encaissement</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot><tr class="font-semibold"><td class="pt-2">Total</td><td colspan="3" class="pt-2 text-right tabular-nums"><x-money :amount="$r['collected_total']" /></td></tr></tfoot>
                    </table>
                    <dl class="mt-3 space-y-1 border-t border-zinc-200 pt-2 text-xs text-zinc-500 dark:border-zinc-700">
                        <div class="flex justify-between"><dt>Monnaie rendue</dt><dd><x-money :amount="$r['change_given']" /></dd></div>
                        <div class="flex justify-between"><dt>Ventes à crédit</dt><dd><x-money :amount="$r['credit_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>TVA collectée</dt><dd><x-money :amount="$r['tax']" /></dd></div>
                        <div class="flex justify-between"><dt>Ventes annulées</dt><dd>{{ $r['voided_count'] }}</dd></div>
                    </dl>
                </div>

                <div class="panel p-4">
                    <flux:heading size="sm">Articles les plus vendus</flux:heading>
                    <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($r['top_products'] as $product)
                            <li class="flex justify-between gap-2 py-1.5"><span class="truncate">{{ $product->description }}</span><span class="whitespace-nowrap tabular-nums text-zinc-500">{{ (float) $product->quantity }} · <x-money :amount="$product->total" class="text-zinc-800 dark:text-zinc-200" /></span></li>
                        @empty
                            <li class="py-3 text-center text-zinc-500">Aucune vente</li>
                        @endforelse
                    </ul>
                    <flux:heading size="sm" class="mt-4">Décaissements</flux:heading>
                    <ul class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">
                        @forelse ($r['disbursements'] as $movement)
                            <li class="flex justify-between py-0.5"><span class="truncate">{{ $movement->created_at->format('H:i') }} · {{ $movement->description }}{{ $movement->authorizer ? ' (validé '.$movement->authorizer->name.')' : '' }}</span><x-money :amount="$movement->amount" /></li>
                        @empty
                            <li class="text-zinc-500">Aucun</li>
                        @endforelse
                    </ul>
                </div>

                <div class="panel p-4">
                    <flux:heading size="sm">Stock — {{ $r['warehouse']?->name ?? 'aucun entrepôt' }}</flux:heading>
                    <ul class="mt-2 space-y-1 text-sm">
                        @forelse ($r['stock_movements'] as $movement)
                            @php($type = $movement->type instanceof \App\Enums\StockMovementType ? $movement->type : \App\Enums\StockMovementType::from($movement->type))
                            <li class="flex justify-between"><span>{{ $type->label() }} ({{ $movement->count }})</span><span @class(['tabular-nums', 'text-red-600' => $movement->quantity < 0, 'text-emerald-600' => $movement->quantity > 0])>{{ (float) $movement->quantity }}</span></li>
                        @empty
                            <li class="text-zinc-500">Aucun mouvement pendant la session</li>
                        @endforelse
                    </ul>
                    @if ($r['low_stock']->isNotEmpty())
                        <flux:heading size="sm" class="mt-3 text-red-600">Réassort à prévoir</flux:heading>
                        <ul class="mt-1 text-xs">
                            @foreach ($r['low_stock'] as $level)
                                <li class="flex justify-between"><span class="truncate">{{ $level->product->name }}</span><span class="text-red-600">{{ (float) $level->quantity }} / {{ (float) $level->product->min_stock }}</span></li>
                            @endforeach
                        </ul>
                    @endif

                    <div @class(['mt-4 rounded-lg p-3 text-sm', 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' => $r['accounting']['synced'], 'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => ! $r['accounting']['synced']])>
                        <div class="flex items-center gap-2 font-medium"><flux:icon.book-open class="size-4" /> Comptabilité générale</div>
                        <div class="mt-1 text-xs">{{ $r['accounting']['entries'] }} écriture(s) passée(s) · {{ \App\Services\Support\Money::format($r['accounting']['debit']) }}</div>
                        <div class="text-xs">{{ $r['accounting']['synced'] ? 'Toutes les opérations sont synchronisées.' : $r['accounting']['missing'].' opération(s) seront comptabilisées à la clôture.' }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="panel p-4">
                    <flux:heading size="sm">Comptage des espèces</flux:heading>
                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($this->denominationValues() as $value)
                            <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-2 py-1.5 text-sm dark:border-zinc-700" wire:key="den-{{ $value }}">
                                <span class="w-16 shrink-0 text-right font-medium tabular-nums">{{ rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',') }}</span>
                                <span class="text-zinc-400">×</span>
                                <input type="number" min="0" inputmode="numeric" x-model="d['{{ $value }}']" class="min-w-0 flex-1 rounded-md border-0 bg-zinc-100 px-2 py-1.5 text-right text-base tabular-nums dark:bg-zinc-800" placeholder="0">
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <flux:input x-model="counted" type="number" step="0.01" label="Espèces comptées (solde réel)" />
                        <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                            <div class="flex justify-between"><span class="text-zinc-500">Fond de caisse</span><x-money :amount="$r['opening']" /></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Espèces théoriques</span><strong><x-money :amount="$r['expected_cash']" /></strong></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Écart</span>
                                <strong :class="Math.abs(difference) < 0.005 ? 'text-emerald-600' : (Math.abs(difference) > tolerance ? 'text-red-600' : 'text-amber-600')" x-text="money(difference)"></strong>
                            </div>
                        </div>
                    </div>
                    <flux:error name="countedBalance" />
                </div>

                <div class="panel space-y-3 p-4">
                    <flux:heading size="sm">Versement & validation</flux:heading>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input wire:model="depositAmount" type="number" step="0.01" label="Montant versé (coffre / banque)" />
                        <flux:select wire:model="depositAccountId" label="Vers le compte">
                            <flux:select.option value="">—</flux:select.option>
                            @foreach ($this->depositAccounts as $account)
                                <flux:select.option :value="$account->id">{{ $account->name }} ({{ $account->type->label() }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div x-show="Math.abs(difference) > tolerance" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-500/10">
                        <flux:text size="sm" class="text-red-700 dark:text-red-300">Écart supérieur à {{ number_format($tolerance, 2, ',', ' ') }} : validation d’un responsable requise.</flux:text>
                        <div class="mt-2 grid grid-cols-2 gap-3">
                            <flux:select wire:model="closingSupervisorId" label="Responsable">
                                <flux:select.option value="">Choisir…</flux:select.option>
                                @foreach ($this->supervisors as $supervisor)
                                    <flux:select.option :value="$supervisor->id">{{ $supervisor->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:input wire:model="closingSupervisorPin" type="password" inputmode="numeric" label="PIN responsable" />
                        </div>
                    </div>
                    <flux:textarea wire:model="closingNotes" label="Observations" rows="2" />
                    <flux:button type="submit" variant="danger" icon="lock-closed" class="w-full" wire:loading.attr="disabled">Clôturer, synchroniser et imprimer le rapport Z</flux:button>
                </div>
            </div>
        </form>
    @endif
</flux:modal>
