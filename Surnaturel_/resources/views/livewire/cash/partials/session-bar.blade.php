{{-- Barre de session de caisse partagée (caisse commandes et POS) --}}
@if ($this->session)
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <flux:badge color="emerald" icon="lock-open">{{ $this->session->register->code }} · {{ $this->session->register->site->name }}</flux:badge>
        <span class="text-zinc-500">Session {{ $this->session->number }} · ouverte {{ $this->session->opened_at->format('H:i') }}</span>
        <span class="text-zinc-500">· Espèces théoriques <x-money :amount="$this->session->theoreticalCashBalance()" class="font-medium text-zinc-800 dark:text-zinc-100" /></span>
        @can('cash.disburse')
            <flux:modal.trigger name="disburse"><flux:button size="xs" icon="arrow-up-right">Décaisser</flux:button></flux:modal.trigger>
        @endcan
        <flux:button size="xs" icon="document-text" :href="route('print.cash-session', $this->session)" target="_blank">Situation</flux:button>
        <flux:button size="xs" variant="danger" icon="lock-closed" wire:click="openClosing">Clôturer</flux:button>
    </div>

    <flux:modal name="disburse" class="w-full max-w-md">
        <form wire:submit="disburse" class="space-y-4">
            <flux:heading size="lg">Bon de décaissement</flux:heading>
            <flux:text>Au-delà de <x-money :amount="$this->session->register->max_disbursement_without_approval" />, un responsable doit valider avec son PIN.</flux:text>
            <flux:input wire:model="disbursementAmount" type="number" step="0.01" label="Montant" required />
            <flux:input wire:model="disbursementReason" label="Motif / bénéficiaire" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:select wire:model="supervisorId" label="Responsable">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($this->supervisors as $supervisor)
                        <flux:select.option :value="$supervisor->id">{{ $supervisor->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="supervisorPin" type="password" inputmode="numeric" label="PIN responsable" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Décaisser</flux:button>
            </div>
        </form>
    </flux:modal>

    @include('livewire.cash.partials.closing-modal')
@endif
