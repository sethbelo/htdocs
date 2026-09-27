<?php

namespace App\Services\Production;

use App\Enums\MachineStatus;
use App\Enums\MaintenanceType;
use App\Models\Machine;
use App\Models\MachineMaintenance;
use App\Models\User;

class MachineService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function openMaintenance(Machine $machine, MaintenanceType $type, array $data, User $user): MachineMaintenance
    {
        $maintenance = $machine->maintenances()->create($data + [
            'type' => $type,
            'status' => 'in_progress',
            'started_at' => now(),
            'counter_reading' => $machine->counter,
            'user_id' => $user->id,
        ]);

        $machine->update(['status' => $type === MaintenanceType::Breakdown ? MachineStatus::Broken : MachineStatus::Maintenance]);

        return $maintenance;
    }

    public function closeMaintenance(MachineMaintenance $maintenance, float $cost, ?string $description = null): MachineMaintenance
    {
        $completedAt = now();

        $maintenance->update([
            'status' => 'done',
            'completed_at' => $completedAt,
            'cost' => $cost,
            'downtime_hours' => round(($maintenance->started_at ?? $completedAt)->diffInMinutes($completedAt) / 60, 2),
            'description' => $description ?? $maintenance->description,
        ]);

        $machine = $maintenance->machine;
        $machine->update([
            'status' => MachineStatus::Operational,
            'last_maintenance_at' => $completedAt->toDateString(),
            'next_maintenance_at' => $completedAt->copy()->addDays($machine->maintenance_interval_days)->toDateString(),
        ]);

        return $maintenance;
    }
}
