<div class="space-y-6">
    <x-page-header title="Paramètres" subtitle="Identité de l’entreprise, documents, règles de gestion et comptabilité." />
    @include('livewire.settings.nav')

    <form wire:submit="save" class="space-y-6">
        <div class="panel p-4 space-y-4">
            <flux:heading>Entreprise</flux:heading>
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="company.name" label="Nom commercial" />
                <flux:input wire:model="company.legal_name" label="Raison sociale" />
                <flux:input wire:model="company.rccm" label="RCCM" />
                <flux:input wire:model="company.id_nat" label="ID.NAT" />
                <flux:input wire:model="company.nif" label="NIF" />
                <flux:input wire:model="company.phone" label="Téléphone" />
                <flux:input wire:model="company.email" type="email" label="E-mail" />
                <flux:input wire:model="company.website" label="Site web" placeholder="https://" />
                <flux:input wire:model="company.address" label="Adresse" />
                <flux:input wire:model="company.city" label="Ville" />
                <flux:input wire:model="company.country" label="Pays" />
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div class="flex items-center gap-4">
                    <x-app-logo class="size-14 rounded-lg" />
                    <flux:input type="file" wire:model="logo" label="Logo" accept="image/*" />
                </div>
                <flux:input type="file" wire:model="favicon" label="Favicon" accept="image/png,image/x-icon,image/svg+xml" />
            </div>
            <flux:heading size="sm">Réseaux sociaux</flux:heading>
            <div class="grid gap-3 md:grid-cols-5">
                @foreach ($socials as $network => $value)<flux:input wire:model="socials.{{ $network }}" :label="ucfirst($network)" />@endforeach
            </div>
        </div>

        <div class="panel p-4 space-y-4">
            <flux:heading>Facturation & documents</flux:heading>
            <div class="grid gap-3 md:grid-cols-4">
                <flux:input wire:model="company.currency" label="Devise (ISO)" maxlength="3" />
                <flux:input wire:model="company.currency_decimals" type="number" label="Décimales" />
                <flux:input wire:model="company.default_tax_rate" type="number" step="0.01" label="TVA par défaut %" />
                <flux:input wire:model="operations.default_deposit_rate" type="number" label="Acompte par défaut %" />
            </div>
            <flux:input wire:model="company.ticket_footer" label="Pied de ticket" />
            <flux:switch wire:model="operations.allow_negative_stock" label="Autoriser le stock négatif" />
        </div>

        <div class="panel p-4 space-y-4">
            <flux:heading>Ressources humaines</flux:heading>
            <div class="grid gap-3 md:grid-cols-5">
                <flux:input wire:model="operations.work_start" type="time" label="Heure d’arrivée" />
                <flux:input wire:model="operations.late_tolerance_minutes" type="number" label="Tolérance retard (min)" />
                <flux:input wire:model="operations.daily_hours" type="number" step="0.5" label="Heures / jour" />
                <flux:input wire:model="operations.working_days_per_month" type="number" label="Jours ouvrés / mois" />
                <flux:input wire:model="operations.overtime_rate" type="number" step="0.05" label="Majoration HS" />
            </div>
        </div>

        <div class="panel p-4 space-y-4">
            <flux:heading>Imputations comptables automatiques</flux:heading>
            <div class="grid gap-3 md:grid-cols-3">
                @foreach ($accounting as $key => $code)
                    <flux:select wire:model="accounting.{{ $key }}" :label="$accountLabels[$key] ?? $key">
                        @foreach ($accounts as $account)<flux:select.option :value="$account->code">{{ $account->code }} — {{ $account->name }}</flux:select.option>@endforeach
                    </flux:select>
                @endforeach
            </div>
        </div>

        @can('settings.update')
            <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="check">Enregistrer les paramètres</flux:button></div>
        @endcan
    </form>
</div>
