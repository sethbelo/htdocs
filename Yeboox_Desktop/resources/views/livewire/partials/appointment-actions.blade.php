<div class="flex justify-end">
<flux:dropdown position="bottom" align="end">
    <flux:button variant="filled" icon="ellipsis-horizontal" class="yb-action-trigger" aria-label="Actions du rendez-vous de {{ $appointment->customer->name }}" />
    <flux:menu>
        @if($appointment->status === 'pending')
            <flux:menu.item wire:click="updateStatus({{ $appointment->id }}, 'confirmed')" icon="check-circle" class="text-emerald-700! dark:text-emerald-300!">Confirmer</flux:menu.item>
        @endif
        @if(in_array($appointment->status, ['pending', 'confirmed']))
            <flux:menu.item wire:click="updateStatus({{ $appointment->id }}, 'completed')" icon="check-badge" class="text-blue-700! dark:text-blue-300!">Marquer terminé</flux:menu.item>
            <flux:menu.item wire:click="updateStatus({{ $appointment->id }}, 'cancelled')" icon="x-circle" class="text-amber-700! dark:text-amber-300!">Annuler</flux:menu.item>
        @endif
        <flux:menu.separator />
        <flux:menu.item wire:click="deleteAppointment({{ $appointment->id }})" wire:confirm="Supprimer définitivement ce rendez-vous ?" icon="trash" variant="danger">Supprimer</flux:menu.item>
    </flux:menu>
</flux:dropdown>
</div>
