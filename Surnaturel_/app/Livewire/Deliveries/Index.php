<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Delivery;
use App\Services\Delivery\DeliveryService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Livraisons')]
class Index extends Component
{
    use InteractsWithToasts, WithFileUploads, WithPagination;

    #[Url(as: 'statut')]
    public string $status = 'active';

    public bool $mineOnly = false;

    public ?int $deliveryId = null;

    public $signature = null;

    public $photo = null;

    public string $observations = '';

    public string $reason = '';

    public string $postponeTo = '';

    public function mount(): void
    {
        $this->mineOnly = auth()->user()->hasRole('livreur');
    }

    public function start(int $id, DeliveryService $service): void
    {
        $this->authorize('deliveries.update');
        $this->attempt(fn () => $service->dispatch($this->find($id), auth()->user()), 'Livraison en cours.');
    }

    public function openComplete(int $id): void
    {
        $this->deliveryId = $id;
        $this->reset('signature', 'photo', 'observations', 'reason', 'postponeTo');
        $this->modal('complete')->show();
    }

    public function complete(DeliveryService $service): void
    {
        $this->authorize('deliveries.update');
        $this->validate(['signature' => ['nullable', 'image', 'max:5120'], 'photo' => ['nullable', 'image', 'max:10240'], 'observations' => ['nullable', 'string', 'max:2000']]);

        if ($this->attempt(fn () => $service->complete($this->find($this->deliveryId), auth()->user(), $this->signature, $this->photo, $this->observations ?: null), 'Livraison confirmée.')) {
            $this->modal('complete')->close();
        }
    }

    public function fail(DeliveryService $service): void
    {
        $this->authorize('deliveries.update');
        $this->validate(['reason' => ['required', 'string', 'max:255'], 'postponeTo' => ['nullable', 'date']], [], ['reason' => 'motif']);

        if ($this->attempt(fn () => $service->fail($this->find($this->deliveryId), auth()->user(), $this->reason, (bool) $this->postponeTo, $this->postponeTo ?: null), $this->postponeTo ? 'Livraison reportée.' : 'Échec enregistré.')) {
            $this->modal('complete')->close();
        }
    }

    private function find(int $id): Delivery
    {
        return Delivery::query()->whereHas('order', fn (Builder $q) => $q->visibleTo(auth()->user()))->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.deliveries.index', [
            'deliveries' => Delivery::query()->with('order.client', 'driver')
                ->whereHas('order', fn (Builder $q) => $q->visibleTo(auth()->user()))
                ->when($this->status === 'active', fn (Builder $q) => $q->whereIn('status', [DeliveryStatus::Pending, DeliveryStatus::InProgress, DeliveryStatus::Postponed]))
                ->when($this->status && $this->status !== 'active', fn (Builder $q) => $q->where('status', $this->status))
                ->when($this->mineOnly, fn (Builder $q) => $q->where('driver_id', auth()->id()))
                ->orderByRaw('scheduled_at IS NULL')->orderBy('scheduled_at')->latest()
                ->paginate(20),
            'statuses' => DeliveryStatus::options(),
        ]);
    }
}
