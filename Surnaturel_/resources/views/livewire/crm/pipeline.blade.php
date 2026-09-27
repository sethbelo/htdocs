<div class="space-y-6">
    <x-page-header title="Pipeline commercial" subtitle="Glissez les cartes d’une étape à l’autre.">
        <x-slot:actions>
            <flux:switch wire:model.live="mineOnly" label="Mes opportunités" />
            @can('pipeline.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="create">Opportunité</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach ($stages as $stage)
            <div class="w-72 shrink-0" wire:key="stage-{{ $stage->id }}">
                <div class="mb-2 flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" :color="$stage->color">{{ $stage->opportunities->count() }}</flux:badge>
                        <flux:heading size="sm">{{ $stage->name }}</flux:heading>
                    </div>
                    <span class="text-xs text-zinc-500"><x-money :amount="$stage->opportunities->sum('estimated_amount')" /></span>
                </div>
                <div class="min-h-40 space-y-2 rounded-xl bg-zinc-100 p-2 dark:bg-zinc-800/50"
                    wire:sort="move" wire:sort:group="pipeline" wire:sort:group-id="{{ $stage->id }}">
                    @foreach ($stage->opportunities as $opportunity)
                        <div wire:sort:item="{{ $opportunity->id }}" wire:key="opp-{{ $opportunity->id }}" class="panel cursor-grab p-3 text-sm active:cursor-grabbing">
                            <div class="flex items-start justify-between gap-2">
                                <button wire:click="edit({{ $opportunity->id }})" class="text-left font-medium hover:text-indigo-600">{{ $opportunity->title }}</button>
                                <x-money :amount="$opportunity->estimated_amount" class="text-xs font-semibold" />
                            </div>
                            <div class="mt-1 truncate text-zinc-500">{{ $opportunity->contactName() }}</div>
                            <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                                <span>{{ $opportunity->salesRep?->name }}</span>
                                <span>{{ $opportunity->expected_close_date?->format('d/m') }}</span>
                            </div>
                            @if ($opportunity->lost_reason)<div class="mt-1 text-xs text-red-600">{{ $opportunity->lost_reason }}</div>@endif
                        </div>
                    @endforeach
                </div>
                @can('pipeline.create')
                    <flux:button size="xs" variant="ghost" icon="plus" class="mt-1 w-full" wire:click="create({{ $stage->id }})">Ajouter</flux:button>
                @endcan
            </div>
        @endforeach
    </div>

    <flux:modal name="opportunity" class="w-full max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Opportunité' : 'Nouvelle opportunité' }}</flux:heading>
            <flux:input wire:model="form.title" label="Intitulé" required />
            <flux:select wire:model="form.client_id" label="Client existant">
                <flux:select.option value="">— Prospect —</flux:select.option>
                @foreach ($clients as $client)<flux:select.option :value="$client->id">{{ $client->displayName() }}</flux:select.option>@endforeach
            </flux:select>
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="form.prospect_name" label="Prospect" />
                <flux:input wire:model="form.prospect_phone" label="Téléphone" />
                <flux:input wire:model="form.prospect_email" label="E-mail" />
                <flux:input wire:model="form.estimated_amount" type="number" step="0.01" label="Montant estimé" />
                <flux:input wire:model="form.expected_close_date" type="date" label="Clôture prévue" />
                <flux:select wire:model="form.pipeline_stage_id" label="Étape">
                    @foreach ($stages as $stage)<flux:select.option :value="$stage->id">{{ $stage->name }}</flux:select.option>@endforeach
                </flux:select>
            </div>
            <flux:textarea wire:model="form.needs" label="Besoin identifié" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="lost" class="w-full max-w-md">
        <form wire:submit="saveLostReason" class="space-y-4">
            <flux:heading size="lg">Opportunité perdue</flux:heading>
            <flux:input wire:model="lostReason" label="Motif de la perte" required />
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </form>
    </flux:modal>
</div>
