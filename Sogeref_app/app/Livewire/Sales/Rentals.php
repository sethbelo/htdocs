<?php

namespace App\Livewire\Sales;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Equipment;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Location de matériel : réservations, départs, retours et facturation,
 * avec planning de disponibilité du parc.
 */
#[Title('Location de matériel')]
class Rentals extends Component
{
    use InteractsWithUi;

    #[Url(as: 'statut')]
    public string $status = '';

    public bool $showForm = false;

    public ?int $contactId = null;

    public string $startDate = '';

    public string $endDate = '';

    public string $deposit = '0';

    public string $notes = '';

    public array $equipmentIds = [];

    public function create(): void
    {
        $this->authorizeModule('rentals', 'manage');
        $this->reset(['contactId', 'notes', 'equipmentIds']);
        $this->startDate = now()->toDateString();
        $this->endDate = now()->addDays(3)->toDateString();
        $this->deposit = '0';
        $this->showForm = true;
    }

    public function save(RentalService $service): void
    {
        $this->authorizeModule('rentals', 'manage');
        $this->validate(['contactId' => ['required', 'integer'], 'startDate' => ['required', 'date'], 'endDate' => ['required', 'date', 'after_or_equal:startDate'], 'equipmentIds' => ['required', 'array', 'min:1'], 'deposit' => ['numeric', 'min:0']], [], ['contactId' => 'client', 'equipmentIds' => 'matériel']);
        $rental = $this->attempt(fn () => $service->create(['contact_id' => $this->contactId, 'start_date' => $this->startDate, 'end_date' => $this->endDate, 'deposit' => $this->deposit, 'notes' => $this->notes ?: null], $this->equipmentIds));
        if ($rental) {
            $this->showForm = false;
            $this->toast("Réservation {$rental->number} enregistrée — ".money($rental->total).'.');
        }
    }

    public function act(int $id, string $action): void
    {
        $this->authorizeModule('rentals', 'manage');
        $service = app(RentalService::class);
        $rental = Rental::with('lines.equipment')->where('company_id', company_id())->findOrFail($id);
        match ($action) {
            'start' => $this->attempt(fn () => $service->start($rental), 'Départ du matériel enregistré.'),
            'return' => $this->attempt(fn () => $service->return($rental), 'Retour enregistré : matériel de nouveau disponible.'),
            'invoice' => ($invoice = $this->attempt(fn () => $service->invoice($rental))) && $this->toast("Facture {$invoice->number} émise."),
            'cancel' => $rental->status === 'reserved' ? tap($rental->update(['status' => 'cancelled']), fn () => $this->toast('Réservation annulée.', 'warning')) : $this->toast('Seule une réservation peut être annulée.', 'error'),
            default => null,
        };
    }

    public function render()
    {
        $cid = company_id();
        $days = collect(range(0, 13))->map(fn ($d) => today()->addDays($d - 2));
        $active = Rental::with('lines')->where('company_id', $cid)->whereIn('status', ['reserved', 'ongoing'])->get();

        return view('livewire.sales.rentals', [
            'rentals' => Rental::with('contact', 'lines.equipment', 'invoice')->where('company_id', $cid)->when($this->status, fn ($q) => $q->where('status', $this->status))->latest('start_date')->get(),
            'fleet' => Equipment::where('company_id', $cid)->where('is_rentable', true)->orderBy('name')->get(),
            'days' => $days,
            'bookings' => $active->flatMap(fn ($r) => $r->lines->map(fn ($l) => ['equipment_id' => $l->equipment_id, 'start' => $r->start_date, 'end' => $r->end_date, 'number' => $r->number, 'status' => $r->status])),
            'clients' => Contact::where('company_id', $cid)->where('type', 'client')->orderByRaw('COALESCE(company_name, last_name)')->get(),
            'estimate' => $this->showForm && $this->startDate && $this->endDate
                ? max(1, Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1) * (float) Equipment::whereIn('id', $this->equipmentIds ?: [0])->sum('daily_rent_price') : 0,
            'stats' => ['ongoing' => $active->where('status', 'ongoing')->count(), 'reserved' => $active->where('status', 'reserved')->count(), 'late' => $active->filter->isLate()->count(), 'month' => (float) Rental::where('company_id', $cid)->whereNotIn('status', ['cancelled'])->where('start_date', '>=', now()->startOfMonth())->sum('total')],
        ]);
    }
}
