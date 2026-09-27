<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query->latest('ordered_at');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['N° commande', 'Date', 'Client', 'Siège', 'Commercial', 'Priorité', 'Statut', 'Délai', 'Total', 'Payé', 'Reste'];
    }

    /**
     * @param  Order  $order
     * @return list<mixed>
     */
    public function map($order): array
    {
        return [
            $order->number,
            $order->ordered_at->format('d/m/Y H:i'),
            $order->client->displayName(),
            $order->site->name,
            $order->salesRep?->name,
            $order->priority->label(),
            $order->status->label(),
            $order->due_at?->format('d/m/Y H:i'),
            (float) $order->total,
            (float) $order->amount_paid,
            $order->balanceDue(),
        ];
    }
}
