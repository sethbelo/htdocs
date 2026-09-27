<?php

namespace App\Livewire\Orders;

use App\Enums\ClientType;
use App\Enums\OrderPriority;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Client;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Site;
use App\Services\Sales\OrderService;
use App\Services\Support\NumberGenerator;
use App\Services\Support\TotalsCalculator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nouvelle commande')]
class Create extends Component
{
    use InteractsWithToasts;

    public ?int $client_id = null;

    public string $clientSearch = '';

    public ?int $site_id = null;

    public ?string $due_at = null;

    public string $priority = 'normal';

    public bool $requires_design = true;

    public string $deposit_rate = '';

    public string $global_discount = '0';

    public string $technical_notes = '';

    /** @var list<array<string, mixed>> */
    public array $items = [];

    /** @var array<string, string> */
    public array $newClient = ['name' => '', 'company_name' => '', 'phone' => '', 'type' => 'individual'];

    public function mount(): void
    {
        $this->site_id = auth()->user()->default_site_id ?? auth()->user()->accessibleSiteIds()[0] ?? null;
        $this->deposit_rate = (string) Setting::get('orders.default_deposit_rate', 100);
        $this->due_at = now()->addDays(2)->setTime(17, 0)->format('Y-m-d\TH:i');
        $this->client_id = request()->integer('client') ?: null;
        $this->addItem();
    }

    public function addItem(?int $productId = null): void
    {
        $product = $productId ? Product::query()->find($productId) : null;

        $this->items[] = [
            'product_id' => $product?->id,
            'description' => $product?->name ?? '',
            'quantity' => '1',
            'unit_price' => (string) ($product?->sale_price ?? '0'),
            'discount' => '0',
            'tax_rate' => (string) ($product?->tax_rate ?? Setting::get('company.default_tax_rate', 0)),
            'format' => '', 'width' => '', 'height' => '', 'dimension_unit' => 'cm', 'paper' => '', 'grammage' => '',
            'colors' => 'Quadri', 'print_sides' => 'recto', 'finishing' => '', 'binding' => '', 'lamination' => false,
            'cutting' => '', 'film_coating' => '', 'pages' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems(mixed $value, string $key): void
    {
        [$index, $field] = explode('.', $key, 2);

        if ($field === 'product_id' && $value && ($product = Product::query()->find($value))) {
            $this->items[$index]['description'] = $product->name;
            $this->items[$index]['unit_price'] = (string) $product->sale_price;
            $this->items[$index]['tax_rate'] = (string) $product->tax_rate;
        }
    }

    public function selectClient(int $id): void
    {
        $this->client_id = $id;
        $this->clientSearch = '';
    }

    public function createClient(NumberGenerator $numbers): void
    {
        $this->authorize('clients.create');

        $data = $this->validate([
            'newClient.name' => ['required', 'string', 'max:255'],
            'newClient.company_name' => ['nullable', 'string', 'max:255'],
            'newClient.phone' => ['required', 'string', 'max:30'],
            'newClient.type' => ['required', Rule::enum(ClientType::class)],
        ], [], ['newClient.name' => 'nom', 'newClient.phone' => 'téléphone'])['newClient'];

        $client = Client::query()->create($data + [
            'number' => $numbers->next(NumberGenerator::Client),
            'segment' => $data['type'] === 'individual' ? 'b2c' : 'b2b',
            'whatsapp' => $data['phone'],
            'site_id' => $this->site_id,
            'sales_rep_id' => auth()->id(),
        ]);

        $this->client_id = $client->id;
        $this->reset('newClient');
        $this->modal('new-client')->close();
        $this->toast("Client {$client->number} créé.");
    }

    #[Computed]
    public function client(): ?Client
    {
        return $this->client_id ? Client::query()->find($this->client_id) : null;
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clientResults(): Collection
    {
        return strlen($this->clientSearch) < 2 ? collect() : Client::query()->search($this->clientSearch)->where('is_active', true)->limit(8)->get();
    }

    /**
     * @return array{subtotal: float, discount_total: float, tax_total: float, total: float, deposit: float}
     */
    #[Computed]
    public function totals(): array
    {
        $lines = collect($this->items)->map(fn (array $item): array => [
            'quantity' => (float) $item['quantity'], 'unit_price' => (float) $item['unit_price'],
            'discount' => (float) $item['discount'], 'tax_rate' => (float) $item['tax_rate'],
        ])->all();

        $totals = app(TotalsCalculator::class)->document($lines, (float) $this->global_discount);

        return $totals + ['deposit' => round($totals['total'] * min(100, max(0, (float) $this->deposit_rate)) / 100, 2)];
    }

    public function save(bool $asDraft = false): void
    {
        $this->authorize('orders.create');

        $validated = $this->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'site_id' => ['required', Rule::in(auth()->user()->accessibleSiteIds())],
            'due_at' => ['nullable', 'date', 'after:now'],
            'priority' => ['required', Rule::enum(OrderPriority::class)],
            'requires_design' => ['boolean'],
            'deposit_rate' => ['required', 'numeric', 'between:0,100'],
            'global_discount' => ['nullable', 'numeric', 'min:0'],
            'technical_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'between:0,100'],
            'items.*.format' => ['nullable', 'string', 'max:30'],
            'items.*.width' => ['nullable', 'numeric', 'min:0'],
            'items.*.height' => ['nullable', 'numeric', 'min:0'],
            'items.*.dimension_unit' => ['nullable', 'in:mm,cm,m'],
            'items.*.paper' => ['nullable', 'string', 'max:255'],
            'items.*.grammage' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'items.*.colors' => ['nullable', 'string', 'max:30'],
            'items.*.print_sides' => ['nullable', 'in:recto,recto_verso'],
            'items.*.finishing' => ['nullable', 'string', 'max:255'],
            'items.*.binding' => ['nullable', 'string', 'max:255'],
            'items.*.lamination' => ['boolean'],
            'items.*.cutting' => ['nullable', 'string', 'max:255'],
            'items.*.film_coating' => ['nullable', 'string', 'max:255'],
            'items.*.pages' => ['nullable', 'integer', 'min:1'],
        ], [], ['client_id' => 'client', 'items.*.description' => 'désignation', 'items.*.quantity' => 'quantité', 'items.*.unit_price' => 'prix unitaire']);

        if ((float) $this->global_discount > 0 && ! auth()->user()->can('orders.approve') && ! auth()->user()->can('pos.discount')) {
            $this->addError('global_discount', 'Remise globale non autorisée.');

            return;
        }

        $items = array_map(fn (array $item): array => array_map(fn ($v) => $v === '' ? null : $v, $item), $validated['items']);

        $order = app(OrderService::class)->create($validated, $items, auth()->user(), $asDraft);

        session()->flash('print_ticket', ! $asDraft);
        $this->redirectRoute('orders.show', $order, navigate: true);
    }

    public function render()
    {
        return view('livewire.orders.create', [
            'sites' => Site::query()->whereIn('id', auth()->user()->accessibleSiteIds())->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->whereIn('type', ['service', 'finished_good'])->orderBy('name')->get(['id', 'name', 'sale_price']),
            'priorities' => OrderPriority::options(),
            'clientTypes' => ClientType::options(),
        ]);
    }
}
