<div>
    @php
        $statusConfig = [
            'pending' => ['color' => 'amber', 'dot' => 'bg-amber-500', 'event' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200', 'label' => 'En attente'],
            'confirmed' => ['color' => 'emerald', 'dot' => 'bg-emerald-500', 'event' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200', 'label' => 'Confirmée'],
            'seated' => ['color' => 'blue', 'dot' => 'bg-blue-500', 'event' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200', 'label' => 'Installée'],
            'completed' => ['color' => 'zinc', 'dot' => 'bg-zinc-400', 'event' => 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200', 'label' => 'Terminée'],
            'cancelled' => ['color' => 'red', 'dot' => 'bg-red-500', 'event' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200', 'label' => 'Annulée'],
            'no_show' => ['color' => 'orange', 'dot' => 'bg-orange-500', 'event' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-200', 'label' => 'Absente'],
        ];
    @endphp

    <div class="yeboox-page-head">
        <div>
            <flux:heading size="xl">Planning des réservations</flux:heading>
            <flux:text class="mt-1">{{ $todayCount }} aujourd’hui · {{ $pendingCount }} en attente de confirmation</flux:text>
        </div>
        <div class="mobile-stack-actions flex flex-wrap gap-2">
            @can('reservations.import')<flux:button wire:click="openImport" icon="arrow-up-tray">Importer Excel</flux:button>@endcan
            @can('reservations.export')<flux:dropdown position="bottom" align="end"><flux:button icon="arrow-down-tray">Exporter</flux:button><flux:menu><flux:menu.item icon="table-cells" href="{{ route('admin.planning-data.export', ['reservations', 'xlsx']) }}">Excel (.xlsx)</flux:menu.item><flux:menu.item icon="document-text" href="{{ route('admin.planning-data.export', ['reservations', 'pdf']) }}">PDF — modèle configuré</flux:menu.item></flux:menu></flux:dropdown>@endcan
            <flux:button variant="subtle" icon="squares-2x2" href="{{ route('admin.tables.index') }}" wire:navigate>Plan de salle</flux:button>
            @can('reservations.create')<flux:button wire:click="openForm" variant="primary" icon="calendar-days">Nouvelle réservation</flux:button>@endcan
        </div>
    </div>

    @if(session('success'))
        <x-dismissible-alert variant="success" icon="check-circle" class="mb-5" heading="Réservations mises à jour">{{ session('success') }}</x-dismissible-alert>
    @endif

    <div class="mb-5 overflow-x-auto rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex min-w-max gap-1" role="tablist" aria-label="Affichage des réservations">
            <flux:button wire:click="selectTab('planning')" :variant="$activeTab === 'planning' ? 'primary' : 'subtle'" icon="calendar-days" role="tab" :aria-selected="$activeTab === 'planning' ? 'true' : 'false'">Calendrier</flux:button>
            <flux:button wire:click="selectTab('list')" :variant="$activeTab === 'list' ? 'primary' : 'subtle'" icon="list-bullet" role="tab" :aria-selected="$activeTab === 'list' ? 'true' : 'false'">Liste et suivi</flux:button>
        </div>
    </div>

    @if($activeTab === 'planning')
        <flux:card class="overflow-hidden p-0">
            <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg" class="capitalize">{{ $calendarTitle }}</flux:heading>
                    <flux:text size="sm">Cliquez sur une journée pour ouvrir sa liste détaillée.</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button wire:click="previousMonth" icon="chevron-left" square aria-label="Mois précédent" />
                    <flux:button wire:click="goToCurrentMonth" variant="subtle">Aujourd’hui</flux:button>
                    <flux:button wire:click="nextMonth" icon="chevron-right" square aria-label="Mois suivant" />
                </div>
            </div>
            <div class="overflow-x-auto">
                <div class="min-w-[56rem]">
                    <div class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-800 dark:bg-zinc-900/80">
                        @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $weekday)
                            <div class="px-3 py-2.5 text-center text-[.7rem] font-bold uppercase tracking-wider text-zinc-500">{{ $weekday }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 bg-zinc-200/70 gap-px dark:bg-zinc-800">
                        @foreach($calendarDays as $day)
                            @php
                                $dateKey = $day['date']->toDateString();
                                $dayReservations = $calendarReservations->get($dateKey, collect());
                            @endphp
                            <div wire:key="calendar-{{ $dateKey }}" class="min-h-36 bg-white p-2.5 dark:bg-zinc-950 {{ $day['currentMonth'] ? '' : 'bg-zinc-50/70 dark:bg-zinc-950/60' }}">
                                <div class="mb-2 flex items-center justify-between">
                                    <button type="button" wire:click="showDay('{{ $dateKey }}')" class="grid size-7 place-items-center rounded-full text-xs font-semibold transition hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950 {{ $day['today'] ? 'bg-blue-600 text-white shadow-sm hover:bg-blue-600 hover:text-white' : ($day['currentMonth'] ? 'text-zinc-700 dark:text-zinc-200' : 'text-zinc-400 dark:text-zinc-600') }}">{{ $day['date']->day }}</button>
                                    @if($dayReservations->isNotEmpty())<span class="text-[.65rem] font-semibold text-zinc-400">{{ $dayReservations->count() }}</span>@endif
                                </div>
                                <div class="space-y-1.5">
                                    @foreach($dayReservations->take(3) as $reservation)
                                        @php $eventStyle = $statusConfig[$reservation->status] ?? $statusConfig['pending']; @endphp
                                        <button type="button" @can('reservations.edit') wire:click="openForm('{{ $reservation->id }}')" @else wire:click="showDay('{{ $dateKey }}')" @endcan class="block w-full truncate rounded-lg border px-2 py-1.5 text-start text-[.7rem] font-medium transition hover:-translate-y-px hover:shadow-sm {{ $eventStyle['event'] }}">
                                            <span class="font-bold">{{ substr($reservation->reservation_time, 0, 5) }}</span> · {{ $reservation->guest_name }}
                                        </button>
                                    @endforeach
                                    @if($dayReservations->count() > 3)
                                        <button type="button" wire:click="showDay('{{ $dateKey }}')" class="w-full px-1 text-start text-[.68rem] font-semibold text-blue-600 hover:underline dark:text-blue-400">+ {{ $dayReservations->count() - 3 }} autre(s)</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2 border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">
                @foreach($statusConfig as $config)
                    <span class="inline-flex items-center gap-2 text-xs text-zinc-500"><span class="size-2 rounded-full {{ $config['dot'] }}"></span>{{ $config['label'] }}</span>
                @endforeach
            </div>
        </flux:card>
    @endif

    @if($activeTab === 'list')
        <flux:card class="mb-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:input wire:model.live="dateFilter" type="date" label="Date" />
                <flux:select wire:model.live="statusFilter" label="Statut" class="sm:w-52">
                    <flux:select.option value="">Tous les statuts</flux:select.option>
                    @foreach($statusConfig as $key => $config)<flux:select.option value="{{ $key }}">{{ $config['label'] }}</flux:select.option>@endforeach
                </flux:select>
                @if($dateFilter || $statusFilter)<flux:button wire:click="clearFilters" variant="subtle" icon="x-mark">Effacer les filtres</flux:button>@endif
            </div>
        </flux:card>

        <div class="space-y-3">
            @forelse($reservations as $resa)
                @php $s = $statusConfig[$resa->status] ?? $statusConfig['pending']; @endphp
                <flux:card wire:key="resa-{{ $resa->id }}" class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex size-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-zinc-100 text-center dark:bg-zinc-800">
                        <span class="text-xl font-bold">{{ $resa->reservation_date->format('d') }}</span><span class="text-[.65rem] font-semibold uppercase text-zinc-500">{{ $resa->reservation_date->translatedFormat('M') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><flux:heading>{{ $resa->guest_name }}</flux:heading><flux:badge :color="$s['color']">{{ $s['label'] }}</flux:badge></div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500">
                            <span class="inline-flex items-center gap-1"><flux:icon.clock class="size-3.5"/> {{ substr($resa->reservation_time, 0, 5) }} · {{ $resa->duration_minutes }} min</span>
                            <span class="inline-flex items-center gap-1"><flux:icon.users class="size-3.5"/> {{ $resa->party_size }} personne(s)</span>
                            @if($resa->table)<span class="inline-flex items-center gap-1"><flux:icon.squares-2x2 class="size-3.5"/> {{ $resa->table->name }}</span>@endif
                            @if($resa->guest_phone)<span class="inline-flex items-center gap-1"><flux:icon.phone class="size-3.5"/> {{ $resa->guest_phone }}</span>@endif
                        </div>
                        @if($resa->special_requests)<flux:text size="sm" class="mt-2 italic">{{ $resa->special_requests }}</flux:text>@endif
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if($resa->status === 'pending')<flux:button size="sm" wire:click="confirm('{{ $resa->id }}')" variant="primary" icon="check">Confirmer</flux:button><flux:button size="sm" wire:click="cancel('{{ $resa->id }}')" variant="danger">Annuler</flux:button>@elseif($resa->status === 'confirmed')<flux:button size="sm" wire:click="markSeated('{{ $resa->id }}')" variant="primary">Installer</flux:button><flux:button size="sm" wire:click="markNoShow('{{ $resa->id }}')" variant="subtle">Absente</flux:button>@endif
                        <flux:dropdown position="bottom" align="end"><flux:button size="sm" variant="subtle" icon="ellipsis-horizontal" square/><flux:menu>@can('reservations.edit')<flux:menu.item icon="pencil-square" wire:click="openForm('{{ $resa->id }}')">Modifier</flux:menu.item>@endcan @can('reservations.delete')<flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete('{{ $resa->id }}')">Supprimer</flux:menu.item>@endcan</flux:menu></flux:dropdown>
                    </div>
                </flux:card>
            @empty
                <div class="yeboox-empty"><div><flux:icon.calendar-days class="mx-auto size-11 text-zinc-300"/><flux:heading class="mt-3">Aucune réservation</flux:heading><flux:text>Aucun client ne correspond à cette période.</flux:text></div></div>
            @endforelse
        </div>

        @if($reservations->hasPages())<flux:pagination :paginator="$reservations" scroll-to class="mt-6" />@endif
    @endif

    <flux:modal wire:model.self="showFormModal" class="md:w-[48rem]">
        <form wire:submit="save" class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#f59e0b"><flux:icon.calendar-days class="size-5"/></div><div><flux:heading size="lg">{{ $editingId ? 'Modifier la réservation' : 'Nouvelle réservation' }}</flux:heading><flux:text>Client, horaire, table et demandes particulières.</flux:text></div></div>
            <div class="grid gap-5 sm:grid-cols-2"><flux:input wire:model="guest_name" label="Nom du client" required/><flux:input wire:model="guest_phone" label="Téléphone"/><flux:input wire:model="guest_email" type="email" label="E-mail"/><flux:select wire:model="table_id" label="Table"><flux:select.option value="">Attribution ultérieure</flux:select.option>@foreach($tables as $table)<flux:select.option value="{{ $table->id }}">{{ $table->name }} — {{ $table->capacity }} places</flux:select.option>@endforeach</flux:select><flux:input wire:model="reservation_date" type="date" label="Date" required/><flux:input wire:model="reservation_time" type="time" label="Heure" required/><flux:input wire:model="party_size" type="number" min="1" label="Nombre de personnes"/><flux:input wire:model="duration_minutes" type="number" min="15" step="15" label="Durée" suffix="minutes"/><flux:select wire:model="status" label="Statut"><flux:select.option value="pending">En attente</flux:select.option><flux:select.option value="confirmed">Confirmée</flux:select.option><flux:select.option value="seated">Installée</flux:select.option><flux:select.option value="completed">Terminée</flux:select.option><flux:select.option value="cancelled">Annulée</flux:select.option><flux:select.option value="no_show">Absente</flux:select.option></flux:select></div>
            <flux:textarea wire:model="special_requests" label="Demandes particulières" rows="3"/>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showFormModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="check">Enregistrer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showImportModal" class="md:w-[34rem]">
        <form wire:submit="importExcel" class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#16a34a"><flux:icon.arrow-up-tray class="size-5"/></div><div><flux:heading size="lg">Importer des réservations</flux:heading><flux:text>Fichier Excel ou CSV, 10 Mo maximum.</flux:text></div></div>
            <flux:callout icon="information-circle" heading="Colonnes obligatoires">Client, Date, Heure et Personnes. La table peut contenir son nom ou son numéro.</flux:callout>
            <flux:input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv" label="Fichier à importer" required/>
            @error('importFile')<x-dismissible-alert variant="danger">{{ $message }}</x-dismissible-alert>@enderror
            @if($importErrors)<x-dismissible-alert variant="warning" heading="Lignes non importées"><ul class="list-disc space-y-1 ps-5">@foreach($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul></x-dismissible-alert>@endif
            <flux:button href="{{ route('admin.planning-data.export', ['reservations', 'template']) }}" variant="subtle" icon="arrow-down-tray">Télécharger le modèle Excel</flux:button>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showImportModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="arrow-up-tray">Lancer l’importation</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5"/></div><div><flux:heading size="lg">Supprimer la réservation ?</flux:heading><flux:text>La réservation de « {{ $deleteName }} » sera retirée du planning.</flux:text></div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Supprimer</flux:button></div></div></flux:modal>
</div>
