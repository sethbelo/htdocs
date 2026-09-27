<flux:modal name="client" class="w-full max-w-2xl">
    <form wire:submit="save" class="space-y-4">
        <flux:heading size="lg">{{ $editingId ? 'Modifier le client' : 'Nouveau client' }}</flux:heading>
        <div class="grid gap-3 md:grid-cols-2">
            <flux:select wire:model="form.type" label="Type">
                @foreach (\App\Enums\ClientType::options() as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model="form.segment" label="Segment">
                <flux:select.option value="b2c">B2C</flux:select.option>
                <flux:select.option value="b2b">B2B</flux:select.option>
            </flux:select>
            <flux:input wire:model="form.name" label="Nom / contact" required />
            <flux:input wire:model="form.company_name" label="Entreprise / organisation" />
            <flux:input wire:model="form.phone" label="Téléphone" />
            <flux:input wire:model="form.whatsapp" label="WhatsApp" />
            <flux:input wire:model="form.email" type="email" label="E-mail" />
            <flux:input wire:model="form.tax_id" label="NIF / ID fiscal" />
            <flux:input wire:model="form.address" label="Adresse" />
            <flux:input wire:model="form.city" label="Ville" />
            @isset($salesReps)
                <flux:select wire:model="form.sales_rep_id" label="Commercial responsable">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($salesReps as $rep)<flux:select.option :value="$rep->id">{{ $rep->name }}</flux:select.option>@endforeach
                </flux:select>
            @endisset
            @can('clients.approve')
                <div class="flex items-end gap-3">
                    <flux:switch wire:model="form.allow_credit" label="Crédit autorisé" />
                    <flux:input wire:model="form.credit_limit" type="number" step="0.01" label="Plafond" />
                </div>
            @endcan
        </div>
        <flux:textarea wire:model="form.notes" label="Notes internes" rows="2" />
        <div class="flex justify-end gap-2">
            <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</flux:modal>
