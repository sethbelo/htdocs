<?php

namespace App\Livewire;

use App\Models\Product;
use App\Support\DisplayCurrency;
use App\Support\SalesReportData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Sales extends Component
{
    use WithPagination;

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $search = '';

    public int $perPage = 15;

    public function mount(): void
    {
        $this->startDate = $this->startDate ?: Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->endDate = $this->endDate ?: Carbon::today()->format('Y-m-d');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['startDate', 'endDate', 'category', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate ?: now()->startOfMonth())->format('Y-m-d');
        $end = Carbon::parse($this->endDate ?: now())->format('Y-m-d');
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        $analysis = SalesReportData::build($start, $end, $this->category ?: null);
        $sales = SalesReportData::query($start, $end, $this->category ?: null)
            ->when($this->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('id', ctype_digit($search) ? (int) $search : -1)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items.product', fn (Builder $product) => $product->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.sales', [
            ...$analysis,
            'sales' => $sales,
            'startDate' => $start,
            'endDate' => $end,
            'availableCategories' => Product::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
            'currency' => DisplayCurrency::code(),
        ])->layout('layouts.app');
    }
}
