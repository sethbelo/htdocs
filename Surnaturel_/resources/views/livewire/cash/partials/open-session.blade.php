<div class="mx-auto max-w-lg panel p-6 space-y-4">
    <div class="flex items-center gap-3">
        <div class="rounded-lg bg-brand-50 p-2 text-brand-600 dark:bg-brand-500/10"><flux:icon.lock-closed /></div>
        <div>
            <flux:heading size="lg">Ouvrir une caisse</flux:heading>
            <flux:text>Aucune session ouverte à votre nom.</flux:text>
        </div>
    </div>
    <form wire:submit="openSession" class="space-y-4">
        <flux:select wire:model="registerId" label="Caisse" placeholder="Choisir une caisse…">
            @foreach ($this->registers as $register)
                <flux:select.option :value="$register->id" :disabled="(bool) $register->openSession">
                    {{ $register->code }} — {{ $register->name }} ({{ $register->site->name }}){{ $register->openSession ? ' · ouverte par '.$register->openSession->cashier->name : '' }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="openingBalance" type="number" step="0.01" label="Fond de caisse (espèces)" />
        <flux:input wire:model="pin" type="password" inputmode="numeric" label="PIN caisse" description="Définissez votre PIN depuis le menu utilisateur." />
        <flux:button type="submit" variant="primary" class="w-full" icon="lock-open">Ouvrir la caisse</flux:button>
    </form>
</div>
