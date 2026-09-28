<?php

namespace App\Livewire\Production;

use App\Enums\MachineStatus;
use App\Enums\MachineType;
use App\Enums\MaintenanceType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Machine;
use App\Models\MachineMaintenance;
use App\Models\PrintRoom;
use App\Models\Site;
use App\Services\Production\MachineService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Parc machines')]
class Machines extends Component
{
    use InteractsWithToasts;

    public string $search = '';

    public string $machineStatus = '';

    public string $machineType = '';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public ?int $machineId = null;

    /** @var array<string, string> */
    public array $maintenance = ['type' => 'preventive', 'technician' => '', 'description' => '', 'scheduled_on' => ''];

    public string $closingCost = '0';

    public function create(): void
    {
        $this->authorize('machines.create');
        $this->editingId = null;
        $this->form = ['code' => '', 'name' => '', 'type' => 'digital_printer', 'brand' => '', 'model' => '', 'serial_number' => '', 'site_id' => auth()->user()->default_site_id, 'print_room_id' => '', 'status' => 'operational', 'counter' => 0, 'maintenance_interval_days' => 90, 'purchase_cost' => 0, 'next_maintenance_at' => ''];
        $this->modal('machine')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('machines.update');
        $machine = Machine::query()->visibleTo(auth()->user())->findOrFail($id);
        $this->editingId = $id;
        $this->form = $machine->only(['code', 'name', 'brand', 'model', 'serial_number', 'site_id', 'print_room_id', 'counter', 'maintenance_interval_days', 'purchase_cost'])
            + ['type' => $machine->type->value, 'status' => $machine->status->value, 'next_maintenance_at' => $machine->next_maintenance_at?->toDateString()];
        $this->modal('machine')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'machines.update' : 'machines.create');

        $data = $this->validate([
            'form.code' => ['required', 'string', 'max:20', Rule::unique('machines', 'code')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.type' => ['required', Rule::enum(MachineType::class)],
            'form.brand' => ['nullable', 'string', 'max:255'],
            'form.model' => ['nullable', 'string', 'max:255'],
            'form.serial_number' => ['nullable', 'string', 'max:255'],
            'form.site_id' => ['required', Rule::in(auth()->user()->accessibleSiteIds())],
            'form.print_room_id' => ['nullable', 'exists:print_rooms,id'],
            'form.status' => ['required', Rule::enum(MachineStatus::class)],
            'form.counter' => ['required', 'integer', 'min:0'],
            'form.maintenance_interval_days' => ['required', 'integer', 'min:1'],
            'form.purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'form.next_maintenance_at' => ['nullable', 'date'],
        ])['form'];

        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
        Machine::query()->updateOrCreate(['id' => $this->editingId], $data);

        $this->modal('machine')->close();
        $this->toast('Machine enregistrée.');
    }

    public function openMaintenance(int $id): void
    {
        $this->authorize('machines.update');
        $this->machineId = $id;
        $this->modal('maintenance')->show();
    }

    public function startMaintenance(MachineService $service): void
    {
        $data = $this->validate([
            'maintenance.type' => ['required', Rule::enum(MaintenanceType::class)],
            'maintenance.technician' => ['nullable', 'string', 'max:255'],
            'maintenance.description' => ['required', 'string', 'max:2000'],
            'maintenance.scheduled_on' => ['nullable', 'date'],
        ])['maintenance'];

        $machine = Machine::query()->visibleTo(auth()->user())->findOrFail($this->machineId);
        $service->openMaintenance($machine, MaintenanceType::from($data['type']), ['technician' => $data['technician'] ?: null, 'description' => $data['description'], 'scheduled_on' => $data['scheduled_on'] ?: null], auth()->user());

        $this->modal('maintenance')->close();
        $this->reset('maintenance');
        $this->toast('Intervention ouverte : machine immobilisée.');
    }

    public function closeMaintenance(int $maintenanceId, MachineService $service): void
    {
        $this->authorize('machines.update');
        $this->validate(['closingCost' => ['required', 'numeric', 'min:0']]);
        $maintenance = MachineMaintenance::query()->findOrFail($maintenanceId);
        $service->closeMaintenance($maintenance, (float) $this->closingCost);
        $this->reset('closingCost');
        $this->toast('Maintenance clôturée : machine opérationnelle.');
    }

    public function render()
    {
        return view('livewire.production.machines', [
            'machines' => Machine::query()->visibleTo(auth()->user())->with('printRoom', 'site', 'maintenances')->withSum('maintenances', 'cost')->withSum('maintenances', 'downtime_hours')
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('brand', 'like', "%{$this->search}%")->orWhere('model', 'like', "%{$this->search}%")))
                ->when($this->machineStatus, fn ($q) => $q->where('status', $this->machineStatus))
                ->when($this->machineType, fn ($q) => $q->where('type', $this->machineType))
                ->orderBy('code')->get(),
            'types' => MachineType::options(),
            'statuses' => MachineStatus::options(),
            'maintenanceTypes' => MaintenanceType::options(),
            'printRooms' => PrintRoom::query()->visibleTo(auth()->user())->get(),
            'sites' => auth()->user()->hasConsolidatedView() ? Site::all() : auth()->user()->sites,
        ]);
    }
}
