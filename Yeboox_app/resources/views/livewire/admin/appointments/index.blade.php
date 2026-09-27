<div>
    @php
        $statusConfig = [
            'scheduled' => ['color' => 'amber', 'dot' => 'bg-amber-500', 'event' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200', 'label' => 'Planifié'],
            'confirmed' => ['color' => 'emerald', 'dot' => 'bg-emerald-500', 'event' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200', 'label' => 'Confirmé'],
            'in_progress' => ['color' => 'blue', 'dot' => 'bg-blue-500', 'event' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200', 'label' => 'En cours'],
            'completed' => ['color' => 'zinc', 'dot' => 'bg-zinc-400', 'event' => 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200', 'label' => 'Terminé'],
            'cancelled' => ['color' => 'red', 'dot' => 'bg-red-500', 'event' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200', 'label' => 'Annulé'],
            'no_show' => ['color' => 'orange', 'dot' => 'bg-orange-500', 'event' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-200', 'label' => 'Absent'],
        ];
    @endphp

    <div class="yeboox-page-head">
        <div>
            <flux:heading size="xl">Agenda des rendez-vous</flux:heading>
            <flux:text class="mt-1">{{ $todayCount }} aujourd’hui · {{ $remindersDue }} rappel(s) WhatsApp à préparer</flux:text>
        </div>
        <div class="mobile-stack-actions flex flex-wrap gap-2">
            @can('appointments.import')<flux:button wire:click="openImport" icon="arrow-up-tray">Importer Excel</flux:button>@endcan
            @can('appointments.export')
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="arrow-down-tray">Exporter</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="table-cells" href="{{ route('admin.planning-data.export', ['appointments', 'xlsx']) }}">Excel (.xlsx)</flux:menu.item>
                        <flux:menu.item icon="document-text" href="{{ route('admin.planning-data.export', ['appointments', 'pdf']) }}">PDF — modèle configuré</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endcan
            @can('appointments.create')<flux:button wire:click="openForm" variant="primary" icon="plus">Nouveau rendez-vous</flux:button>@endcan
        </div>
    </div>

    @if(session('success'))<x-dismissible-alert variant="success" icon="check-circle" class="mb-5">{{ session('success') }}</x-dismissible-alert>@endif

    @if($remindersDue > 0)
        <x-dismissible-alert variant="warning" icon="bell-alert" class="mb-5" heading="Rappels à envoyer">
            {{ $remindersDue }} rendez-vous commence dans les {{ $reminderHours }} prochaines heures et attend encore son rappel WhatsApp.
        </x-dismissible-alert>
    @endif

    <div class="mb-5 overflow-x-auto rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex min-w-max gap-1" role="tablist" aria-label="Affichage des rendez-vous">
            <flux:button wire:click="selectTab('agenda')" :variant="$activeTab === 'agenda' ? 'primary' : 'subtle'" icon="calendar-days" role="tab" :aria-selected="$activeTab === 'agenda' ? 'true' : 'false'">Agenda</flux:button>
            <flux:button wire:click="selectTab('list')" :variant="$activeTab === 'list' ? 'primary' : 'subtle'" icon="list-bullet" role="tab" :aria-selected="$activeTab === 'list' ? 'true' : 'false'">Liste et rappels</flux:button>
        </div>
    </div>

    @if($activeTab === 'agenda')
        <flux:card class="overflow-hidden p-0">
            <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-zinc-800">
                <div><flux:heading size="lg" class="capitalize">{{ $calendarTitle }}</flux:heading><flux:text size="sm">Prestations, clients et collaborateurs sur un seul planning.</flux:text></div>
                <div class="flex items-center gap-2"><flux:button wire:click="previousMonth" icon="chevron-left" square aria-label="Mois précédent"/><flux:button wire:click="goToCurrentMonth" variant="subtle">Aujourd’hui</flux:button><flux:button wire:click="nextMonth" icon="chevron-right" square aria-label="Mois suivant"/></div>
            </div>
            <div class="overflow-x-auto">
                <div class="min-w-[56rem]">
                    <div class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-800 dark:bg-zinc-900/80">
                        @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $weekday)<div class="px-3 py-2.5 text-center text-[.7rem] font-bold uppercase tracking-wider text-zinc-500">{{ $weekday }}</div>@endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-px bg-zinc-200/70 dark:bg-zinc-800">
                        @foreach($calendarDays as $day)
                            @php $dateKey = $day['date']->toDateString(); $dayAppointments = $calendarAppointments->get($dateKey, collect()); @endphp
                            <div wire:key="appointment-day-{{ $dateKey }}" class="min-h-36 bg-white p-2.5 dark:bg-zinc-950 {{ $day['currentMonth'] ? '' : 'bg-zinc-50/70 dark:bg-zinc-950/60' }}">
                                <div class="mb-2 flex items-center justify-between">
                                    <button type="button" wire:click="showDay('{{ $dateKey }}')" class="grid size-7 place-items-center rounded-full text-xs font-semibold transition hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950 {{ $day['today'] ? 'bg-rose-600 text-white hover:bg-rose-600 hover:text-white' : ($day['currentMonth'] ? 'text-zinc-700 dark:text-zinc-200' : 'text-zinc-400 dark:text-zinc-600') }}">{{ $day['date']->day }}</button>
                                    @if($dayAppointments->isNotEmpty())<span class="text-[.65rem] font-semibold text-zinc-400">{{ $dayAppointments->count() }}</span>@endif
                                </div>
                                <div class="space-y-1.5">
                                    @foreach($dayAppointments->take(3) as $appointment)
                                        @php $eventStyle = $statusConfig[$appointment->status] ?? $statusConfig['scheduled']; @endphp
                                        <button type="button" @can('appointments.edit') wire:click="openForm('{{ $appointment->id }}')" @else wire:click="showDay('{{ $dateKey }}')" @endcan class="block w-full truncate rounded-lg border px-2 py-1.5 text-start text-[.7rem] font-medium transition hover:-translate-y-px hover:shadow-sm {{ $eventStyle['event'] }}"><span class="font-bold">{{ substr($appointment->appointment_time, 0, 5) }}</span> · {{ $appointment->client_name }}<span class="block truncate opacity-70">{{ $appointment->service_name }}</span></button>
                                    @endforeach
                                    @if($dayAppointments->count() > 3)<button type="button" wire:click="showDay('{{ $dateKey }}')" class="w-full px-1 text-start text-[.68rem] font-semibold text-rose-600 hover:underline dark:text-rose-400">+ {{ $dayAppointments->count() - 3 }} autre(s)</button>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2 border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">@foreach($statusConfig as $config)<span class="inline-flex items-center gap-2 text-xs text-zinc-500"><span class="size-2 rounded-full {{ $config['dot'] }}"></span>{{ $config['label'] }}</span>@endforeach</div>
        </flux:card>
    @endif

    @if($activeTab === 'list')
        <flux:card class="mb-4"><div class="flex flex-col gap-3 sm:flex-row sm:items-end"><flux:input wire:model.live="dateFilter" type="date" label="Date"/><flux:select wire:model.live="statusFilter" label="Statut" class="sm:w-52"><flux:select.option value="">Tous les statuts</flux:select.option>@foreach($statusConfig as $key => $config)<flux:select.option value="{{ $key }}">{{ $config['label'] }}</flux:select.option>@endforeach</flux:select>@if($dateFilter || $statusFilter)<flux:button wire:click="clearFilters" variant="subtle" icon="x-mark">Effacer</flux:button>@endif</div></flux:card>

        <div class="space-y-3">
            @forelse($appointments as $appointment)
                @php $config = $statusConfig[$appointment->status] ?? $statusConfig['scheduled']; @endphp
                <flux:card wire:key="appointment-{{ $appointment->id }}" class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex size-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-rose-50 text-center text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"><span class="text-xl font-bold">{{ $appointment->appointment_date->format('d') }}</span><span class="text-[.65rem] font-semibold uppercase">{{ $appointment->appointment_date->translatedFormat('M') }}</span></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><flux:heading>{{ $appointment->client_name }}</flux:heading><flux:badge :color="$config['color']">{{ $config['label'] }}</flux:badge>@if($appointment->reminder_sent_at)<flux:badge color="green" icon="check">Rappel envoyé</flux:badge>@endif</div>
                        <p class="mt-1 font-medium text-rose-600 dark:text-rose-400">{{ $appointment->service_name }}</p>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500"><span class="inline-flex items-center gap-1"><flux:icon.clock class="size-3.5"/> {{ substr($appointment->appointment_time, 0, 5) }} · {{ $appointment->duration_minutes }} min</span>@if($appointment->staff_name)<span class="inline-flex items-center gap-1"><flux:icon.user class="size-3.5"/> {{ $appointment->staff_name }}</span>@endif<span class="inline-flex items-center gap-1"><flux:icon.phone class="size-3.5"/> {{ $appointment->client_phone }}</span></div>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @can('appointments.remind')<flux:button href="{{ route('admin.appointments.whatsapp', $appointment->id) }}" target="_blank" size="sm" color="green" variant="primary" icon="chat-bubble-left-right">WhatsApp</flux:button>@endcan
                        @if($appointment->status === 'scheduled')<flux:button wire:click="updateStatus('{{ $appointment->id }}', 'confirmed')" size="sm" variant="subtle">Confirmer</flux:button>@elseif($appointment->status === 'confirmed')<flux:button wire:click="updateStatus('{{ $appointment->id }}', 'in_progress')" size="sm" variant="subtle">Démarrer</flux:button>@elseif($appointment->status === 'in_progress')<flux:button wire:click="updateStatus('{{ $appointment->id }}', 'completed')" size="sm" variant="subtle">Terminer</flux:button>@endif
                        <flux:dropdown position="bottom" align="end"><flux:button size="sm" variant="subtle" icon="ellipsis-horizontal" square/><flux:menu>@can('appointments.edit')<flux:menu.item icon="pencil-square" wire:click="openForm('{{ $appointment->id }}')">Modifier</flux:menu.item><flux:menu.item icon="x-circle" wire:click="updateStatus('{{ $appointment->id }}', 'cancelled')">Annuler</flux:menu.item>@endcan @can('appointments.delete')<flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete('{{ $appointment->id }}')">Supprimer</flux:menu.item>@endcan</flux:menu></flux:dropdown>
                    </div>
                </flux:card>
            @empty
                <div class="yeboox-empty"><div><flux:icon.clock class="mx-auto size-11 text-zinc-300"/><flux:heading class="mt-3">Aucun rendez-vous</flux:heading><flux:text>Créez votre premier rendez-vous client.</flux:text></div></div>
            @endforelse
        </div>
        @if($appointments->hasPages())<flux:pagination :paginator="$appointments" scroll-to class="mt-6"/>@endif
    @endif

    <flux:modal wire:model.self="showFormModal" class="md:w-[50rem]">
        <form wire:submit="save" class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#e11d48"><flux:icon.clock class="size-5"/></div><div><flux:heading size="lg">{{ $editingId ? 'Modifier le rendez-vous' : 'Nouveau rendez-vous' }}</flux:heading><flux:text>Client, prestation, collaborateur et horaire.</flux:text></div></div>
            <flux:select wire:model.live="customer_id" label="Compte client"><flux:select.option value="">Client occasionnel</flux:select.option>@foreach($customers as $customer)<flux:select.option value="{{ $customer->id }}">{{ $customer->display_name }}{{ $customer->phone ? ' — '.$customer->phone : '' }}</flux:select.option>@endforeach</flux:select>
            <div class="grid gap-5 sm:grid-cols-2"><flux:input wire:model="client_name" label="Nom du client" required/><flux:input wire:model="client_phone" label="Téléphone WhatsApp" placeholder="+243…" required/><flux:input wire:model="client_email" type="email" label="E-mail"/><flux:input wire:model="service_name" label="Prestation ou service" placeholder="Massage, coiffure, consultation…" required/><flux:input wire:model="staff_name" label="Collaborateur" placeholder="Nom du praticien"/><flux:select wire:model="status" label="Statut">@foreach($statusConfig as $key => $config)<flux:select.option value="{{ $key }}">{{ $config['label'] }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="appointment_date" type="date" label="Date" required/><flux:input wire:model="appointment_time" type="time" label="Heure" required/><flux:input wire:model="duration_minutes" type="number" min="15" step="15" label="Durée" suffix="minutes"/></div>
            <flux:textarea wire:model="notes" label="Notes" rows="3"/>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showFormModal', false)">Fermer</flux:button><flux:button type="submit" variant="primary" icon="check">Enregistrer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showImportModal" class="md:w-[34rem]">
        <form wire:submit="importExcel" class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#16a34a"><flux:icon.arrow-up-tray class="size-5"/></div><div><flux:heading size="lg">Importer des rendez-vous</flux:heading><flux:text>Fichier Excel ou CSV, 10 Mo maximum.</flux:text></div></div>
            <flux:callout icon="information-circle" heading="Colonnes obligatoires">Client, Téléphone, Service, Date et Heure. Les statuts utilisent : scheduled, confirmed, in_progress, completed, cancelled ou no_show.</flux:callout>
            <flux:input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv" label="Fichier à importer" required/>
            @error('importFile')<x-dismissible-alert variant="danger">{{ $message }}</x-dismissible-alert>@enderror
            @if($importErrors)<x-dismissible-alert variant="warning" heading="Lignes non importées"><ul class="list-disc space-y-1 ps-5">@foreach($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul></x-dismissible-alert>@endif
            <flux:button href="{{ route('admin.planning-data.export', ['appointments', 'template']) }}" variant="subtle" icon="arrow-down-tray">Télécharger le modèle Excel</flux:button>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showImportModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="arrow-up-tray">Lancer l’importation</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5"/></div><div><flux:heading size="lg">Supprimer le rendez-vous ?</flux:heading><flux:text>Le rendez-vous de « {{ $deleteName }} » sera retiré de l’agenda.</flux:text></div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Supprimer</flux:button></div></div></flux:modal>
</div>
