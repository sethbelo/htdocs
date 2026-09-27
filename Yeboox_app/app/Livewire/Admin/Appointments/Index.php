<?php

namespace App\Livewire\Admin\Appointments;

use App\Models\Appointment;
use App\Models\Customer;
use App\Services\PlanningDataExchange;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $activeTab = 'agenda';

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

    public string $customer_id = '';

    public string $client_name = '';

    public string $client_phone = '';

    public string $client_email = '';

    public string $service_name = '';

    public string $staff_name = '';

    public string $appointment_date = '';

    public string $appointment_time = '';

    public int $duration_minutes = 60;

    public string $status = 'scheduled';

    public string $notes = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->establishment?->appointmentsModuleEnabled(), 403);
        abort_unless(auth()->user()?->can('appointments.view'), 403);
    }

    public function mount(): void
    {
        $this->calendarMonth = now()->format('Y-m');
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['agenda', 'list'], true)) {
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

    public function updatedCustomerId(string $customerId): void
    {
        if ($customerId === '') {
            return;
        }

        $customer = Customer::forEstablishment()->find($customerId);
        if ($customer) {
            $this->client_name = $customer->full_name;
            $this->client_phone = $customer->phone ?? '';
            $this->client_email = $customer->email ?? '';
        }
    }

    public function openForm(?string $id = null): void
    {
        abort_unless(auth()->user()->can($id ? 'appointments.edit' : 'appointments.create'), 403);
        $this->resetForm();

        if ($id) {
            $appointment = Appointment::forEstablishment()->findOrFail($id);
            $this->editingId = $appointment->id;
            $this->customer_id = $appointment->customer_id ?? '';
            $this->client_name = $appointment->client_name;
            $this->client_phone = $appointment->client_phone;
            $this->client_email = $appointment->client_email ?? '';
            $this->service_name = $appointment->service_name;
            $this->staff_name = $appointment->staff_name ?? '';
            $this->appointment_date = $appointment->appointment_date->format('Y-m-d');
            $this->appointment_time = substr($appointment->appointment_time, 0, 5);
            $this->duration_minutes = $appointment->duration_minutes;
            $this->status = $appointment->status;
            $this->notes = $appointment->notes ?? '';
        } else {
            $this->appointment_date = today()->format('Y-m-d');
            $this->appointment_time = now()->addHour()->format('H:00');
        }

        $this->showFormModal = true;
    }

    public function openImport(): void
    {
        abort_unless(auth()->user()->can('appointments.import'), 403);
        $this->reset(['importFile', 'importErrors']);
        $this->showImportModal = true;
    }

    public function importExcel(PlanningDataExchange $exchange): void
    {
        abort_unless(auth()->user()->can('appointments.import'), 403);
        $this->validate(['importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        $result = $exchange->import('appointments', $this->importFile->getRealPath(), auth()->user()->establishment);
        $this->importErrors = $result['errors'];
        $this->reset('importFile');
        if ($result['imported'] > 0) {
            session()->flash('success', $result['imported'].' rendez-vous importé(s).');
        }
        if ($this->importErrors === []) {
            $this->showImportModal = false;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->editingId ? 'appointments.edit' : 'appointments.create'), 403);
        $establishmentId = auth()->user()->establishment_id;
        $validated = $this->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('establishment_id', $establishmentId)],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:40'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'service_name' => ['required', 'string', 'max:255'],
            'staff_name' => ['nullable', 'string', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'status' => ['required', 'in:scheduled,confirmed,in_progress,completed,cancelled,no_show'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['customer_id'] = $validated['customer_id'] ?: null;
        $validated['establishment_id'] = $establishmentId;
        $validated['branch_id'] = auth()->user()->branch_id;
        $validated['created_by'] = auth()->id();

        $appointment = $this->editingId
            ? Appointment::forEstablishment()->findOrFail($this->editingId)
            : new Appointment;
        $appointment->fill($validated)->save();

        $this->showFormModal = false;
        session()->flash('success', $this->editingId ? 'Rendez-vous modifié.' : 'Rendez-vous créé.');
        $this->resetForm();
    }

    public function updateStatus(string $id, string $status): void
    {
        abort_unless(auth()->user()->can('appointments.edit'), 403);
        abort_unless(in_array($status, ['confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'], true), 422);
        Appointment::forEstablishment()->findOrFail($id)->update(['status' => $status]);
    }

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('appointments.delete'), 403);
        $appointment = Appointment::forEstablishment()->findOrFail($id);
        $this->deleteId = $appointment->id;
        $this->deleteName = $appointment->client_name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('appointments.delete'), 403);
        Appointment::forEstablishment()->findOrFail($this->deleteId)->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Rendez-vous supprimé.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'customer_id', 'client_name', 'client_phone', 'client_email',
            'service_name', 'staff_name', 'appointment_date', 'appointment_time', 'notes',
        ]);
        $this->duration_minutes = 60;
        $this->status = 'scheduled';
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
        $appointments = Appointment::query()
            ->forEstablishment()
            ->with('customer')
            ->when($this->dateFilter, fn ($query) => $query->whereDate('appointment_date', $this->dateFilter))
            ->when(! $this->dateFilter, fn ($query) => $query->whereDate('appointment_date', '>=', now()->subDay()))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(15);

        $month = $this->monthAnchor();
        $calendarStart = $month->startOfWeek();
        $calendarEnd = $month->endOfMonth()->endOfWeek();
        $calendarAppointments = Appointment::query()
            ->forEstablishment()
            ->whereBetween('appointment_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('appointment_time')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->appointment_date->toDateString());

        $calendarDays = [];
        for ($day = $calendarStart; $day->lte($calendarEnd); $day = $day->addDay()) {
            $calendarDays[] = [
                'date' => $day,
                'currentMonth' => $day->month === $month->month,
                'today' => $day->isToday(),
            ];
        }

        $reminderHours = max(1, (int) auth()->user()->establishment->getSetting('appointment_reminder_hours', 24));
        $reminderLimit = now()->addHours($reminderHours);
        $remindersDue = Appointment::query()
            ->forEstablishment()
            ->upcoming()
            ->whereNull('reminder_sent_at')
            ->whereBetween('appointment_date', [today()->toDateString(), $reminderLimit->toDateString()])
            ->get()
            ->filter(fn (Appointment $appointment) => $appointment->starts_at->between(now(), $reminderLimit))
            ->count();

        return view('livewire.admin.appointments.index', [
            'appointments' => $appointments,
            'calendarAppointments' => $calendarAppointments,
            'calendarDays' => $calendarDays,
            'calendarTitle' => $month->translatedFormat('F Y'),
            'customers' => Customer::forEstablishment()->active()->orderBy('first_name')->get(),
            'todayCount' => Appointment::forEstablishment()->whereDate('appointment_date', today())->count(),
            'remindersDue' => $remindersDue,
            'reminderHours' => $reminderHours,
        ])->layout('layouts.admin', ['title' => 'Rendez-vous', 'breadcrumb' => 'Rendez-vous']);
    }
}
