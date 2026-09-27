<?php

namespace App\Livewire\Admin\Reservations;

use App\Models\Reservation;
use App\Models\Table;
use App\Services\PlanningDataExchange;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $activeTab = 'planning';

    public string $calendarMonth = '';

    public string $dateFilter = '';

    public string $statusFilter = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showImportModal = false;

    public $importFile;

    public array $importErrors = [];

    public ?string $editingId = null;

    public ?string $deleteId = null;

    public string $deleteName = '';

    public string $guest_name = '';

    public string $guest_phone = '';

    public string $guest_email = '';

    public string $table_id = '';

    public string $reservation_date = '';

    public string $reservation_time = '';

    public int $duration_minutes = 120;

    public int $party_size = 2;

    public string $status = 'pending';

    public string $special_requests = '';

    public function boot(): void
    {
        abort_unless(
            auth()->user()?->establishment?->reservationsModuleEnabled(),
            403,
            'Le module Réservations est désactivé pour cet établissement.'
        );
    }

    public function mount(): void
    {
        $this->calendarMonth = now()->format('Y-m');
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['planning', 'list'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = $this->monthAnchor()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = $this->monthAnchor()->addMonth()->format('Y-m');
    }

    public function goToCurrentMonth(): void
    {
        $this->calendarMonth = now()->format('Y-m');
    }

    public function showDay(string $date): void
    {
        $this->dateFilter = CarbonImmutable::parse($date)->format('Y-m-d');
        $this->activeTab = 'list';
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['dateFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function confirm(string $id): void
    {
        Reservation::forEstablishment()->findOrFail($id)->confirm();
        session()->flash('success', 'Réservation confirmée.');
    }

    public function cancel(string $id): void
    {
        Reservation::forEstablishment()->findOrFail($id)->cancel();
        session()->flash('success', 'Réservation annulée.');
    }

    public function markSeated(string $id): void
    {
        Reservation::forEstablishment()->findOrFail($id)->markSeated();
    }

    public function markNoShow(string $id): void
    {
        Reservation::forEstablishment()->findOrFail($id)->markNoShow();
    }

    public function openForm(?string $id = null): void
    {
        abort_unless(auth()->user()->can($id ? 'reservations.edit' : 'reservations.create'), 403);
        $this->resetForm();
        if ($id) {
            $reservation = Reservation::forEstablishment()->findOrFail($id);
            $this->editingId = $reservation->id;
            $this->guest_name = $reservation->guest_name;
            $this->guest_phone = $reservation->guest_phone ?? '';
            $this->guest_email = $reservation->guest_email ?? '';
            $this->table_id = $reservation->table_id ?? '';
            $this->reservation_date = $reservation->reservation_date->format('Y-m-d');
            $this->reservation_time = substr($reservation->reservation_time, 0, 5);
            $this->duration_minutes = $reservation->duration_minutes;
            $this->party_size = $reservation->party_size;
            $this->status = $reservation->status;
            $this->special_requests = $reservation->special_requests ?? '';
        } else {
            $this->reservation_date = today()->format('Y-m-d');
            $this->reservation_time = now()->addHour()->format('H:00');
        }
        $this->showFormModal = true;
    }

    public function openImport(): void
    {
        abort_unless(auth()->user()->can('reservations.import'), 403);
        $this->reset(['importFile', 'importErrors']);
        $this->showImportModal = true;
    }

    public function importExcel(PlanningDataExchange $exchange): void
    {
        abort_unless(auth()->user()->can('reservations.import'), 403);
        $this->validate(['importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        $result = $exchange->import('reservations', $this->importFile->getRealPath(), auth()->user()->establishment);
        $this->importErrors = $result['errors'];
        $this->reset('importFile');
        if ($result['imported'] > 0) {
            session()->flash('success', $result['imported'].' réservation(s) importée(s).');
        }
        if ($this->importErrors === []) {
            $this->showImportModal = false;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->editingId ? 'reservations.edit' : 'reservations.create'), 403);
        $establishmentId = auth()->user()->establishment_id;
        $validated = $this->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'table_id' => ['nullable', Rule::exists('tables', 'id')->where('establishment_id', $establishmentId)],
            'reservation_date' => ['required', 'date'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'party_size' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', 'in:pending,confirmed,seated,completed,cancelled,no_show'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['table_id'] = $validated['table_id'] ?: null;
        $validated['establishment_id'] = $establishmentId;
        $validated['branch_id'] = auth()->user()->branch_id;
        $validated['created_by'] = auth()->id();

        $reservation = $this->editingId ? Reservation::forEstablishment()->findOrFail($this->editingId) : new Reservation;
        $reservation->fill($validated)->save();
        $this->showFormModal = false;
        session()->flash('success', $this->editingId ? 'Réservation modifiée.' : 'Réservation créée.');
        $this->resetForm();
    }

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('reservations.delete'), 403);
        $reservation = Reservation::forEstablishment()->findOrFail($id);
        $this->deleteId = $reservation->id;
        $this->deleteName = $reservation->guest_name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('reservations.delete'), 403);
        Reservation::forEstablishment()->findOrFail($this->deleteId)->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Réservation supprimée.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'guest_name', 'guest_phone', 'guest_email', 'table_id', 'reservation_date', 'reservation_time', 'special_requests']);
        $this->duration_minutes = 120;
        $this->party_size = 2;
        $this->status = 'pending';
        $this->resetValidation();
    }

    private function monthAnchor(): CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $this->calendarMonth.'-01')->startOfMonth();
        } catch (\Throwable) {
            return CarbonImmutable::now()->startOfMonth();
        }
    }

    public function render()
    {
        $reservations = Reservation::query()
            ->forEstablishment()
            ->with(['table', 'customer'])
            ->when($this->dateFilter, fn ($q) => $q->forDate($this->dateFilter))
            ->when(! $this->dateFilter, fn ($q) => $q->where('reservation_date', '>=', now()->subDay()))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->paginate(15);

        $todayCount = Reservation::forEstablishment()->forDate(now()->toDateString())->count();
        $pendingCount = Reservation::forEstablishment()->pending()->where('reservation_date', '>=', now()->toDateString())->count();
        $tables = Table::forEstablishment()->active()->orderBy('name')->get();

        $month = $this->monthAnchor();
        $calendarStart = $month->startOfWeek();
        $calendarEnd = $month->endOfMonth()->endOfWeek();
        $calendarReservations = Reservation::query()
            ->forEstablishment()
            ->with('table')
            ->whereBetween('reservation_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('reservation_time')
            ->get()
            ->groupBy(fn (Reservation $reservation) => $reservation->reservation_date->toDateString());

        $calendarDays = [];
        for ($day = $calendarStart; $day->lte($calendarEnd); $day = $day->addDay()) {
            $calendarDays[] = [
                'date' => $day,
                'currentMonth' => $day->month === $month->month,
                'today' => $day->isToday(),
            ];
        }
        $calendarTitle = $month->translatedFormat('F Y');

        return view('livewire.admin.reservations.index', compact(
            'reservations',
            'todayCount',
            'pendingCount',
            'tables',
            'calendarDays',
            'calendarReservations',
            'calendarTitle',
        ))
            ->layout('layouts.admin', ['title' => 'Réservations', 'breadcrumb' => 'Réservations']);
    }
}
