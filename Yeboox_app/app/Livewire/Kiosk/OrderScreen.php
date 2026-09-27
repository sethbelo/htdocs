<?php

namespace App\Livewire\Kiosk;

use App\Models\Category;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.kiosk')]
class OrderScreen extends Component
{
    // ── State ───────────────────────────────────────────
    public string $step = 'categories'; // categories, products, cart, payment, confirmation

    public ?string $selectedCategoryId = null;

    public array $cart = [];

    public string $orderType = 'dine_in'; // dine_in, takeaway

    public string $customerName = '';

    public ?string $paymentMethodId = null;

    // ── Auto-reset timer ────────────────────────────────
    public int $inactivityTimeout = 60; // seconds

    public int $lastActivity;

    // ── Computed ─────────────────────────────────────────
    public function mount()
    {
        $this->lastActivity = time();
    }

    public function getEstablishmentId(): string
    {
        return config('yeboox.kiosk.establishment_id')
            ?: Establishment::query()->value('id')
            ?: '';
    }

    // ── Categories ──────────────────────────────────────
    public function getCategories()
    {
        return Category::where('establishment_id', $this->getEstablishmentId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function selectCategory(string $categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->step = 'products';
        $this->touch();
    }

    // ── Products ────────────────────────────────────────
    public function getProducts()
    {
        return Product::where('establishment_id', $this->getEstablishmentId())
            ->where('category_id', $this->selectedCategoryId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->orderBy('name')
            ->get();
    }

    // ── Cart ────────────────────────────────────────────
    public function addToCart(string $productId)
    {
        $product = Product::query()
            ->where('establishment_id', $this->getEstablishmentId())
            ->active()
            ->available()
            ->find($productId);

        if (! $product) {
            return;
        }

        $key = array_search($productId, array_column($this->cart, 'product_id'));

        if ($key !== false) {
            $this->cart[$key]['quantity']++;
            $this->cart[$key]['total'] = $this->cart[$key]['quantity'] * $this->cart[$key]['price'];
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->effective_price,
                'quantity' => 1,
                'total' => $product->effective_price,
                'image' => $product->image_url,
            ];
        }

        $this->touch();
    }

    public function removeFromCart(int $index)
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);
        }
        $this->touch();
    }

    public function incrementItem(int $index)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity']++;
            $this->cart[$index]['total'] = $this->cart[$index]['quantity'] * $this->cart[$index]['price'];
        }
        $this->touch();
    }

    public function decrementItem(int $index)
    {
        if (isset($this->cart[$index])) {
            if ($this->cart[$index]['quantity'] <= 1) {
                $this->removeFromCart($index);
            } else {
                $this->cart[$index]['quantity']--;
                $this->cart[$index]['total'] = $this->cart[$index]['quantity'] * $this->cart[$index]['price'];
            }
        }
        $this->touch();
    }

    public function getCartTotal(): float
    {
        return array_sum(array_column($this->cart, 'total'));
    }

    public function getCartCount(): int
    {
        return array_sum(array_column($this->cart, 'quantity'));
    }

    // ── Navigation ──────────────────────────────────────
    public function goToCart()
    {
        if (count($this->cart) > 0) {
            $this->step = 'cart';
        }
        $this->touch();
    }

    public function goToPayment()
    {
        $this->step = 'payment';
        $this->touch();
    }

    public function goBack()
    {
        $this->step = match ($this->step) {
            'products' => 'categories',
            'cart' => 'products',
            'payment' => 'cart',
            default => 'categories',
        };
        $this->touch();
    }

    // ── Order Submission ────────────────────────────────
    public function submitOrder()
    {
        if (empty($this->cart)) {
            return;
        }

        try {
            $establishmentId = $this->getEstablishmentId();
            $operator = User::query()->forEstablishment($establishmentId)->active()->first();

            if (! $operator) {
                session()->flash('error', 'Le kiosque doit être associé à un utilisateur actif.');

                return;
            }

            $products = Product::query()
                ->where('establishment_id', $establishmentId)
                ->active()
                ->available()
                ->whereIn('id', collect($this->cart)->pluck('product_id'))
                ->get()
                ->keyBy('id');

            $items = collect($this->cart)->map(function (array $item) use ($products) {
                $product = $products->get($item['product_id']);

                abort_unless($product, 422, 'Un produit du panier n’est plus disponible.');

                $quantity = max(1, min(100, (int) $item['quantity']));

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $product->effective_price,
                    'total' => $product->effective_price * $quantity,
                ];
            });

            $paymentMethod = $this->paymentMethodId
                ? PaymentMethod::query()
                    ->where('establishment_id', $establishmentId)
                    ->active()
                    ->find($this->paymentMethodId)
                : null;

            DB::transaction(function () use ($establishmentId, $items, $operator, $paymentMethod) {
                $total = $items->sum('total');
                $date = now()->format('ymd');
                $sequence = Order::whereDate('created_at', today())->count() + 1;

                $order = Order::create([
                    'establishment_id' => $establishmentId,
                    'user_id' => $operator->id,
                    'order_number' => "KSK-{$date}-".str_pad($sequence, 4, '0', STR_PAD_LEFT),
                    'type' => in_array($this->orderType, ['dine_in', 'takeaway'], true) ? $this->orderType : 'dine_in',
                    'status' => 'confirmed',
                    'subtotal' => $total,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => $total,
                    'notes' => $this->customerName ? "Kiosque — Client: {$this->customerName}" : 'Commande Kiosque',
                    'is_paid' => (bool) $paymentMethod,
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'name' => $item['product']->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total'],
                    ]);
                }

                if ($paymentMethod) {
                    Payment::create([
                        'establishment_id' => $establishmentId,
                        'order_id' => $order->id,
                        'payment_method_id' => $paymentMethod->id,
                        'amount' => $total,
                        'status' => 'completed',
                        'processed_by' => $operator->id,
                    ]);
                }
            }, 3);

            $this->step = 'confirmation';
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'La commande n’a pas pu être enregistrée. Veuillez réessayer.');
        }
    }

    // ── Reset (auto-reset after inactivity) ─────────────
    public function resetKiosk()
    {
        $this->cart = [];
        $this->step = 'categories';
        $this->selectedCategoryId = null;
        $this->customerName = '';
        $this->paymentMethodId = null;
        $this->orderType = 'dine_in';
        $this->touch();
    }

    public function touch()
    {
        $this->lastActivity = time();
    }

    // ── Render ──────────────────────────────────────────
    public function render()
    {
        $categories = $this->getCategories();
        $products = $this->selectedCategoryId ? $this->getProducts() : collect();
        $paymentMethods = PaymentMethod::where('establishment_id', $this->getEstablishmentId())
            ->where('is_active', true)
            ->get();

        return view('livewire.kiosk.order-screen', [
            'categories' => $categories,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'cartTotal' => $this->getCartTotal(),
            'cartCount' => $this->getCartCount(),
        ]);
    }
}
