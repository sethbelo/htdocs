<div class="space-y-6">
    <x-page-header title="Fournisseurs" subtitle="Coordonnées, délais, historique des commandes, dettes et paiements.">
        <x-slot:actions>
            <livewire:import-data type="suppliers" :key="'import-suppliers'" />@can('suppliers.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="create">Nouveau fournisseur</flux:button>@endcan</x-slot:actions>
    </x-page-header>

    <div class="panel p-4 space-y-4">
        <div class="flex flex-wrap gap-2.5">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, code, contact, téléphone…" class="max-w-sm" size="sm" />
            <flux:select wire:model.live="city" size="sm" class="max-w-44"><flux:select.option value="">Toutes les villes</flux:select.option>@foreach ($cities as $c)<flux:select.option :value="$c">{{ $c }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="state" size="sm" class="max-w-40"><flux:select.option value="active">Actifs</flux:select.option><flux:select.option value="inactive">Inactifs</flux:select.option><flux:select.option value="">Tous</flux:select.option></flux:select>
        </div>
        <flux:table :paginate="$suppliers">
            <flux:table.columns><flux:table.column>Code</flux:table.column><flux:table.column>Fournisseur</flux:table.column><flux:table.column>Contact</flux:table.column><flux:table.column>Délais</flux:table.column><flux:table.column align="end">Commandes</flux:table.column><flux:table.column align="end">Dette</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @foreach ($suppliers as $supplier)
                    <flux:table.row :key="$supplier->id">
                        <flux:table.cell class="text-zinc-500">{{ $supplier->code }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $supplier->name }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $supplier->contact_name }} {{ $supplier->phone }}<div class="text-xs text-zinc-500">{{ $supplier->email }}</div></flux:table.cell>
                        <flux:table.cell class="text-sm">Livraison {{ $supplier->lead_time_days }} j · paiement {{ $supplier->payment_terms_days }} j</flux:table.cell>
                        <flux:table.cell align="end">{{ $supplier->purchase_orders_count }}</flux:table.cell>
                        <flux:table.cell align="end" @class(['font-medium text-amber-600' => ($supplier->invoiced - $supplier->settled) > 0])><x-money :amount="(float) $supplier->invoiced - (float) $supplier->settled" /></flux:table.cell>
                        <flux:table.cell>@can('suppliers.update')<x-edit-button wire:click="edit({{ $supplier->id }})" />@endcan</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="supplier" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Fournisseur</flux:heading>
            <div class="grid gap-3 md:grid-cols-2">
                <flux:input wire:model="form.code" label="Code" />
                <flux:input wire:model="form.name" label="Raison sociale" />
                <flux:input wire:model="form.contact_name" label="Contact" />
                <flux:input wire:model="form.phone" label="Téléphone" />
                <flux:input wire:model="form.email" label="E-mail" />
                <flux:input wire:model="form.tax_id" label="NIF" />
                <flux:input wire:model="form.address" label="Adresse" />
                <flux:input wire:model="form.city" label="Ville" />
                <flux:input wire:model="form.lead_time_days" type="number" label="Délai de livraison (j)" />
                <flux:input wire:model="form.payment_terms_days" type="number" label="Délai de paiement (j)" />
            </div>
            <flux:textarea wire:model="form.notes" label="Notes" rows="2" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>
</div>
