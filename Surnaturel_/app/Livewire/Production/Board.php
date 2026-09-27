<?php

namespace App\Livewire\Production;

use App\Enums\MachineStatus;
use App\Enums\PrintJobStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Machine;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Production\PrintJobService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Salle d’impression')]
class Board extends Component
{
    use InteractsWithToasts;

    public ?int $jobId = null;

    public ?int $machineId = null;

    public string $produced = '';

    public string $waste = '0';

    public string $observations = '';

    /** @var array{product_id: string, warehouse_id: string, quantity: string, waste: string} */
    public array $consumption = ['product_id' => '', 'warehouse_id' => '', 'quantity' => '', 'waste' => '0'];

    /**
     * @return array<string, Collection<int, PrintJob>>
     */
    #[Computed]
    public function columns(): array
    {
        $jobs = PrintJob::query()->with('order.client', 'order.designJob.brief', 'orderItem', 'machine', 'operator', 'proof.designJob.designer')
            ->whereHas('order', fn (Builder $q) => $q->visibleTo(auth()->user()))
            ->where(fn (Builder $q) => $q->where('status', '!=', PrintJobStatus::Ready)->orWhere('finished_at', '>=', today()))
            ->get()
            ->sortBy(fn (PrintJob $job) => [['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3][$job->order->priority->value], $job->order->due_at?->timestamp ?? PHP_INT_MAX]);

        return collect(PrintJobStatus::cases())
            ->mapWithKeys(fn (PrintJobStatus $status): array => [$status->value => $jobs->where('status', $status)->values()])
            ->all();
    }

    #[Computed]
    public function job(): ?PrintJob
    {
        return $this->jobId ? PrintJob::query()->with('order.client', 'order.items', 'order.designJob.brief.validator', 'orderItem', 'machine', 'operator', 'proof.version', 'consumptions.product')->find($this->jobId) : null;
    }

    /**
     * @return Collection<int, Machine>
     */
    #[Computed]
    public function machines(): Collection
    {
        return Machine::query()->visibleTo(auth()->user())->orderBy('code')->get();
    }

    public function open(int $id): void
    {
        $this->jobId = $id;
        $job = $this->job;
        $this->machineId = $job?->machine_id;
        $this->produced = (string) ($job?->produced_quantity ?: $job?->planned_quantity);
        $this->waste = (string) ($job?->waste_quantity ?? 0);
        $this->observations = (string) $job?->observations;
        $this->consumption['warehouse_id'] = (string) Warehouse::query()->where('site_id', $job?->order->site_id)->value('id');
        $this->modal('job')->show();
    }

    public function start(PrintJobService $service): void
    {
        $this->authorize('production.update');
        $this->validate(['machineId' => ['required', 'exists:machines,id']], [], ['machineId' => 'machine']);

        $this->attempt(fn () => $service->start($this->job, auth()->user(), Machine::query()->findOrFail($this->machineId)), 'Travail démarré.');
        $this->refreshBoard();
    }

    public function advance(PrintJobService $service): void
    {
        $this->authorize('production.update');
        $this->attempt(fn () => $service->advance($this->job, auth()->user()), 'Étape suivante.');
        $this->refreshBoard();
    }

    public function saveOutput(PrintJobService $service): void
    {
        $this->authorize('production.update');
        $this->validate(['produced' => ['required', 'integer', 'min:0'], 'waste' => ['required', 'integer', 'min:0'], 'observations' => ['nullable', 'string', 'max:2000']]);

        $this->attempt(fn () => $service->recordOutput($this->job, (int) $this->produced, (int) $this->waste, $this->observations ?: null), 'Production enregistrée.');
        $this->refreshBoard();
    }

    public function consume(PrintJobService $service): void
    {
        $this->authorize('production.update');
        $data = $this->validate([
            'consumption.product_id' => ['required', 'exists:products,id'],
            'consumption.warehouse_id' => ['required', 'exists:warehouses,id'],
            'consumption.quantity' => ['required', 'numeric', 'gt:0'],
            'consumption.waste' => ['nullable', 'numeric', 'min:0'],
        ], [], ['consumption.product_id' => 'matière', 'consumption.quantity' => 'quantité'])['consumption'];

        $this->attempt(fn () => $service->consume($this->job, Product::query()->findOrFail($data['product_id']), Warehouse::query()->findOrFail($data['warehouse_id']), (float) $data['quantity'], (float) ($data['waste'] ?? 0), auth()->user()), 'Consommation déstockée.');
        $this->consumption = ['product_id' => '', 'warehouse_id' => $data['warehouse_id'], 'quantity' => '', 'waste' => '0'];
        $this->refreshBoard();
    }

    public function quality(bool $passed): void
    {
        $this->authorize('production.update');
        $this->attempt(fn () => app(PrintJobService::class)->qualityCheck($this->job, auth()->user(), $passed, $this->observations ?: null), $passed ? 'Contrôle qualité validé : prêt à livrer.' : 'Travail renvoyé « À refaire ».');
        $this->refreshBoard();
    }

    private function refreshBoard(): void
    {
        unset($this->columns, $this->job);
    }

    public function render()
    {
        return view('livewire.production.board', [
            'materials' => Product::query()->whereIn('type', ['paper', 'ink', 'consumable', 'raw_material'])->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->visibleTo(auth()->user())->orderBy('name')->get(['id', 'name']),
            'operationalStatus' => MachineStatus::Operational,
        ]);
    }
}
