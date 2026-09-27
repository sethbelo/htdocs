<?php

namespace App\Livewire\Orders;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Exports\OrdersExport;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Commandes')]
class Index extends Component
{
    use ScopesToSite, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'priorite')]
    public string $priority = '';

    public ?int $salesRepId = null;

    #[Url(as: 'du')]
    public ?string $from = null;

    #[Url(as: 'au')]
    public ?string $to = null;

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'priority', 'salesRepId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function query(): Builder
    {
        return Order::query()
            ->visibleTo(auth()->user())
            ->forSite($this->siteId)
            ->with('client', 'salesRep', 'site')
            ->search($this->search)
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->priority, fn (Builder $q) => $q->where('priority', $this->priority))
            ->when($this->salesRepId, fn (Builder $q) => $q->where('sales_rep_id', $this->salesRepId))
            ->when($this->from, fn (Builder $q) => $q->whereDate('ordered_at', '>=', $this->from))
            ->when($this->to, fn (Builder $q) => $q->whereDate('ordered_at', '<=', $this->to));
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('orders.export');

        return Excel::download(new OrdersExport($this->query()), 'commandes-'.now()->format('Ymd-His').'.xlsx');
    }

    public function render()
    {
        return view('livewire.orders.index', [
            'orders' => $this->query()->latest('ordered_at')->paginate(20),
            'statuses' => OrderStatus::options(),
            'priorities' => OrderPriority::options(),
            'salesReps' => User::permission('orders.create')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
