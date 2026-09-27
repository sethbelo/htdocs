<div class="space-y-6">
    <x-page-header title="Documents & signatures" subtitle="Charte des documents imprimés : mentions, signataires des bons et modèles de messages." eyebrow="Paramètres" />
    @include('livewire.settings.nav')

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="panel space-y-4 p-5 xl:col-span-1">
                <flux:heading>Mentions des documents</flux:heading>
                <div class="rounded-xl border border-line bg-surface-2 p-4">
                    <x-app-logo variant="full" class="h-10 w-auto" />
                    <div class="brand-rule mt-3"></div>
                    <p class="mt-2 text-xs text-fg-subtle">Aperçu de l’en-tête : logo, puis filet CMJN sur toutes les pages A4 / A5.</p>
                </div>
                <flux:input wire:model="slogan" label="Slogan (pied de page)" placeholder="L’impression qui marque les esprits" />
                <flux:textarea wire:model="bankDetails" label="Coordonnées bancaires" rows="2" />
                <flux:input wire:model="ticketFooter" label="Message des tickets de caisse" />
                <flux:input wire:model="cardValidityYears" type="number" min="1" max="10" label="Validité des cartes de service (années)" />
            </div>

            <div class="panel p-5 xl:col-span-2">
                <flux:heading>Signataires des bons et documents</flux:heading>
                <flux:text class="mt-1">Un signataire par ligne (5 au maximum). Ils apparaissent dans le cadre « Visas & signatures » de chaque document imprimé.</flux:text>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($types as $type => $definition)
                        <div class="rounded-xl border border-line p-3" wire:key="sig-{{ $type }}">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold">{{ $definition['label'] }}</span>
                                <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click="resetSignatures('{{ $type }}')" title="Valeurs par défaut" />
                            </div>
                            <flux:textarea wire:model="signatures.{{ $type }}" rows="3" resize="none" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel space-y-4 p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading>Modèles de messages WhatsApp</flux:heading>
                    <flux:text>Variables disponibles : <span class="kbd">{client}</span> <span class="kbd">{agent}</span> <span class="kbd">{entreprise}</span></flux:text>
                </div>
                <flux:button size="sm" icon="plus" wire:click="addTemplate">Ajouter</flux:button>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($templates as $index => $template)
                    <div class="space-y-2 rounded-xl border border-line p-3" wire:key="tpl-{{ $index }}">
                        <div class="flex gap-2">
                            <flux:input wire:model="templates.{{ $index }}.name" placeholder="Nom du modèle" class="flex-1" />
                            <flux:button icon="trash" variant="ghost" wire:click="removeTemplate({{ $index }})" />
                        </div>
                        <flux:textarea wire:model="templates.{{ $index }}.body" rows="3" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="check">Enregistrer</flux:button></div>
    </form>
</div>
