<div class="px-5">
    <flux:table
        :paginate="$movementPaginator ?? null"
        pagination:class="pb-4 pt-4"
        pagination:scroll-to="body"
        class="w-full"
    >
        <flux:table.columns>
            <flux:table.row>
                <flux:table.column><x-table-heading icon="calendar-days">Date et heure</x-table-heading></flux:table.column>
                <flux:table.column><x-table-heading icon="cube">Article</x-table-heading></flux:table.column>
                <flux:table.column class="hidden md:table-cell"><x-table-heading icon="arrows-right-left">Opération</x-table-heading></flux:table.column>
                <flux:table.column align="end"><x-table-heading icon="calculator">Quantité</x-table-heading></flux:table.column>
                <flux:table.column align="end" class="hidden lg:table-cell"><x-table-heading icon="chart-bar">Stock avant → après</x-table-heading></flux:table.column>
                <flux:table.column class="hidden xl:table-cell"><x-table-heading icon="hashtag">Référence</x-table-heading></flux:table.column>
                <flux:table.column class="hidden xl:table-cell"><x-table-heading icon="user">Agent</x-table-heading></flux:table.column>
            </flux:table.row>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($movementRows as $movement)
                <flux:table.row wire:key="movement-{{ $movement->id }}">
                    <flux:table.cell>
                        <span class="whitespace-nowrap font-medium text-slate-700 dark:text-slate-200">{{ $movement->created_at->format('d/m/Y') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500">{{ $movement->created_at->format('H:i') }}</span>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">
                        <p class="max-w-52 truncate font-semibold">{{ $movement->product?->name ?? 'Article supprimé' }}</p>
                        <p class="max-w-52 truncate text-xs font-normal text-slate-500">{{ $movement->description ?: 'Sans observation' }}</p>
                    </flux:table.cell>
                    <flux:table.cell class="hidden md:table-cell">
                        <flux:badge :color="$movement->type === 'in' ? 'emerald' : 'rose'">{{ $reasonLabels[$movement->reason] ?? ucfirst($movement->reason) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <strong class="whitespace-nowrap {{ $movement->type === 'in' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $movement->type === 'in' ? '+' : '−' }}{{ number_format($movement->quantity, 3, ',', ' ') }} {{ $movement->product?->unitOfMeasure?->symbol }}</strong>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="hidden lg:table-cell">
                        <span class="whitespace-nowrap font-medium text-slate-700 dark:text-slate-200">{{ $movement->stock_before !== null ? number_format($movement->stock_before, 3, ',', ' ') : '—' }} <span class="px-1 text-slate-400">→</span> {{ $movement->stock_after !== null ? number_format($movement->stock_after, 3, ',', ' ') : '—' }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="hidden xl:table-cell"><span class="whitespace-nowrap font-mono text-xs">{{ $movement->reference ?: '—' }}</span></flux:table.cell>
                    <flux:table.cell class="hidden xl:table-cell"><span class="whitespace-nowrap">{{ $movement->user?->name ?? 'Système' }}</span></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <div class="py-6"><flux:callout icon="clock" heading="Aucun mouvement" text="Aucune opération ne correspond aux critères sélectionnés." /></div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
