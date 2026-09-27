<?php

namespace App\Livewire\Pos;

use App\Enums\PaymentMethod;
use App\Enums\StockTransferStatus;
use App\Livewire\Cash\Concerns\ManagesCashSession;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Client;
use App\Models\LoyaltyAccount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\Cash\PaymentData;
use App\Services\Inventory\StockTransferService;
use App\Services\Pos\CartLine;
use App\Services\Pos\PosCheckoutService;
use App\Services\Printing\EscPosPrinter;
use App\Services\Support\TotalsCalculator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.fullscreen')]
#[Title('Point de vente')]
class Terminal extends Component
{
    use InteractsWithToasts, ManagesCashSession;

    public string $search = '';

    public ?int $categoryId = null;

    public bool $favoritesOnly = false;

    /** @var array<int, array{product_id: int, name: string, price: float, quantity: float, discount: float}> */
    public array $cart = [];

    public ?int $clientId = null;

    public string $clientSearch = '';

    public string $promotionCode = '';

    public int $redeemPoints = 0;

    /** @var list<array{method: string, amount: string, reference: string}> */
    public array $payments = [];

    public ?int $lastSaleId = null;

    public string $stockSearch = '';

    public ?int $restockFromId = null;

    /** @var list<array{product_id: string, quantity: string}> */
    public array $restockLines = [];

    protected function afterSessionOpened(): void
    {
        $this->modal('pos-startup')->close();
    }

    protected function afterSessionClosed(): void
    {
        $this->clearCart();
        $this->modal('pos-startup')->show();
    }

    /**
     * Alertes de stock faible par caisse (entrepôt lié), affichées dans le modal de démarrage.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function registerStockAlerts(): array
    {
        return $this->registers->mapWithKeys(fn ($register): array => [$register->id => $register->warehouse_id
            ? StockLevel::query()->where('warehouse_id', $register->warehouse_id)
                ->join('products', 'products.id', '=', 'stock_levels.product_id')
                ->where('products.min_stock', '>', 0)
                ->whereColumn('stock_levels.quantity', '<=', 'products.min_stock')->count()
            : 0])->all();
    }

    public function openStock(): void
    {
        $this->authorize('inventory.view');
        $this->restockFromId = Warehouse::query()->where('id', '!=', $this->session?->register->warehouse_id)->orderBy('id')->value('id');
        $this->restockLines = [['product_id' => '', 'quantity' => '']];
        $this->modal('pos-stock')->show();
    }

    /**
     * @return Collection<int, StockLevel>
     */
    #[Computed]
    public function warehouseStock(): Collection
    {
        $warehouseId = $this->session?->register->warehouse_id;

        return $warehouseId ? StockLevel::query()->with('product.unit')->where('warehouse_id', $warehouseId)
            ->whereHas('product', fn ($q) => $q->search($this->stockSearch ?: null))
            ->orderBy('quantity')->limit(60)->get() : collect();
    }

    /**
     * @return Collection<int, StockTransfer>
     */
    #[Computed]
    public function incomingTransfers(): Collection
    {
        $warehouseId = $this->session?->register->warehouse_id;

        return $warehouseId ? StockTransfer::query()->with('from', 'items.product')
            ->where('to_warehouse_id', $warehouseId)
            ->whereIn('status', [StockTransferStatus::Requested, StockTransferStatus::InTransit])
            ->latest('requested_at')->get() : collect();
    }

    public function addRestockLine(): void
    {
        $this->restockLines[] = ['product_id' => '', 'quantity' => ''];
    }

    public function requestRestock(StockTransferService $transfers): void
    {
        $this->authorize('inventory.create');
        $this->validate([
            'restockFromId' => ['required', 'exists:warehouses,id'],
            'restockLines.*.product_id' => ['required', 'exists:products,id'],
            'restockLines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], [], ['restockFromId' => 'entrepôt source', 'restockLines.*.product_id' => 'article', 'restockLines.*.quantity' => 'quantité']);

        $transfer = $this->attempt(fn () => $transfers->request(
            Warehouse::query()->findOrFail($this->restockFromId),
            $this->session->register->warehouse,
            $this->restockLines,
            auth()->user(),
            "Réassort demandé depuis la caisse {$this->session->register->code}",
        ));

        if ($transfer) {
            $this->restockLines = [['product_id' => '', 'quantity' => '']];
            $this->toast("Réassort {$transfer->number} demandé à {$transfer->from->name}.");
            unset($this->incomingTransfers);
        }
    }

    public function receiveTransfer(int $transferId, StockTransferService $transfers): void
    {
        $this->authorize('inventory.update');
        $transfer = StockTransfer::query()->where('to_warehouse_id', $this->session?->register->warehouse_id)->findOrFail($transferId);

        if ($this->attempt(fn () => $transfers->receive($transfer, auth()->user(), [], 'Réceptionné en caisse'), "Transfert {$transfer->number} réceptionné.")) {
            unset($this->incomingTransfers, $this->warehouseStock, $this->products);
        }
    }

    public function showAll(): void
    {
        $this->categoryId = null;
        $this->favoritesOnly = false;
    }

    public function toggleFavorites(): void
    {
        $this->favoritesOnly = ! $this->favoritesOnly;
        $this->categoryId = null;
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()->where('is_active', true)->where('is_sellable_pos', true)->find($productId);

        if (! $product) {
            $this->toast('Article introuvable ou non vendable en boutique.', 'warning');

            return;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = ['product_id' => $product->id, 'name' => $product->name, 'price' => (float) $product->sale_price, 'quantity' => 1, 'discount' => 0];
        }

        $this->lastSaleId = null;
    }

    /**
     * Scan code-barres / QR / RFID : article, ou carte fidélité client.
     */
    public function scan(string $code): void
    {
        $code = trim($code);

        if ($product = Product::query()->where('is_active', true)->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code)->orWhere('rfid_tag', $code))->first()) {
            $this->addProduct($product->id);

            return;
        }

        if ($account = LoyaltyAccount::query()->where('card_number', $code)->first()) {
            $this->clientId = $account->client_id;
            $this->toast("Client {$account->client->displayName()} identifié.");

            return;
        }

        $this->toast("Code « {$code} » inconnu.", 'warning');
    }

    public function submitSearch(): void
    {
        $results = $this->products;

        if ($results->count() === 1) {
            $this->addProduct($results->first()->id);
            $this->search = '';
        } elseif ($this->search !== '') {
            $this->scan($this->search);
            $this->search = '';
        }
    }

    public function increment(int $productId): void
    {
        $this->cart[$productId]['quantity']++;
    }

    public function decrement(int $productId): void
    {
        if ($this->cart[$productId]['quantity'] <= 1) {
            unset($this->cart[$productId]);

            return;
        }

        $this->cart[$productId]['quantity']--;
    }

    public function remove(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function clearCart(): void
    {
        $this->reset('cart', 'clientId', 'promotionCode', 'redeemPoints', 'payments');
    }

    public function selectClient(int $id): void
    {
        $this->clientId = $id;
        $this->clientSearch = '';
    }

    public function openPayment(): void
    {
        if ($this->cart === []) {
            return;
        }

        $this->payments = [['method' => PaymentMethod::Cash->value, 'amount' => (string) $this->totals['total'], 'reference' => '']];
        $this->modal('payment')->show();
    }

    public function quickCash(float $amount): void
    {
        $this->payments = [['method' => PaymentMethod::Cash->value, 'amount' => (string) $amount, 'reference' => '']];
    }

    public function addPayment(): void
    {
        $remaining = max(0, $this->totals['total'] - collect($this->payments)->sum(fn ($p) => (float) $p['amount']));
        $this->payments[] = ['method' => PaymentMethod::MobileMoney->value, 'amount' => (string) round($remaining, 2), 'reference' => ''];
    }

    public function removePayment(int $index): void
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    }

    public function checkout(PosCheckoutService $pos): void
    {
        $this->validate([
            'payments.*.method' => ['required', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'cart.*.discount' => ['numeric', 'min:0'],
        ]);

        $sale = $this->attempt(fn () => $pos->checkout(
            CartLine::collection(array_values(array_map(fn (array $line): array => ['product_id' => $line['product_id'], 'quantity' => $line['quantity'], 'discount' => $line['discount']], $this->cart))),
            PaymentData::collection($this->payments),
            $this->session,
            auth()->user(),
            $this->clientId ? Client::query()->find($this->clientId) : null,
            $this->promotionCode ?: null,
            $this->redeemPoints,
        ));

        if ($sale) {
            $this->modal('payment')->close();
            $this->lastSaleId = $sale->id;
            $this->toast("Vente {$sale->number} validée".((float) $sale->change_due > 0 ? ' — monnaie : '.number_format((float) $sale->change_due, 2, ',', ' ') : '').'.');
            $this->printReceipt($sale);
            $this->clearCart();
            unset($this->session);
        }
    }

    /**
     * Ticket : imprimante thermique ESC/POS si configurée pour la caisse, sinon PDF 80 mm.
     */
    private function printReceipt(Sale $sale): void
    {
        $registerCode = $sale->register->code;

        if (Setting::get("pos.printer.{$registerCode}")) {
            $printed = $this->attempt(fn () => app(EscPosPrinter::class)->printSaleReceipt($sale, $registerCode) ?? true);

            if ($printed) {
                return;
            }
        }

        $this->js('window.open('.json_encode(route('print.sale-receipt', $sale)).', "_blank")');
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        $warehouseId = $this->session?->register->warehouse_id;

        return Product::query()
            ->where('is_active', true)->where('is_sellable_pos', true)
            ->search($this->search ?: null)
            ->when($this->categoryId, fn ($q) => $q->where('product_category_id', $this->categoryId))
            ->when($this->favoritesOnly, fn ($q) => $q->where('is_favorite', true))
            ->addSelect(['stock' => StockLevel::query()->select('quantity')->whereColumn('product_id', 'products.id')->where('warehouse_id', $warehouseId ?? 0)->limit(1)])
            ->orderByDesc('is_favorite')->orderBy('name')
            ->limit(60)->get();
    }

    /**
     * @return Collection<int, ProductCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return ProductCategory::query()->whereHas('products', fn ($q) => $q->where('is_sellable_pos', true))->orderBy('position')->get();
    }

    #[Computed]
    public function client(): ?Client
    {
        return $this->clientId ? Client::query()->with('loyaltyAccount.tier')->find($this->clientId) : null;
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clientResults(): Collection
    {
        if (strlen($this->clientSearch) < 2) {
            return collect();
        }

        return Client::query()->search($this->clientSearch)
            ->orWhereHas('loyaltyAccount', fn ($q) => $q->where('card_number', $this->clientSearch))
            ->limit(6)->get();
    }

    /**
     * Aperçu des totaux (le calcul définitif, promotions incluses, est fait par le service).
     *
     * @return array{subtotal: float, discount_total: float, tax_total: float, total: float, items: int}
     */
    #[Computed]
    public function totals(): array
    {
        $taxRates = Product::query()->whereIn('id', array_keys($this->cart))->pluck('tax_rate', 'id');
        $lines = array_map(fn (array $line): array => [
            'quantity' => $line['quantity'], 'unit_price' => $line['price'], 'discount' => $line['discount'], 'tax_rate' => (float) ($taxRates[$line['product_id']] ?? 0),
        ], $this->cart);

        $tierDiscount = (float) ($this->client?->loyaltyAccount?->tier?->discount_rate ?? 0);
        $totals = app(TotalsCalculator::class)->document($lines);
        $totals = app(TotalsCalculator::class)->document($lines, round($totals['total'] * $tierDiscount / 100, 2) + round($this->redeemPoints * (float) Setting::get('loyalty.point_value', 0), 2));

        return $totals + ['items' => (int) array_sum(array_column($this->cart, 'quantity'))];
    }

    public function render()
    {
        return view('livewire.pos.terminal', ['methods' => PaymentMethod::options()]);
    }
}
