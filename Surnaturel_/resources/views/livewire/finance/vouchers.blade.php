<div class="space-y-6">
    <x-page-header title="Bons administratifs" subtitle="Bons de décaissement, de caisse, de sortie, de transfert, d’achat, de production… avec circuit de validation.">
        <x-slot:actions>@can('vouchers.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="create">Nouveau bon</flux:button>@endcan</x-slot:actions>
    </x-page-header>
    <div class="panel p-4 space-y-4">
        <flux:select wire:model.live="type" class="max-w-xs"><flux:select.option value="">Tous les bons</flux:select.option>@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
        <flux:table :paginate="$vouchers">
            <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Type</flux:table.column><flux:table.column>Objet</flux:table.column><flux:table.column>Bénéficiaire</flux:table.column><flux:table.column>Créateur</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Montant</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($vouchers as $voucher)
                    <flux:table.row :key="$voucher->id">
                        <flux:table.cell class="font-medium">{{ $voucher->number }}</flux:table.cell>
                        <flux:table.cell>{{ $voucher->type->label() }}</flux:table.cell>
                        <flux:table.cell class="max-w-56 truncate">{{ $voucher->description }}</flux:table.cell>
                        <flux:table.cell>{{ $voucher->beneficiary }}</flux:table.cell>
                        <flux:table.cell>{{ $voucher->creator?->name }} <div class="text-xs text-zinc-500">{{ $voucher->created_at->format('d/m/Y') }}</div></flux:table.cell>
                        <flux:table.cell><x-status-badge :status="$voucher->status" /> @if ($voucher->approvalRequest?->currentStep)<span class="text-xs text-zinc-500">· {{ $voucher->approvalRequest->currentStep->name }}</span>@endif</flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$voucher->amount" /></flux:table.cell>
                        <flux:table.cell><flux:button size="xs" variant="ghost" icon="printer" :href="route('print.voucher', $voucher)" target="_blank" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="document-duplicate" title="Aucun bon" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="voucher" class="w-full max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Nouveau bon</flux:heading>
            <flux:select wire:model="form.type" label="Type">@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="form.beneficiary" label="Bénéficiaire" />
                <flux:input wire:model="form.amount" type="number" step="0.01" label="Montant" />
            </div>
            <flux:textarea wire:model="form.description" label="Objet" rows="2" required />
            @foreach ($lines as $i => $line)
                <div class="grid grid-cols-6 gap-2" wire:key="vl-{{ $i }}">
                    <flux:input wire:model="lines.{{ $i }}.label" placeholder="Désignation" class="col-span-4" />
                    <flux:input wire:model="lines.{{ $i }}.quantity" type="number" step="any" placeholder="Qté" />
                    <flux:input wire:model="lines.{{ $i }}.amount" type="number" step="0.01" placeholder="Montant" />
                </div>
            @endforeach
            <flux:button type="button" size="sm" icon="plus" wire:click="addLine">Ligne détaillée</flux:button>
            <flux:button type="submit" variant="primary" class="w-full">Émettre et soumettre</flux:button>
        </form>
    </flux:modal>
</div>
