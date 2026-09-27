<?php

namespace App\Livewire\Sales;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Category;
use App\Models\Contact;
use App\Models\FinancialAccount;
use App\Models\PosOrder;
use App\Models\Product;
use App\Services\CashService;
use App\Services\LoyaltyService;
use App\Services\PosService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Point de vente du showroom, conçu pour tablette : catalogue tactile,
 * panier, client & fidélité, encaissement et ticket 80 mm.
 */
#[Title('Point de vente')]
class Pos extends Component
{
    use InteractsWithUi;

    public string $search = '';

    public ?int $categoryId = null;

    public array $cart = [];

    public ?int $customerId = null;

    public string $customerSearch = '';

    public string $method = 'cash';

    public string $tendered = '';

    public int $redeemPoints = 0;

    public bool $showPayment = false;

    public ?int $lastOrderId = null;

    // Session POS
    public ?int $accountId = null;

    public string $openingAmount = '0';

    public function mount(): void
    {
        $this->accountId = FinancialAccount::where('company_id', company_id())->where('type', 'cash')->where('name', 'like', '%showroom%')->value('id')
            ?? FinancialAccount::where('company_id', company_id())->where('type', 'cash')->value('id');
    }

    private function session()
    {
        return app(CashService::class)->current(register: 'pos');
    }

    public function openSession(CashService $cash): void
    {
        $this->authorizeModule('pos', 'manage');
        $account = FinancialAccount::where('company_id', company_id())->findOrFail($this->accountId);
        $this->attempt(fn () => $cash->open($account, (float) $this->openingAmount, 'pos'), 'Caisse du point de vente ouverte.');
    }

    public function closeSession(CashService $cash): void
    {
        $this->authorizeModule('pos', 'manage');
        $session = $this->session();
        $this->attempt(fn () => $cash->close($session, $cash->expected($session)), 'Session clôturée : '.money($cash->expected($session)).' en caisse.');
    }

    // ── Panier ──────────────────────────────────────────────────────────────
    public function add(int $productId): void
    {
        $product = Product::where('company_id', company_id())->findOrFail($productId);
        $key = (string) $product->id;
        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        } else {
            $this->cart[$key] = ['product_id' => $product->id, 'name' => $product->name, 'unit_price' => (float) $product->sale_price, 'quantity' => 1, 'discount_rate' => 0, 'tax_rate' => (float) $product->tax_rate, 'stock' => (float) $product->stock_quantity, 'track' => $product->track_stock];
        }
    }

    public function increment(string $key, int $delta): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }
        $this->cart[$key]['quantity'] = max(0, $this->cart[$key]['quantity'] + $delta);
        if ($this->cart[$key]['quantity'] === 0) {
            unset($this->cart[$key]);
        }
    }

    public function clear(): void
    {
        $this->reset(['cart', 'customerId', 'customerSearch', 'redeemPoints', 'tendered']);
    }

    public function selectCustomer(?int $id): void
    {
        $this->customerId = $id;
        $this->customerSearch = '';
        $this->redeemPoints = 0;
    }

    private function totals(): array
    {
        $customer = $this->customerId ? Contact::find($this->customerId) : null;
        $loyalty = app(LoyaltyService::class);
        $card = $customer ? $loyalty->cardFor($customer, false) : null;
        $tier = $card ? $loyalty->tierDiscount($card) : 0;
        $subtotal = $discount = $tax = 0;
        foreach ($this->cart as $line) {
            $gross = $line['quantity'] * $line['unit_price'];
            $rate = max((float) $line['discount_rate'], $tier);
            $d = round($gross * $rate / 100, 2);
            $subtotal += $gross;
            $discount += $d;
            $tax += round(($gross - $d) * $line['tax_rate'] / 100, 2);
        }
        $pointsValue = $card && $this->redeemPoints >= $card->program->min_redeem_points ? round(min($this->redeemPoints, $card->points) * (float) $card->program->point_value, 2) : 0;

        return ['subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'points' => $pointsValue, 'total' => round($subtotal - $discount + $tax - $pointsValue, 2), 'card' => $card, 'tier' => $tier];
    }

    public function checkout(): void
    {
        $this->showPayment = true;
        $this->tendered = number_format($this->totals()['total'], 2, '.', '');
    }

    public function pay(PosService $pos): void
    {
        $this->authorizeModule('pos', 'manage');
        $session = $this->session();
        $customer = $this->customerId ? Contact::where('company_id', company_id())->find($this->customerId) : null;
        $order = $this->attempt(fn () => $pos->checkout($session, array_values($this->cart), $customer, $this->method, (float) $this->tendered, $this->redeemPoints));
        if ($order) {
            $this->lastOrderId = $order->id;
            $this->showPayment = false;
            $this->toast("Vente {$order->number} encaissée".((float) $order->change_due ? ' — rendu '.money($order->change_due) : '').'.');
            $this->clear();
        }
    }

    public function render()
    {
        $cid = company_id();
        $session = $this->session();

        return view('livewire.sales.pos', [
            'session' => $session,
            'products' => Product::where('company_id', $cid)->where('is_active', true)->where('pos_visible', true)
                ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")->orWhere('barcode', $this->search)))
                ->orderBy('name')->limit(60)->get(),
            'categories' => Category::where('company_id', $cid)->whereHas('products', fn ($q) => $q->where('pos_visible', true))->get(),
            'customers' => strlen($this->customerSearch) >= 2 ? Contact::where('company_id', $cid)->where('type', 'client')->where(fn ($q) => $q->where('company_name', 'like', "%{$this->customerSearch}%")->orWhere('last_name', 'like', "%{$this->customerSearch}%")->orWhere('first_name', 'like', "%{$this->customerSearch}%")->orWhere('phone', 'like', "%{$this->customerSearch}%"))->limit(6)->get() : collect(),
            'customer' => $this->customerId ? Contact::find($this->customerId) : null,
            'totals' => $this->totals(),
            'cashAccounts' => FinancialAccount::where('company_id', $cid)->where('type', 'cash')->get(),
            'sales' => $session ? PosOrder::where('cash_session_id', $session->id)->latest('id')->limit(5)->get() : collect(),
            'sessionTotal' => $session ? (float) PosOrder::where('cash_session_id', $session->id)->sum('total') : 0,
        ]);
    }
}
