<?php

namespace App\Services\Production;

use App\Enums\MachineStatus;
use App\Enums\OrderStatus;
use App\Enums\PrintJobStatus;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Machine;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use App\Services\Sales\OrderWorkflow;
use Illuminate\Support\Facades\DB;

/**
 * Salle d'impression : File d'attente → Préparation → Impression → Finition → Contrôle qualité → Prête.
 */
class PrintJobService
{
    /**
     * @var array<string, PrintJobStatus>
     */
    private const NEXT = [
        'queued' => PrintJobStatus::Preparing,
        'preparing' => PrintJobStatus::Printing,
        'printing' => PrintJobStatus::Finishing,
        'finishing' => PrintJobStatus::QualityCheck,
        'rework' => PrintJobStatus::Preparing,
    ];

    public function __construct(private OrderWorkflow $workflow, private StockService $stock) {}

    public function start(PrintJob $job, User $operator, Machine $machine): PrintJob
    {
        if (! in_array($job->status, [PrintJobStatus::Queued, PrintJobStatus::Rework], true)) {
            throw new BusinessRuleException('Ce travail est déjà en production.');
        }

        if ($machine->status !== MachineStatus::Operational) {
            throw new BusinessRuleException("La machine {$machine->code} n’est pas opérationnelle ({$machine->status->label()}).");
        }

        $job->update([
            'operator_id' => $operator->id,
            'machine_id' => $machine->id,
            'status' => PrintJobStatus::Preparing,
            'started_at' => $job->started_at ?? now(),
        ]);

        $this->syncOrder($job, $operator);

        return $job;
    }

    public function advance(PrintJob $job, User $operator): PrintJob
    {
        $next = self::NEXT[$job->status->value] ?? throw new BusinessRuleException("Aucune étape suivante depuis « {$job->status->label()} ».");

        $job->update(['status' => $next]);
        $this->syncOrder($job, $operator);

        return $job;
    }

    /**
     * Consommation de matières (papier, encre…) : déstockage « Consommation production ».
     */
    public function consume(PrintJob $job, Product $product, Warehouse $warehouse, float $quantity, float $waste, User $operator): void
    {
        DB::transaction(function () use ($job, $product, $warehouse, $quantity, $waste, $operator): void {
            $movement = $this->stock->move($product, $warehouse, StockMovementType::ProductionConsumption, $quantity + $waste, $job, "Production {$job->number}", $operator);

            $job->consumptions()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'waste_quantity' => $waste,
                'stock_movement_id' => $movement?->id,
            ]);
        });
    }

    public function recordOutput(PrintJob $job, int $produced, int $waste, ?string $observations = null): PrintJob
    {
        $job->update([
            'produced_quantity' => $produced,
            'waste_quantity' => $waste,
            'observations' => $observations ?? $job->observations,
        ]);

        if ($job->machine) {
            $job->machine->increment('counter', $produced + $waste);
        }

        return $job;
    }

    public function qualityCheck(PrintJob $job, User $controller, bool $passed, ?string $observations = null): PrintJob
    {
        if ($job->status !== PrintJobStatus::QualityCheck) {
            throw new BusinessRuleException('Le travail n’est pas au contrôle qualité.');
        }

        if (! $passed && blank($observations)) {
            throw new BusinessRuleException('Décrivez le défaut constaté.');
        }

        $job->update([
            'status' => $passed ? PrintJobStatus::Ready : PrintJobStatus::Rework,
            'quality_passed' => $passed,
            'quality_checked_by' => $controller->id,
            'finished_at' => $passed ? now() : null,
            'observations' => $observations ?? $job->observations,
        ]);

        $this->syncOrder($job, $controller, $observations);

        return $job;
    }

    /**
     * Répercute l'avancement des travaux sur le statut de la commande.
     */
    private function syncOrder(PrintJob $job, User $user, ?string $comment = null): void
    {
        $order = $job->order->refresh();
        $statuses = $order->printJobs()->pluck('status')->map(fn ($status) => $status instanceof PrintJobStatus ? $status : PrintJobStatus::from($status));

        $target = match (true) {
            $statuses->contains(PrintJobStatus::Rework) => OrderStatus::Rework,
            $statuses->every(fn (PrintJobStatus $s): bool => $s === PrintJobStatus::Ready) => OrderStatus::Ready,
            $statuses->contains(fn (PrintJobStatus $s): bool => $s === PrintJobStatus::QualityCheck) && $statuses->every(fn (PrintJobStatus $s): bool => in_array($s, [PrintJobStatus::QualityCheck, PrintJobStatus::Ready], true)) => OrderStatus::QualityCheck,
            $statuses->contains(fn (PrintJobStatus $s): bool => $s !== PrintJobStatus::Queued) => OrderStatus::Printing,
            default => null,
        };

        if ($target === null || $order->status === $target) {
            return;
        }

        $path = match ($target) {
            OrderStatus::Printing => [OrderStatus::Printing],
            OrderStatus::QualityCheck => [OrderStatus::Printing, OrderStatus::QualityCheck],
            OrderStatus::Ready => [OrderStatus::Printing, OrderStatus::QualityCheck, OrderStatus::Ready],
            OrderStatus::Rework => [OrderStatus::Printing, OrderStatus::QualityCheck, OrderStatus::Rework],
            default => [$target],
        };

        if ($order->status === OrderStatus::Rework) {
            $path = [OrderStatus::PrintQueue, ...$path];
        }

        $position = array_search($order->status, $path, true);
        $remaining = $position === false ? $path : array_slice($path, $position + 1);

        foreach ($remaining as $status) {
            if ($order->status->canTransitionTo($status)) {
                $this->workflow->transition($order, $status, $user, $comment);
            }
        }
    }
}
