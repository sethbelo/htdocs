<?php

namespace App\Services;

use App\Actions\Documents\CreateDocument;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Equipment;
use App\Models\Rental;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Location de matériel : réservation, départ, retour, facturation.
 */
class RentalService
{
    public function __construct(private readonly SequenceService $sequences, private readonly CreateDocument $documents) {}

    public function create(array $data, array $equipmentIds): Rental
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        if ($end->lt($start)) {
            throw new DomainException('La date de retour précède la date de départ.');
        }
        $days = max(1, $start->diffInDays($end) + 1);

        return DB::transaction(function () use ($data, $equipmentIds, $days, $start, $end) {
            $rental = Rental::create($data + ['company_id' => company_id(), 'number' => $this->sequences->next('rental'), 'status' => 'reserved']);
            $total = 0;
            foreach (Equipment::whereIn('id', $equipmentIds)->get() as $equipment) {
                $busy = $rental->newQuery()->whereKeyNot($rental->id)->whereIn('status', ['reserved', 'ongoing'])
                    ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)
                    ->whereHas('lines', fn ($q) => $q->where('equipment_id', $equipment->id))->exists();
                if ($busy || in_array($equipment->status, ['maintenance', 'out_of_service'], true)) {
                    throw new DomainException("« {$equipment->name} » n’est pas disponible sur cette période.");
                }
                $lineTotal = round($days * (float) $equipment->daily_rent_price, 2);
                $total += $lineTotal;
                $rental->lines()->create(['equipment_id' => $equipment->id, 'daily_price' => $equipment->daily_rent_price, 'days' => $days, 'total' => $lineTotal]);
            }
            $rental->update(['total' => $total]);

            return $rental->load('lines.equipment');
        });
    }

    public function start(Rental $rental): void
    {
        if ($rental->status !== 'reserved') {
            throw new DomainException('Seule une réservation peut démarrer.');
        }
        DB::transaction(function () use ($rental) {
            Equipment::whereIn('id', $rental->lines->pluck('equipment_id'))->update(['status' => 'rented']);
            $rental->update(['status' => 'ongoing']);
        });
    }

    public function return(Rental $rental, array $conditions = []): void
    {
        if ($rental->status !== 'ongoing') {
            throw new DomainException('Cette location n’est pas en cours.');
        }
        DB::transaction(function () use ($rental, $conditions) {
            foreach ($rental->lines as $line) {
                $line->update(['return_condition' => $conditions[$line->id] ?? 'Bon état']);
            }
            Equipment::whereIn('id', $rental->lines->pluck('equipment_id'))->update(['status' => 'available']);
            $rental->update(['status' => 'returned', 'returned_at' => now()->toDateString()]);
        });
    }

    public function invoice(Rental $rental)
    {
        if (! in_array($rental->status, ['ongoing', 'returned'], true) || $rental->invoice_id) {
            throw new DomainException('Location déjà facturée ou non démarrée.');
        }

        return DB::transaction(function () use ($rental) {
            $invoice = $this->documents->handle([
                'company_id' => $rental->company_id,
                'contact_id' => $rental->contact_id,
                'created_by' => auth()->id(),
                'type' => DocumentType::Invoice,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'currency_code' => 'USD',
                'exchange_rate' => 1,
                'notes' => "Location {$rental->number} du {$rental->start_date->format('d/m/Y')} au {$rental->end_date->format('d/m/Y')}",
                'template' => erp_setting('templates.invoice', 'moderne'),
            ], $rental->lines->map(fn ($l, $i) => [
                'description' => 'Location '.$l->equipment->name.' ('.$l->days.' j)', 'quantity' => $l->days, 'unit' => 'jour',
                'unit_price' => $l->daily_price, 'tax_rate' => 16, 'position' => $i,
            ])->all());
            $invoice->update(['status' => DocumentStatus::Sent]);
            $rental->update(['invoice_id' => $invoice->id, 'status' => $rental->status === 'returned' ? 'invoiced' : $rental->status]);

            return $invoice;
        });
    }
}
