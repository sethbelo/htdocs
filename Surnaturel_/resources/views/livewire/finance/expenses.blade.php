<div class="space-y-6">
    <x-page-header title="Notes de frais" subtitle="Employé → Responsable → Finance → Paiement, justificatifs obligatoires.">
        <x-slot:actions>
            @can('expenses.view')<flux:switch wire:model.live="mineOnly" label="Mes notes" />@endcan
            <flux:modal.trigger name="expense"><flux:button size="sm" variant="primary" icon="plus">Nouvelle note</flux:button></flux:modal.trigger>
        </x-slot:actions>
    </x-page-header>

    <div class="panel p-4">
        <flux:table :paginate="$claims">
            <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Employé</flux:table.column><flux:table.column>Objet</flux:table.column><flux:table.column>Date</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column>Justificatifs</flux:table.column><flux:table.column align="end">Montant</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($claims as $claim)
                    <flux:table.row :key="$claim->id">
                        <flux:table.cell class="font-medium">{{ $claim->number }}</flux:table.cell>
                        <flux:table.cell>{{ $claim->user->name }}</flux:table.cell>
                        <flux:table.cell class="max-w-56 truncate">{{ $claim->title }}</flux:table.cell>
                        <flux:table.cell>{{ $claim->expense_date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell><x-status-badge :status="$claim->status" /> @if ($claim->approvalRequest?->currentStep)<span class="text-xs text-zinc-500">· {{ $claim->approvalRequest->currentStep->name }}</span>@endif</flux:table.cell>
                        <flux:table.cell>
                            @foreach ($claim->receipts as $receipt)
                                @if ($receipt->latestVersion)<flux:button size="xs" variant="ghost" icon="paper-clip" :href="app(\App\Services\Documents\DocumentService::class)->temporaryUrl($receipt->latestVersion)" wire:key="rc-{{ $receipt->id }}" />@endif
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$claim->amount" /></flux:table.cell>
                        <flux:table.cell>@if ($claim->status->value === 'finance_approved' && (auth()->user()->can('expenses.disburse') || auth()->user()->can('treasury.disburse')))<flux:button size="xs" icon="banknotes" wire:click="openPay({{ $claim->id }})">Payer</flux:button>@endif</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="receipt-percent" title="Aucune note de frais" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="expense" class="w-full max-w-lg">
        <form wire:submit="submit" class="space-y-4">
            <flux:heading size="lg">Nouvelle note de frais</flux:heading>
            <flux:input wire:model="form.title" label="Objet" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="form.amount" type="number" step="0.01" label="Montant" />
                <flux:input wire:model="form.expense_date" type="date" label="Date de la dépense" />
            </div>
            <flux:textarea wire:model="form.description" label="Détail" rows="2" />
            <flux:input type="file" wire:model="receipts" multiple label="Justificatifs (PDF, photos)" />
            <flux:error name="receipts.*" />
            <flux:button type="submit" variant="primary" class="w-full">Soumettre</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="pay" class="w-full max-w-md">
        <form wire:submit="pay" class="space-y-4">
            <flux:heading size="lg">Paiement de la note de frais</flux:heading>
            <flux:select wire:model="payment.treasury_account_id" label="Compte"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($treasuryAccounts as $t)<flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="payment.method" label="Moyen">@foreach ($methods as $v => $l)@if ($v !== 'credit')<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endif @endforeach</flux:select>
            <flux:input wire:model="payment.reference" label="Référence" />
            <flux:button type="submit" variant="primary" class="w-full">Payer</flux:button>
        </form>
    </flux:modal>
</div>
