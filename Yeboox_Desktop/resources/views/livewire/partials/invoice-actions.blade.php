<div class="flex justify-end">
<flux:dropdown position="bottom" align="end">
    <flux:button icon="ellipsis-horizontal" variant="filled" class="yb-action-trigger" aria-label="Actions pour {{ $invoice->number }}" />
    <flux:menu>
        <flux:menu.item wire:click="openViewModal({{ $invoice->id }})" icon="eye" class="text-blue-700! dark:text-blue-300!">Voir et imprimer</flux:menu.item>
        @if($invoice->status === 'draft')
            <flux:menu.item wire:click="openEditModal({{ $invoice->id }})" icon="pencil-square" class="text-amber-700! dark:text-amber-300!">Modifier</flux:menu.item>
            <flux:menu.item wire:click="postInvoice({{ $invoice->id }})" icon="check-circle" class="text-emerald-700! dark:text-emerald-300!">Valider la facture</flux:menu.item>
        @elseif($balance > 0)
            <flux:menu.item wire:click="openPaymentModal({{ $invoice->id }})" icon="banknotes" class="text-emerald-700! dark:text-emerald-300!">Enregistrer un paiement</flux:menu.item>
            <flux:menu.item wire:click="openCreditModal({{ $invoice->id }})" icon="arrow-uturn-left" class="text-rose-700! dark:text-rose-300!">Créer un avoir</flux:menu.item>
        @endif
        <flux:menu.item wire:click="duplicateInvoice({{ $invoice->id }})" icon="document-duplicate">Dupliquer en brouillon</flux:menu.item>
        @if($invoice->status === 'draft')
            <flux:menu.separator />
            <flux:menu.item wire:click="askDeleteInvoice({{ $invoice->id }})" icon="trash" variant="danger">Supprimer</flux:menu.item>
        @endif
    </flux:menu>
</flux:dropdown>
</div>
