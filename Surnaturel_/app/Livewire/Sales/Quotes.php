<?php

namespace App\Livewire\Sales;

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\Sales\OrderService;
use App\Services\Sales\QuoteService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Devis & pro forma')]
class Quotes extends Component
{
    use InteractsWithToasts, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'statut')]
    public string $status = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var list<array<string, string>> */
    public array $items = [];

    public function create(): void
    {
        $this->authorize('quotes.create');
        $this->form = ['type' => 'quote', 'client_id' => '', 'site_id' => auth()->user()->default_site_id, 'valid_until' => now()->addDays(30)->toDateString(), 'notes' => ''];
        $this->items = [];
        $this->addItem();
        $this->modal('quote')->show();
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => '1', 'unit_price' => '0', 'discount' => '0', 'tax_rate' => (string) Setting::get('company.default_tax_rate', 0), 'format' => '', 'paper' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(QuoteService $quotes): void
    {
        $this->authorize('quotes.create');

        $data = $this->validate([
            'form.type' => ['required', Rule::enum(QuoteType::class)],
            'form.client_id' => ['required', 'exists:clients,id'],
            'form.site_id' => ['required', Rule::in(auth()->user()->accessibleSiteIds())],
            'form.valid_until' => ['nullable', 'date'],
            'form.notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'between:0,100'],
            'items.*.format' => ['nullable', 'string', 'max:30'],
            'items.*.paper' => ['nullable', 'string', 'max:255'],
        ], [], ['form.client_id' => 'client']);

        $items = array_map(fn (array $item): array => array_map(fn ($v) => $v === '' ? null : $v, $item), $data['items']);
        $quote = $quotes->create($data['form'], $items, auth()->user());

        $this->modal('quote')->close();
        $this->toast("{$quote->type->label()} {$quote->number} créé.");
    }

    public function setStatus(int $id, string $status): void
    {
        $this->authorize('quotes.update');
        Quote::query()->visibleTo(auth()->user())->findOrFail($id)->update(['status' => QuoteStatus::from($status)]);
    }

    public function convert(int $id, OrderService $orders): void
    {
        $this->authorize('orders.create');
        $quote = Quote::query()->visibleTo(auth()->user())->with('items')->findOrFail($id);

        if ($quote->order_id) {
            $this->toast('Ce devis est déjà converti.', 'warning');

            return;
        }

        $order = $this->attempt(fn () => $orders->createFromQuote($quote, auth()->user(), ['deposit_rate' => Setting::get('orders.default_deposit_rate', 100)]));

        if ($order) {
            session()->flash('print_ticket', true);
            $this->redirectRoute('orders.show', $order, navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.sales.quotes', [
            'quotes' => Quote::query()->visibleTo(auth()->user())->with('client', 'salesRep', 'order')
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', "%{$this->search}%")->orWhereHas('client', fn ($c) => $c->search($this->search))))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest()->paginate(20),
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name', 'company_name']),
            'statuses' => QuoteStatus::options(),
        ]);
    }
}
