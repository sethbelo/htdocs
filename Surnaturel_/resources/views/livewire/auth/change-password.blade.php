<form wire:submit="save" class="space-y-5">
    <div>
        <flux:heading size="lg">Changer le mot de passe</flux:heading>
        @if ($forced)
            <flux:text>Pour des raisons de sécurité, définissez un nouveau mot de passe avant de continuer.</flux:text>
        @endif
    </div>

    <flux:input wire:model="current_password" type="password" label="Mot de passe actuel" viewable required />
    <flux:input wire:model="password" type="password" label="Nouveau mot de passe" description="8 caractères minimum, lettres et chiffres." viewable required />
    <flux:input wire:model="password_confirmation" type="password" label="Confirmation" viewable required />

    <div class="flex gap-2">
        <flux:button type="submit" variant="primary" class="flex-1">Enregistrer</flux:button>
        @unless ($forced)
            <flux:button :href="route('home')" wire:navigate variant="ghost">Annuler</flux:button>
        @endunless
    </div>
</form>
