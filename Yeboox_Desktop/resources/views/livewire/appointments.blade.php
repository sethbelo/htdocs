<div class="mx-auto w-full max-w-[1600px] space-y-6">
    @php
        $todayCount = $appointments->filter(fn ($appointment) => $appointment->date_time->isToday())->count();
        $pendingCount = $appointments->where('status', 'pending')->count();
        $confirmedCount = $appointments->where('status', 'confirmed')->count();
        $activeFilters = collect([$filterEmployeeId, $filterStatus, $filterDate])->filter()->count();
        $statusStyles = [
            'pending' => ['label' => 'En attente', 'color' => 'amber', 'icon' => 'clock'],
            'confirmed' => ['label' => 'Confirmé', 'color' => 'blue', 'icon' => 'check-circle'],
            'completed' => ['label' => 'Terminé', 'color' => 'green', 'icon' => 'check-badge'],
            'cancelled' => ['label' => 'Annulé', 'color' => 'red', 'icon' => 'x-circle'],
        ];
    @endphp

    <div x-data="{ show: false, message: '' }" x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3200)" x-show="show" x-transition class="fixed right-5 top-5 z-50" style="display:none">
        <flux:callout variant="success" icon="check-circle" x-text="message" />
    </div>

    {{-- En-tête partagé : planning, création et identité visuelle des rendez-vous. --}}
    <x-page-hero
        title="Rendez-vous"
        eyebrow="Planning centralisé"
        description="Organisez les séances, coordonnez l’équipe et suivez chaque client tout au long de la journée."
        icon="calendar-days"
        badge-icon="clock"
        illustration="appointments"
    >
        <x-slot:actions>
            <flux:button wire:click="exportAppointments" variant="filled" icon="arrow-down-tray" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!">Exporter</flux:button>
            <flux:button wire:click="openCreateModal" variant="primary" color="zinc" icon="plus" class="bg-white! text-indigo-900! hover:bg-violet-50!">Nouveau rendez-vous</flux:button>
        </x-slot:actions>
    </x-page-hero>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Indicateurs des rendez-vous">
        @foreach([
            ['Rendez-vous affichés', $appointments->count(), 'Selon les filtres actifs', 'calendar-days', 'violet'],
            ['Aujourd’hui', $todayCount, 'Séances au programme', 'clock', 'blue'],
            ['À confirmer', $pendingCount, 'Réponses clients attendues', 'clock', 'amber'],
            ['Confirmés', $confirmedCount, 'Prêts à être pris en charge', 'check-circle', 'emerald'],
        ] as [$label, $value, $description, $icon, $color])
            <flux:card class="relative min-h-36 overflow-hidden p-4! sm:p-5!">
                <div class="absolute -right-7 -top-7 size-24 rounded-full bg-zinc-100/70 dark:bg-white/5"></div>
                <div class="flex items-center justify-between"><flux:text size="sm">{{ $label }}</flux:text><span @class(['grid size-9 place-items-center rounded-xl', 'bg-violet-50 text-violet-600 dark:bg-violet-500/10' => $color === 'violet', 'bg-blue-50 text-blue-600 dark:bg-blue-500/10' => $color === 'blue', 'bg-amber-50 text-amber-600 dark:bg-amber-500/10' => $color === 'amber', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' => $color === 'emerald'])><flux:icon :name="$icon" class="size-4" /></span></div>
                <div class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</div><flux:text size="sm" class="mt-1">{{ $description }}</flux:text>
            </flux:card>
        @endforeach
    </section>

    <flux:card class="p-4! sm:p-5!">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid flex-1 gap-4 md:grid-cols-3">
                <flux:select wire:model.live="filterEmployeeId" label="Membre de l’équipe"><flux:select.option value="">Toute l’équipe</flux:select.option>@foreach($employees as $employee)<flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model.live="filterStatus" label="Statut"><flux:select.option value="">Tous les statuts</flux:select.option><flux:select.option value="pending">En attente</flux:select.option><flux:select.option value="confirmed">Confirmé</flux:select.option><flux:select.option value="completed">Terminé</flux:select.option><flux:select.option value="cancelled">Annulé</flux:select.option></flux:select>
                <flux:input type="date" wire:model.live="filterDate" label="Date du rendez-vous" />
            </div>
            <div class="flex items-center justify-between gap-3"><flux:badge :color="$activeFilters ? 'violet' : 'zinc'" icon="funnel">{{ $activeFilters }} filtre{{ $activeFilters > 1 ? 's' : '' }}</flux:badge>@if($activeFilters)<flux:button wire:click="resetFilters" variant="ghost" icon="x-mark">Réinitialiser</flux:button>@endif</div>
        </div>
    </flux:card>

    <section class="grid gap-4 md:hidden" aria-label="Liste mobile des rendez-vous">
        @forelse($appointments as $appointment)
            @php($status = $statusStyles[$appointment->status] ?? $statusStyles['pending'])
            <flux:card class="p-0!">
                <div class="flex items-start gap-4 p-5">
                    <div class="w-14 shrink-0 rounded-xl bg-violet-50 py-2 text-center dark:bg-violet-500/10"><div class="text-lg font-semibold text-violet-700 dark:text-violet-300">{{ $appointment->date_time->format('d') }}</div><div class="text-[10px] font-semibold uppercase tracking-wide text-violet-500">{{ $appointment->date_time->translatedFormat('M') }}</div></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2"><div class="min-w-0"><div class="truncate font-semibold text-zinc-950 dark:text-white">{{ $appointment->customer->name }}</div><flux:text size="sm">{{ $appointment->date_time->format('H:i') }} · {{ $appointment->service->name }}</flux:text></div>@include('livewire.partials.appointment-actions', ['appointment' => $appointment])</div>
                        <div class="mt-3 flex flex-wrap items-center gap-2"><flux:badge :color="$status['color']" :icon="$status['icon']">{{ $status['label'] }}</flux:badge><flux:badge color="zinc" icon="user">{{ $appointment->employee->name }}</flux:badge></div>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="py-12! text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 dark:bg-zinc-800"><flux:icon.calendar-days class="size-6 text-zinc-400" /></span><flux:heading size="lg" class="mt-4">Aucun rendez-vous</flux:heading><flux:text class="mt-1">Modifiez les filtres ou planifiez une nouvelle séance.</flux:text><flux:button wire:click="openCreateModal" variant="primary" color="violet" icon="plus" class="mt-5">Planifier</flux:button></flux:card>
        @endforelse
    </section>

    <flux:card class="hidden overflow-hidden p-0! md:block">
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800"><div><flux:heading size="lg">Agenda</flux:heading><flux:text size="sm" class="mt-1">{{ $appointments->count() }} résultat{{ $appointments->count() > 1 ? 's' : '' }} trié{{ $appointments->count() > 1 ? 's' : '' }} par date</flux:text></div><flux:badge color="violet" icon="calendar-days">Planning à jour</flux:badge></div>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns><flux:table.column><x-table-heading icon="calendar-days">Date et heure</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="sparkles">Prestation</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user-group">Équipe</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="signal">Statut</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="ellipsis-horizontal">Actions</x-table-heading></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse($appointments as $appointment)
                        @php($status = $statusStyles[$appointment->status] ?? $statusStyles['pending'])
                        <flux:table.row>
                            <flux:table.cell><div class="flex items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-sm font-semibold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">{{ $appointment->date_time->format('d') }}</span><div><div class="font-medium text-zinc-950 dark:text-white">{{ $appointment->date_time->translatedFormat('D d M') }}</div><div class="text-xs text-zinc-500">à {{ $appointment->date_time->format('H:i') }}</div></div></div></flux:table.cell>
                            <flux:table.cell><div class="font-semibold text-zinc-950 dark:text-white">{{ $appointment->customer->name }}</div><div class="mt-0.5 text-xs text-zinc-500">{{ $appointment->customer->phone ?: 'Téléphone non renseigné' }}</div></flux:table.cell>
                            <flux:table.cell><div class="max-w-56 truncate font-medium">{{ $appointment->service->name }}</div>@if($appointment->notes)<div class="mt-0.5 max-w-56 truncate text-xs text-zinc-500">{{ $appointment->notes }}</div>@endif</flux:table.cell>
                            <flux:table.cell><div class="flex items-center gap-2"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-semibold uppercase text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ mb_substr($appointment->employee->name, 0, 1) }}</span><span class="max-w-40 truncate">{{ $appointment->employee->name }}</span></div></flux:table.cell>
                            <flux:table.cell><flux:badge :color="$status['color']" :icon="$status['icon']">{{ $status['label'] }}</flux:badge></flux:table.cell>
                            <flux:table.cell align="end">@include('livewire.partials.appointment-actions', ['appointment' => $appointment])</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="6" class="py-14 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 dark:bg-zinc-800"><flux:icon.calendar-days class="size-6 text-zinc-400" /></span><flux:heading size="lg" class="mt-4">Agenda vide</flux:heading><flux:text class="mt-1">Aucun rendez-vous ne correspond à ces critères.</flux:text><flux:button wire:click="openCreateModal" variant="primary" color="violet" icon="plus" class="mt-5">Nouveau rendez-vous</flux:button></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <flux:modal wire:model="showCreateModal" variant="flyout" position="right" class="w-full md:w-[38rem]">
        <div class="flex items-start gap-4 border-b border-zinc-200 pb-5 pr-10 dark:border-zinc-800"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon.calendar-days class="size-5" /></span><div><flux:heading size="lg">Nouveau rendez-vous</flux:heading><flux:text class="mt-1">Renseignez le client, la prestation et le membre de l’équipe responsable.</flux:text></div></div>
        <form wire:submit="createAppointment" class="mt-6 space-y-6">
            <flux:fieldset legend="Client et prestation" description="Les informations essentielles de la séance."><div class="space-y-4"><div><flux:select wire:model="customerId" label="Client" required><flux:select.option value="">Sélectionner un client</flux:select.option>@foreach($customers as $customer)<flux:select.option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->phone }}</flux:select.option>@endforeach</flux:select><flux:error name="customerId" /></div><div><flux:select wire:model="serviceId" label="Prestation" required><flux:select.option value="">Sélectionner un service</flux:select.option>@foreach($services as $service)<flux:select.option value="{{ $service->id }}">{{ $service->name }} · {{ \App\Support\DisplayCurrency::format($service->price) }}</flux:select.option>@endforeach</flux:select><flux:error name="serviceId" /></div></div></flux:fieldset>
            <flux:fieldset legend="Planification" description="Choisissez le responsable et le créneau."><div class="grid gap-4 sm:grid-cols-2"><div><flux:select wire:model="employeeId" label="Membre de l’équipe" required><flux:select.option value="">Sélectionner</flux:select.option>@foreach($employees as $employee)<flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>@endforeach</flux:select><flux:error name="employeeId" /></div><div><flux:input type="datetime-local" wire:model="dateTime" label="Date et heure" required /><flux:error name="dateTime" /></div></div></flux:fieldset>
            <div><flux:textarea wire:model="notes" label="Notes internes" rows="4" placeholder="Préférences du client, préparation particulière…" description="Ces notes restent visibles par l’équipe." /><flux:error name="notes" /></div>
            <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800 sm:flex-row sm:justify-end"><flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet" icon="check">Planifier le rendez-vous</flux:button></div>
        </form>
    </flux:modal>
</div>
