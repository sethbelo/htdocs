{{-- Arrivée d'un visiteur (trait HandlesFrontDesk) — variable : $hosts --}}
<x-ui.drawer wire:model="showVisitor" title="Enregistrer un visiteur" :subtitle="! empty($visitor['appointment_id']) ? 'Arrivée sur rendez-vous' : 'Visite sans rendez-vous'" icon="user-plus">
    <div class="space-y-4">
        <x-ui.input label="Nom complet" wire:model="visitor.full_name" required />
        <div class="grid grid-cols-2 gap-3"><x-ui.input label="Société / organisation" wire:model="visitor.organization" /><x-ui.input label="Téléphone" type="tel" wire:model="visitor.phone" /></div>
        <x-ui.input label="Pièce d’identité" wire:model="visitor.id_document" placeholder="Carte d’électeur, passeport… et numéro" />
        <x-ui.input label="Objet de la visite" wire:model="visitor.purpose" required />
        <x-ui.select label="Personne visitée" wire:model="visitor.host_id" placeholder="—" :options="$hosts->pluck('name', 'id')" />
        <x-ui.input label="N° de badge" wire:model="visitor.badge_number" />
    </div>
    <x-slot:footer>
        <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
        <x-ui.button variant="primary" icon="check" wire:click="checkIn">Enregistrer l’arrivée</x-ui.button>
    </x-slot:footer>
</x-ui.drawer>
