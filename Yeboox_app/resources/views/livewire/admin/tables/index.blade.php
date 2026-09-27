<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Plan de salle</h1>
            <p class="text-sm text-slate-500 mt-1">Gérez l'état de vos tables en temps réel</p>
        </div>
        <div class="mobile-stack-actions flex gap-2">@if(auth()->user()->establishment?->reservationsModuleEnabled())<flux:button href="{{ route('admin.reservations.index') }}" icon="calendar-days" wire:navigate>Réservations</flux:button>@endif @can('tables.manage')<flux:button wire:click="openForm" variant="primary" icon="plus">Nouvelle table</flux:button>@endcan</div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
            <p class="text-xs text-slate-500">Total</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['free'] }}</p>
            <p class="text-xs text-slate-500">Libres</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['occupied'] }}</p>
            <p class="text-xs text-slate-500">Occupées</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['reserved'] }}</p>
            <p class="text-xs text-slate-500">Réservées</p>
        </div>
    </div>

    <!-- Zone Filter -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button wire:click="$set('selectedZone', '')"
                class="{{ !$selectedZone ? 'category-pill-active' : 'category-pill-inactive' }} category-pill">
            Toutes ({{ $tables->count() }})
        </button>
        @foreach($zones as $zone)
            <button wire:click="$set('selectedZone', '{{ $zone->id }}')"
                    class="{{ $selectedZone === $zone->id ? 'category-pill-active' : 'category-pill-inactive' }} category-pill">
                <span class="w-2 h-2 rounded-full inline-block" style="background-color: {{ $zone->color }}"></span>
                {{ $zone->name }} ({{ $zone->tables_count }})
            </button>
        @endforeach
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        @foreach($tables as $table)
            @php
                $statusColors = [
                    'free' => 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20',
                    'occupied' => 'border-blue-400 bg-blue-50 dark:bg-blue-900/20',
                    'reserved' => 'border-purple-400 bg-purple-50 dark:bg-purple-900/20',
                    'ordering' => 'border-amber-400 bg-amber-50 dark:bg-amber-900/20',
                    'served' => 'border-teal-400 bg-teal-50 dark:bg-teal-900/20',
                    'bill_requested' => 'border-orange-400 bg-orange-50 dark:bg-orange-900/20',
                    'cleaning' => 'border-slate-400 bg-slate-50 dark:bg-slate-700/50',
                ];
                $statusIcons = [
                    'free' => 'text-emerald-600 dark:text-emerald-400',
                    'occupied' => 'text-blue-600 dark:text-blue-400',
                    'reserved' => 'text-purple-600 dark:text-purple-400',
                ];
            @endphp
            <div class="relative group border-2 rounded-xl p-4 text-center transition-all hover:shadow-md cursor-pointer
                        {{ $statusColors[$table->status->value] ?? 'border-slate-200' }}"
                 wire:key="tbl-{{ $table->id }}">
                @can('tables.manage')
                    <div class="absolute left-1 top-1 z-10"><flux:dropdown position="bottom" align="start"><flux:button size="sm" variant="subtle" icon="ellipsis-horizontal" square/><flux:menu><flux:menu.item icon="pencil-square" wire:click="openForm('{{ $table->id }}')">Modifier</flux:menu.item><flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete('{{ $table->id }}')">Supprimer</flux:menu.item></flux:menu></flux:dropdown></div>
                @endcan
                <!-- Shape indicator -->
                <div class="mx-auto mb-2 flex items-center justify-center
                    {{ $table->shape === 'round' ? 'w-14 h-14 rounded-full' : ($table->shape === 'rectangle' ? 'w-16 h-10 rounded-lg' : 'w-12 h-12 rounded-lg') }}
                    {{ $table->status->value === 'free' ? 'bg-emerald-200 dark:bg-emerald-800' : ($table->status->value === 'occupied' ? 'bg-blue-200 dark:bg-blue-800' : 'bg-purple-200 dark:bg-purple-800') }}">
                    <span class="text-lg font-bold {{ $statusIcons[$table->status->value] ?? 'text-slate-600' }}">{{ $table->number }}</span>
                </div>

                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $table->name }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    <x-heroicon-o-users class="w-3 h-3 inline"/> {{ $table->capacity }} places
                </p>
                <p class="text-xs font-medium mt-1 {{ $statusIcons[$table->status->value] ?? 'text-slate-500' }}">
                    {{ $table->status->label() }}
                </p>

                @if($table->zone)
                    <div class="absolute top-1 right-1 w-2 h-2 rounded-full" style="background-color: {{ $table->zone->color }}"></div>
                @endif

                <!-- Quick Actions (hover) -->
                <div class="absolute inset-0 bg-white/90 dark:bg-slate-800/90 rounded-xl flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    @if($table->status->value !== 'free')
                        <button wire:click="updateStatus('{{ $table->id }}', 'free')" class="text-xs px-3 py-1 rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors">
                            Libérer
                        </button>
                    @endif
                    @if($table->status->value !== 'occupied')
                        <button wire:click="updateStatus('{{ $table->id }}', 'occupied')" class="text-xs px-3 py-1 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">
                            Occuper
                        </button>
                    @endif
                    @if($table->status->value !== 'reserved')
                        <button wire:click="updateStatus('{{ $table->id }}', 'reserved')" class="text-xs px-3 py-1 rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors">
                            Réserver
                        </button>
                    @endif
                    @if($table->status->value !== 'cleaning')
                        <button wire:click="updateStatus('{{ $table->id }}', 'cleaning')" class="text-xs px-3 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                            Nettoyage
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <flux:pagination :paginator="$tables" scroll-to class="mt-6" />

    <!-- Legend -->
    <div class="mt-6 flex flex-wrap gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-400"></span> Libre</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-400"></span> Occupée</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-400"></span> Réservée</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-400"></span> Commande</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-teal-400"></span> Servie</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-slate-400"></span> Nettoyage</span>
    </div>

    <flux:modal wire:model.self="showFormModal" class="md:w-[38rem]">
        <form wire:submit="save" class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#8b5cf6"><flux:icon.squares-2x2 class="size-5"/></div><div><flux:heading size="lg">{{ $editingId ? 'Modifier la table' : 'Nouvelle table' }}</flux:heading><flux:text>Configurez son emplacement, sa capacité et sa forme.</flux:text></div></div>
            <div class="grid gap-5 sm:grid-cols-2"><flux:input wire:model="name" label="Nom" placeholder="Table terrasse 1" required/><flux:input wire:model="number" label="Numéro" placeholder="T01" required/><flux:select wire:model="zone_id" label="Zone"><flux:select.option value="">Sans zone</flux:select.option>@foreach($zones as $zone)<flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="capacity" type="number" min="1" max="100" label="Capacité" suffix="places"/><flux:select wire:model="shape" label="Forme"><flux:select.option value="square">Carrée</flux:select.option><flux:select.option value="round">Ronde</flux:select.option><flux:select.option value="rectangle">Rectangulaire</flux:select.option></flux:select><div class="flex items-end pb-2"><flux:switch wire:model="is_active" label="Table active"/></div></div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showFormModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="check">Enregistrer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5"/></div><div><flux:heading size="lg">Supprimer cette table ?</flux:heading><flux:text>« {{ $deleteName }} » disparaîtra du plan de salle.</flux:text></div></div>@error('delete')<x-dismissible-alert variant="danger">{{ $message }}</x-dismissible-alert>@enderror<div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Supprimer</flux:button></div></div></flux:modal>
</div>
