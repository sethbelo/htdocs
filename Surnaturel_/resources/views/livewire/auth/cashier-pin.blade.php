<form wire:submit="save" class="space-y-5">
    <div>
        <flux:heading size="lg">PIN caisse</flux:heading>
        <flux:text>Code à 4–6 chiffres demandé à l’ouverture de caisse et pour les validations de responsable.</flux:text>
    </div>
    <flux:input wire:model="password" type="password" label="Mot de passe" viewable required />
    <flux:input wire:model="pin" type="password" inputmode="numeric" label="Nouveau PIN" required />
    <flux:input wire:model="pin_confirmation" type="password" inputmode="numeric" label="Confirmation du PIN" required />
    <div class="flex gap-2">
        <flux:button type="submit" variant="primary" class="flex-1">Enregistrer</flux:button>
        <flux:button :href="route('home')" wire:navigate variant="ghost">Annuler</flux:button>
    </div>
</form>
