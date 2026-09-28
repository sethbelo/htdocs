<div class="space-y-6">
    <x-page-header title="Import de données" eyebrow="Paramètres" subtitle="Reprenez vos fichiers existants ou mettez à jour vos données en masse depuis Excel ou CSV." />
    @include('livewire.settings.nav')

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($types as $type => $definition)
            <div class="panel flex flex-col gap-3 p-5" wire:key="imp-{{ $type }}">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-cyan2-300"><flux:icon.table-cells variant="mini" /></span>
                    <flux:heading>{{ $definition['label'] }}</flux:heading>
                </div>
                <flux:text class="text-xs">Clé de rapprochement : {{ $definition['key'] }}. {{ count($definition['columns']) }} colonnes, dont {{ collect($definition['columns'])->filter(fn ($c) => str_contains($c[1], 'required'))->count() }} obligatoire(s).</flux:text>
                <div class="mt-auto"><livewire:import-data :type="$type" :key="'settings-import-'.$type" /></div>
            </div>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3"><x-empty-state icon="lock-closed" title="Aucun import autorisé pour votre profil" /></div>
        @endforelse
    </div>
</div>
