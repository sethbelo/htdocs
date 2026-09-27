@props(['user'])

<flux:menu class="w-60">
    <div class="px-2 py-1.5">
        <flux:heading size="sm">{{ $user->name }}</flux:heading>
        <flux:text size="sm">{{ $user->getRoleNames()->map(fn ($r) => config("erp.roles.$r.label", $r))->implode(', ') }}</flux:text>
    </div>
    <flux:menu.separator />
    <flux:menu.item icon="key" :href="route('password.change')" wire:navigate>Changer le mot de passe</flux:menu.item>
    <flux:menu.item icon="finger-print" :href="route('profile.pin')" wire:navigate>PIN caisse</flux:menu.item>
    <flux:menu.separator />
    <flux:menu.radio.group x-data variant="segmented" x-model="$flux.appearance">
        <flux:menu.radio value="light" icon="sun">Clair</flux:menu.radio>
        <flux:menu.radio value="dark" icon="moon">Sombre</flux:menu.radio>
        <flux:menu.radio value="system" icon="computer-desktop">Auto</flux:menu.radio>
    </flux:menu.radio.group>
    <flux:menu.separator />
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Déconnexion</flux:menu.item>
    </form>
</flux:menu>
