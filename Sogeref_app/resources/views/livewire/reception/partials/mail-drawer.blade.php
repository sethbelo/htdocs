{{-- Enregistrement / modification d'un courrier (trait HandlesFrontDesk) — variables : $hosts, $departments --}}
@php $in = ($mail['direction'] ?? 'in') === 'in'; @endphp
<x-ui.drawer wire:model="showMail" :title="$editingMail ? 'Courrier '.($mail['reference'] ?? '') : ($in ? 'Courrier entrant' : 'Courrier sortant')" :subtitle="$editingMail ? null : 'La référence est attribuée automatiquement'" icon="envelope">
    <div class="space-y-4">
        @unless ($editingMail)
            <div class="segmented">
                <button type="button" wire:click="$set('mail.direction', 'in')" aria-pressed="{{ $in ? 'true' : 'false' }}"><x-ui.icon name="inbox-arrow-down" class="size-4" /> Entrant</button>
                <button type="button" wire:click="$set('mail.direction', 'out')" aria-pressed="{{ $in ? 'false' : 'true' }}"><x-ui.icon name="paper-airplane" class="size-4" /> Sortant</button>
            </div>
        @endunless
        <div class="grid grid-cols-2 gap-3"><x-ui.input label="Date" type="date" wire:model="mail.record_date" /><x-ui.select label="Canal" wire:model="mail.channel" :options="['physique' => 'Dépôt physique', 'email' => 'E-mail', 'coursier' => 'Coursier']" /></div>
        <x-ui.input :label="$in ? 'Expéditeur' : 'Destinataire'" wire:model="mail.correspondent" required />
        <x-ui.input label="Objet" wire:model="mail.subject" required />
        <x-ui.select :label="$in ? 'Service destinataire' : 'Service émetteur'" wire:model="mail.department_id" placeholder="—" :options="$departments->pluck('name', 'id')" />
        <x-ui.select :label="$in ? 'Transmis à' : 'Signataire / responsable'" wire:model="mail.handled_by" placeholder="—" :options="$hosts->pluck('name', 'id')" />
        <x-ui.select label="Statut" wire:model="mail.status" :options="collect(config('erp.statuses.mail'))->map(fn ($s) => $s[0])" />
        <x-ui.field label="Scan du courrier" for="mailFile" hint="PDF ou image, 10 Mo maximum">
            <input id="mailFile" type="file" wire:model="mailFile" accept=".pdf,image/*" class="input py-1.5">
            <div wire:loading wire:target="mailFile" class="mt-1 text-[12px] text-fg-subtle">Téléversement…</div>
            @if (! empty($mail['has_file']) && ! $mailFile)<p class="mt-1 text-[12px] text-fg-subtle">Un scan est déjà joint ; un nouveau fichier le remplacera.</p>@endif
        </x-ui.field>
    </div>
    <x-slot:footer>
        <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
        <x-ui.button variant="primary" icon="check" wire:click="saveMail" wire:loading.attr="disabled" wire:target="mailFile,saveMail">Enregistrer</x-ui.button>
    </x-slot:footer>
</x-ui.drawer>
