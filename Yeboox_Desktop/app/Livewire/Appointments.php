<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Livewire\Component;

class Appointments extends Component
{
    // Creation parameters
    public $customerId;

    public $employeeId;

    public $serviceId;

    public $dateTime;

    public $notes;

    // Filters
    public $filterEmployeeId = '';

    public $filterStatus = '';

    public $filterDate = '';

    // Modals
    public $showCreateModal = false;

    protected $rules = [
        'customerId' => 'required|exists:customers,id',
        'employeeId' => 'required|exists:users,id',
        'serviceId' => 'required|exists:products,id',
        'dateTime' => 'required|date',
        'notes' => 'nullable|string|max:1000',
    ];

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->dateTime = now()->addHour()->startOfHour()->format('Y-m-d\TH:i');
        $this->showCreateModal = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['filterEmployeeId', 'filterStatus', 'filterDate']);
    }

    public function createAppointment()
    {
        $this->validate();

        $appointment = Appointment::create([
            'customer_id' => $this->customerId,
            'employee_id' => $this->employeeId,
            'service_id' => $this->serviceId,
            'date_time' => Carbon::parse($this->dateTime),
            'status' => 'pending',
            'notes' => $this->notes,
        ]);

        ActivityLog::log('Création Rendez-vous', "Rendez-vous #{$appointment->id} créé pour le ".$appointment->date_time->format('d/m/Y H:i'));

        $this->reset(['customerId', 'employeeId', 'serviceId', 'dateTime', 'notes']);
        $this->showCreateModal = false;
        $this->dispatch('notify', 'Rendez-vous enregistré avec succès.');
    }

    public function updateStatus($appointmentId, $status)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $oldStatus = $appointment->status;
        $appointment->update(['status' => $status]);

        ActivityLog::log('Statut Rendez-vous', "Statut du rendez-vous #{$appointment->id} changé de {$oldStatus} à {$status}");

        $this->dispatch('notify', 'Le statut du rendez-vous a été mis à jour.');
    }

    public function deleteAppointment($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->delete();

        ActivityLog::log('Suppression Rendez-vous', "Rendez-vous #{$appointmentId} supprimé.");
        $this->dispatch('notify', 'Le rendez-vous a été supprimé.');
    }

    public function exportAppointments()
    {
        $query = Appointment::with(['customer', 'employee', 'service']);

        if ($this->filterEmployeeId) {
            $query->where('employee_id', $this->filterEmployeeId);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDate) {
            $date = Carbon::parse($this->filterDate);
            $query->whereBetween('date_time', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        }

        $appointments = $query->orderBy('date_time', 'asc')->get();

        $csvFileName = 'rendez_vous_'.date('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($appointments) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Date & Heure', 'Client', 'Telephone', 'Service', 'Employe Assigne', 'Statut', 'Notes'], ';');

            foreach ($appointments as $appt) {
                fputcsv($handle, [
                    $appt->date_time->format('d/m/Y H:i'),
                    $appt->customer->name,
                    $appt->customer->phone,
                    $appt->service->name,
                    $appt->employee->name,
                    $appt->status,
                    $appt->notes,
                ], ';');
            }

            fclose($handle);
        }, $csvFileName);
    }

    public function render()
    {
        $query = Appointment::with(['customer', 'employee', 'service']);

        if ($this->filterEmployeeId) {
            $query->where('employee_id', $this->filterEmployeeId);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDate) {
            $date = Carbon::parse($this->filterDate);
            $query->whereBetween('date_time', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        }

        $appointments = $query->orderBy('date_time', 'asc')->get();
        $customers = Customer::orderBy('name')->get();
        // Interroger directement la relation évite une erreur 500 lorsque
        // une ancienne base locale ne contient pas encore tous les rôles.
        $employees = User::query()
            ->where('company_id', app(TenantContext::class)->companyId())
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['Caissier', 'Gérant', 'Super administrateur']))
            ->orderBy('name')
            ->get();
        $services = Product::where('type', 'service')->orderBy('name')->get();

        return view('livewire.appointments', [
            'appointments' => $appointments,
            'customers' => $customers,
            'employees' => $employees,
            'services' => $services,
        ])->layout('layouts.app');
    }
}
