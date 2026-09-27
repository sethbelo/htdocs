<?php

namespace App\Services\Sales;

use App\Enums\DesignJobStatus;
use App\Enums\OrderStatus;
use App\Enums\PrintJobStatus;
use App\Models\DesignJob;
use App\Models\Order;
use App\Models\PrintJob;
use App\Models\PrintRoom;
use App\Models\User;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Transfert automatique d'une commande payée vers le Graphisme ou directement vers la salle d'impression.
 */
class ProductionRouter
{
    public function __construct(private OrderWorkflow $workflow, private NumberGenerator $numbers) {}

    public function route(Order $order, ?User $user = null): Order
    {
        if ($order->status !== OrderStatus::Paid) {
            return $order;
        }

        return DB::transaction(function () use ($order, $user): Order {
            if ($order->requires_design) {
                DesignJob::query()->firstOrCreate(
                    ['order_id' => $order->id, 'status' => DesignJobStatus::ToDesign],
                    [],
                );

                return $this->workflow->transition($order, OrderStatus::InDesign, $user, 'Transfert automatique au Graphisme');
            }

            $this->createPrintJobs($order);

            return $this->workflow->transition($order, OrderStatus::PrintQueue, $user, 'Transfert automatique en file d’impression');
        });
    }

    public function createPrintJobs(Order $order, ?int $proofId = null): void
    {
        $printRoomId = PrintRoom::query()->where('site_id', $order->site_id)->where('is_active', true)->value('id');

        foreach ($order->items as $item) {
            $exists = PrintJob::query()->where('order_item_id', $item->id)
                ->whereNotIn('status', [PrintJobStatus::Ready])
                ->exists();

            if ($exists) {
                continue;
            }

            PrintJob::query()->create([
                'number' => $this->numbers->next(NumberGenerator::PrintJob),
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'proof_id' => $proofId,
                'print_room_id' => $printRoomId,
                'status' => PrintJobStatus::Queued,
                'planned_quantity' => (int) ceil((float) $item->quantity),
            ]);
        }
    }
}
