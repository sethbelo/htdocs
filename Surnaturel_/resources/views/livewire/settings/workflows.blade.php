<div class="space-y-6">
    <x-page-header title="Circuits de validation" subtitle="Étapes séquentielles ; une étape ne s’applique qu’à partir de son seuil de montant. Tout refus exige motif et commentaire." />
    @include('livewire.settings.nav')

    @foreach ($workflows as $workflow)
        <div class="panel p-4 space-y-3" wire:key="wf-{{ $workflow->id }}">
            <div class="flex items-center justify-between">
                <flux:heading>{{ $workflow->name }} <span class="text-xs text-zinc-500">({{ $workflow->code }})</span></flux:heading>
                <flux:switch :checked="$workflow->is_active" wire:click="toggle({{ $workflow->id }})" label="Actif" />
            </div>
            @foreach ($workflow->steps as $step)
                <div class="grid grid-cols-12 items-end gap-2" wire:key="st-{{ $step->id }}">
                    <div class="col-span-1 pb-2 text-center font-semibold text-zinc-400">{{ $step->sequence }}</div>
                    <flux:input wire:model="steps.{{ $step->id }}.name" label="Étape" class="col-span-3" />
                    <flux:select wire:model="steps.{{ $step->id }}.approver_role" label="Rôle approbateur" class="col-span-3"><flux:select.option value="">—</flux:select.option>@foreach ($roles as $r)<flux:select.option :value="$r">{{ config("erp.roles.$r.label", $r) }}</flux:select.option>@endforeach</flux:select>
                    <flux:select wire:model="steps.{{ $step->id }}.approver_user_id" label="ou utilisateur désigné" class="col-span-2"><flux:select.option value="">—</flux:select.option>@foreach ($users as $u)<flux:select.option :value="$u->id">{{ $u->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="steps.{{ $step->id }}.min_amount" type="number" step="0.01" label="À partir de" class="col-span-2" />
                    <div class="col-span-1 flex gap-1 pb-1"><flux:button size="sm" icon="check" wire:click="saveStep({{ $step->id }})" /><flux:button size="sm" variant="ghost" icon="trash" wire:click="removeStep({{ $step->id }})" wire:confirm="Supprimer cette étape ?" /></div>
                </div>
            @endforeach
            <div class="grid grid-cols-12 items-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <div class="col-span-1"></div>
                <flux:input wire:model="newStep.{{ $workflow->id }}.name" placeholder="Nouvelle étape" class="col-span-3" />
                <flux:select wire:model="newStep.{{ $workflow->id }}.approver_role" class="col-span-5"><flux:select.option value="">Rôle…</flux:select.option>@foreach ($roles as $r)<flux:select.option :value="$r">{{ config("erp.roles.$r.label", $r) }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="newStep.{{ $workflow->id }}.min_amount" type="number" step="0.01" placeholder="Seuil" class="col-span-2" />
                <flux:button size="sm" icon="plus" wire:click="addStep({{ $workflow->id }})" class="col-span-1" />
            </div>
        </div>
    @endforeach
</div>
