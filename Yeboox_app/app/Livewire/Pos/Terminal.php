<?php

namespace App\Livewire\Pos;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Terminal extends Component
{
    // ── State ────────────────────────────────
    public array $cart = [];

    public string $search = '';

    public string $selectedCategory = '';

    public string $orderType = 'dine_in';

    public string $selectedTableId = '';

    public string $selectedCustomerId = '';

    public string $selectedZone = '';

    public string $orderNotes = '';

    public $discountAmount = 0;

    public string $discountType = 'fixed'; // fixed, percentage

    public array $tablesCarts = []; // Storing multiple carts

    public string $currentCartKey = 'default';

    // Takeaway & Delivery Fields
    public string $customerName = '';

    public string $customerPhone = '';

    public string $deliveryAddress = '';

    public string $driverId = '';

    public string $deliveryStatus = 'pending'; // pending, delivered, cancelled

    // ── Payment Modal ────────────────────────────────
    public bool $showPayment = false;

    public array $paymentSplit = [];

    public string $paymentRef = '';

    public $cashReceived = 0;

    public string $selectedPaymentCurrency = 'CDF';

    // ── Current Session ────────────────────────────────
    public ?string $currentSessionId = null;

    public bool $showOpenSession = false;

    public float $openingAmount = 0;

    public string $openingNote = '';

    public string $selectedCashRegisterId = '';

    public bool $showCloseSession = false;

    public float $closingCdfAmount = 0;

    public float $closingUsdAmount = 0;

    public string $closingNote = '';

    public string $exitAfterClose = 'none';

    public string $selectedDisplayCurrency = 'CDF';

    // ── Table Selector ────────────────────────────────
    public bool $showTableSelector = false;

    // ── Customer Selector ────────────────────────────────
    public bool $showCustomerSelector = false;

    public string $customerSearch = '';

    // ── Success Feedback ────────────────────────────────
    public bool $showSuccess = false;

    public bool $showClearCartModal = false;

    public string $lastOrderNumber = '';

    protected $listeners = ['refreshPos' => '$refresh'];

    public function getCurrentCartKey(): string
    {
        return $this->currentCartKey ?: 'default';
    }

    public function updated($propertyName): void
    {
        $this->saveCurrentTableState();
    }

    public function startNewOrder(): void
    {
        $this->saveCurrentTableState();
        $this->selectedTableId = '';
        $this->currentCartKey = 'default';
        $this->cart = [];
        $this->selectedCustomerId = '';
        $this->discountAmount = 0.0;
        $this->orderNotes = '';
        $this->customerName = '';
        $this->customerPhone = '';
        $this->deliveryAddress = '';
        $this->driverId = '';
        $this->deliveryStatus = 'pending';
        $this->orderType = 'dine_in';

        session()->put('pos_selected_table_id', '');
        session()->put('pos_current_cart_key', 'default');

        $this->showTableSelector = true;
    }

    public function startNewTakeaway(): void
    {
        $this->saveCurrentTableState();
        $key = 'takeaway_'.uniqid();
        $this->tablesCarts[$key] = [
            'cart' => [],
            'selectedCustomerId' => '',
            'discountAmount' => 0.0,
            'discountType' => 'fixed',
            'orderNotes' => '',
            'orderType' => 'takeaway',
            'customerName' => 'Client Emporter '.(count(array_filter(array_keys($this->tablesCarts), fn ($k) => str_starts_with($k, 'takeaway_'))) + 1),
            'customerPhone' => '',
            'deliveryAddress' => '',
            'driverId' => '',
            'deliveryStatus' => 'pending',
        ];
        $this->selectCartKey($key);
    }

    public function startNewDelivery(): void
    {
        $this->saveCurrentTableState();
        $key = 'delivery_'.uniqid();
        $this->tablesCarts[$key] = [
            'cart' => [],
            'selectedCustomerId' => '',
            'discountAmount' => 0.0,
            'discountType' => 'fixed',
            'orderNotes' => '',
            'orderType' => 'delivery',
            'customerName' => 'Client Livraison '.(count(array_filter(array_keys($this->tablesCarts), fn ($k) => str_starts_with($k, 'delivery_'))) + 1),
            'customerPhone' => '',
            'deliveryAddress' => '',
            'driverId' => '',
            'deliveryStatus' => 'pending',
        ];
        $this->selectCartKey($key);
    }

    public function selectCartKey(string $key): void
    {
        $this->saveCurrentTableState();
        $this->currentCartKey = $key;

        if (str_starts_with($key, 'table_')) {
            $this->selectedTableId = str_replace('table_', '', $key);
        } else {
            $this->selectedTableId = '';
        }

        $state = $this->tablesCarts[$key] ?? [
            'cart' => [],
            'selectedCustomerId' => '',
            'discountAmount' => 0.0,
            'discountType' => 'fixed',
            'orderNotes' => '',
            'orderType' => str_starts_with($key, 'takeaway_') ? 'takeaway' : (str_starts_with($key, 'delivery_') ? 'delivery' : 'dine_in'),
            'customerName' => '',
            'customerPhone' => '',
            'deliveryAddress' => '',
            'driverId' => '',
            'deliveryStatus' => 'pending',
        ];

        $this->cart = $state['cart'] ?? [];
        $this->selectedCustomerId = $state['selectedCustomerId'] ?? '';
        $this->discountAmount = $state['discountAmount'] ?? 0.0;
        $this->discountType = $state['discountType'] ?? 'fixed';
        $this->orderNotes = $state['orderNotes'] ?? '';
        $this->orderType = $state['orderType'] ?? 'dine_in';
        $this->customerName = $state['customerName'] ?? '';
        $this->customerPhone = $state['customerPhone'] ?? '';
        $this->deliveryAddress = $state['deliveryAddress'] ?? '';
        $this->driverId = $state['driverId'] ?? '';
        $this->deliveryStatus = $state['deliveryStatus'] ?? 'pending';

        session()->put('pos_current_cart_key', $this->currentCartKey);
        session()->put('pos_selected_table_id', $this->selectedTableId);
    }

    public function saveCurrentTableState(): void
    {
        $key = $this->getCurrentCartKey();
        if ($key === 'default' && empty($this->cart) && ! $this->selectedTableId) {
            return;
        }

        $this->tablesCarts[$key] = [
            'cart' => $this->cart,
            'selectedCustomerId' => $this->selectedCustomerId,
            'discountAmount' => $this->discountAmount,
            'discountType' => $this->discountType,
            'orderNotes' => $this->orderNotes,
            'orderType' => $this->orderType,
            'customerName' => $this->customerName,
            'customerPhone' => $this->customerPhone,
            'deliveryAddress' => $this->deliveryAddress,
            'driverId' => $this->driverId,
            'deliveryStatus' => $this->deliveryStatus,
        ];

        session()->put('pos_tables_carts', $this->tablesCarts);
        session()->put('pos_selected_table_id', $this->selectedTableId);
        session()->put('pos_current_cart_key', $this->currentCartKey);
    }

    public function loadTableState(string $tableId): void
    {
        $this->selectCartKey($tableId ? "table_{$tableId}" : 'default');
    }

    public function mount(): void
    {
        $enabledCurrencies = auth()->user()->establishment?->getSetting('enabled_currencies', ['CDF', 'USD']) ?? ['CDF', 'USD'];
        $preferredCurrency = session()->get('pos_display_currency', 'CDF');
        $this->selectedDisplayCurrency = in_array($preferredCurrency, $enabledCurrencies, true) ? $preferredCurrency : ($enabledCurrencies[0] ?? 'CDF');

        // Load persisted state from session
        $this->tablesCarts = session()->get('pos_tables_carts', []);
        $this->currentCartKey = session()->get('pos_current_cart_key', 'default');

        if (str_starts_with($this->currentCartKey, 'table_')) {
            $this->selectedTableId = str_replace('table_', '', $this->currentCartKey);
        } else {
            $this->selectedTableId = '';
        }

        $key = $this->getCurrentCartKey();
        if (isset($this->tablesCarts[$key])) {
            $state = $this->tablesCarts[$key];
            $this->cart = $state['cart'] ?? [];
            $this->selectedCustomerId = $state['selectedCustomerId'] ?? '';
            $this->discountAmount = $state['discountAmount'] ?? 0.0;
            $this->discountType = $state['discountType'] ?? 'fixed';
            $this->orderNotes = $state['orderNotes'] ?? '';
            $this->orderType = $state['orderType'] ?? 'dine_in';
            $this->customerName = $state['customerName'] ?? '';
            $this->customerPhone = $state['customerPhone'] ?? '';
            $this->deliveryAddress = $state['deliveryAddress'] ?? '';
            $this->driverId = $state['driverId'] ?? '';
            $this->deliveryStatus = $state['deliveryStatus'] ?? 'pending';
        }

        // Check for active cash session
        $session = CashSession::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($session) {
            $this->currentSessionId = $session->id;
        } else {
            $this->selectedCashRegisterId = (string) CashRegister::query()
                ->where('establishment_id', auth()->user()->establishment_id)
                ->active()->value('id');
            $this->showOpenSession = true;
        }
    }

    /**
     * Restore fields added to the closing workflow when an already-open POS tab
     * sends a Livewire snapshot created by an older version of the component.
     */
    public function hydrate(): void
    {
        if (! isset($this->closingCdfAmount)) {
            $this->closingCdfAmount = 0.0;
        }

        if (! isset($this->closingUsdAmount)) {
            $this->closingUsdAmount = 0.0;
        }

        if (! isset($this->closingNote)) {
            $this->closingNote = '';
        }
    }

    // ── Session Management ────────────────────────────────
    public function openSession(): void
    {
        $existingSession = CashSession::query()
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
        if ($existingSession) {
            $this->currentSessionId = $existingSession->id;
            $this->showOpenSession = false;

            return;
        }

        $this->validate([
            'selectedCashRegisterId' => ['required', 'string'],
            'openingAmount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'openingNote' => ['nullable', 'string', 'max:500'],
        ]);

        $cashRegister = CashRegister::where('establishment_id', auth()->user()->establishment_id)
            ->active()->find($this->selectedCashRegisterId);

        if (! $cashRegister) {
            session()->flash('error', 'Aucune caisse configurée.');

            return;
        }

        if ($cashRegister->sessions()->where('status', 'open')->exists()) {
            throw ValidationException::withMessages([
                'selectedCashRegisterId' => 'Cette caisse est déjà ouverte par un autre utilisateur.',
            ]);
        }

        $session = CashSession::create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => auth()->id(),
            'opening_amount' => $this->openingAmount,
            'opening_note' => $this->openingNote ?: null,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->currentSessionId = $session->id;
        $this->showOpenSession = false;
        session()->flash('success', 'Caisse ouverte avec succès.');
    }

    public function requestExit(string $destination = 'none'): void
    {
        abort_unless(in_array($destination, ['none', 'admin', 'logout'], true), 422);

        if (! $this->currentSessionId) {
            $destination === 'logout'
                ? $this->dispatch('submit-pos-logout')
                : $this->redirectRoute('admin.dashboard', navigate: true);

            return;
        }

        $this->exitAfterClose = $destination;
        $this->closingCdfAmount = $this->session_expected_cash;
        $this->closingUsdAmount = 0;
        $this->closingNote = '';
        $this->showCloseSession = true;
    }

    public function closeSession(): void
    {
        $this->validate([
            'closingCdfAmount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'closingUsdAmount' => ['required', 'numeric', 'min:0', 'max:999999'],
            'closingNote' => ['nullable', 'string', 'max:1000'],
        ]);

        if (collect($this->tablesCarts)->contains(fn ($state) => ! empty($state['cart'] ?? [])) || ! empty($this->cart)) {
            throw ValidationException::withMessages([
                'closingCdfAmount' => 'Réglez ou videz toutes les commandes en attente avant de clôturer.',
            ]);
        }

        $countedTotal = (float) $this->closingCdfAmount + ((float) $this->closingUsdAmount * $this->usd_cdf_rate);
        $difference = $countedTotal - $this->session_expected_cash;
        if (abs($difference) >= 1 && trim($this->closingNote) === '') {
            throw ValidationException::withMessages([
                'closingNote' => 'Expliquez l’écart de caisse avant la clôture.',
            ]);
        }

        DB::transaction(function () use ($countedTotal): void {
            $session = CashSession::query()->lockForUpdate()
                ->whereKey($this->currentSessionId)
                ->where('user_id', auth()->id())
                ->where('status', 'open')
                ->firstOrFail();

            $session->cash_count = [
                'cdf' => (float) $this->closingCdfAmount,
                'usd' => (float) $this->closingUsdAmount,
                'usd_cdf_rate' => $this->usd_cdf_rate,
                'usd_equivalent_cdf' => (float) $this->closingUsdAmount * $this->usd_cdf_rate,
            ];
            $session->save();
            $session->close($countedTotal, $this->closingNote ?: null);
        }, 3);

        $destination = $this->exitAfterClose;
        $this->currentSessionId = null;
        $this->showCloseSession = false;
        $this->exitAfterClose = 'none';

        if ($destination === 'logout') {
            $this->dispatch('submit-pos-logout');
        } elseif ($destination === 'admin') {
            $this->dispatch('leave-pos', url: route('admin.dashboard'));
        } else {
            $this->showOpenSession = true;
            session()->flash('success', 'Session clôturée et comptage enregistré.');
        }
    }

    public function switchDisplayCurrency(string $currency): void
    {
        $enabled = auth()->user()->establishment?->getSetting('enabled_currencies', ['CDF', 'USD']) ?? ['CDF', 'USD'];
        abort_unless(in_array($currency, $enabled, true), 422);
        $this->selectedDisplayCurrency = $currency;
        session()->put('pos_display_currency', $currency);
    }

    public function printProvisional(): void
    {
        if (empty($this->cart) || ! auth()->user()->establishment?->provisionalReceiptEnabled()) {
            return;
        }
        $this->saveCurrentTableState();
        $type = auth()->user()->establishment->getSetting('pos_provisional_receipt_type', 'addition');
        $this->dispatch('print-provisional', url: route('pos.print-addition', [
            'tableKey' => $this->getCurrentCartKey(),
            'document' => $type,
            'currency' => $this->selectedDisplayCurrency,
        ]));
    }

    // ── Cart Operations ────────────────────────────────
    public function addToCart(string $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $key = $productId;
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;
            $this->cart[$key]['total'] = $this->cart[$key]['qty'] * $this->cart[$key]['price'];
        } else {
            $this->cart[$key] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) ($product->promo_price ?? $product->selling_price),
                'original_price' => (float) $product->selling_price,
                'qty' => 1,
                'total' => (float) ($product->promo_price ?? $product->selling_price),
                'comment' => '',
                'category' => $product->category?->name ?? '',
            ];
        }

        // Auto-save state
        $this->saveCurrentTableState();
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
        $this->saveCurrentTableState();
    }

    public function incrementQty(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;
            $this->cart[$key]['total'] = $this->cart[$key]['qty'] * $this->cart[$key]['price'];
            $this->saveCurrentTableState();
        }
    }

    public function decrementQty(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']--;
            if ($this->cart[$key]['qty'] <= 0) {
                unset($this->cart[$key]);
            } else {
                $this->cart[$key]['total'] = $this->cart[$key]['qty'] * $this->cart[$key]['price'];
            }
            $this->saveCurrentTableState();
        }
    }

    public function updateComment(string $key, string $comment): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['comment'] = $comment;
            $this->saveCurrentTableState();
        }
    }

    public function clearCart(): void
    {
        $key = $this->getCurrentCartKey();
        unset($this->tablesCarts[$key]);

        $this->cart = [];
        $this->selectedTableId = '';
        $this->selectedCustomerId = '';
        $this->orderNotes = '';
        $this->discountAmount = 0;

        // Clear session key
        session()->put('pos_tables_carts', $this->tablesCarts);
        session()->put('pos_selected_table_id', '');
    }

    public function requestClearCart(): void
    {
        if (! empty($this->cart)) {
            $this->showClearCartModal = true;
        }
    }

    public function confirmClearCart(): void
    {
        $this->clearCart();
        $this->showClearCartModal = false;
    }

    // ── Table Selection ────────────────────────────────
    public function selectTable(string $tableId): void
    {
        $this->loadTableState($tableId);
        $this->showTableSelector = false;
    }

    public function clearTable(): void
    {
        $this->loadTableState('');
    }

    // ── Customer Selection ────────────────────────────────
    public function selectCustomer(string $customerId): void
    {
        $this->selectedCustomerId = $customerId;
        $this->showCustomerSelector = false;
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = '';
    }

    // ── Computed Properties ────────────────────────────────
    public function getSubtotalProperty(): float
    {
        return collect($this->cart)->sum('total');
    }

    public function getDiscountValueProperty(): float
    {
        if ($this->discountType === 'percentage') {
            return $this->subtotal * ($this->discountAmount / 100);
        }

        return $this->discountAmount;
    }

    public function getTotalProperty(): float
    {
        return max(0, $this->subtotal - $this->discount_value);
    }

    public function getCartCountProperty(): int
    {
        return collect($this->cart)->sum('qty');
    }

    public function getChangeAmountProperty(): float
    {
        return max(0, $this->cashReceived - $this->payment_currency_total);
    }

    public function getPaymentCurrencyRateProperty(): float
    {
        if ($this->selectedPaymentCurrency === 'CDF') {
            return 1;
        }

        $currencyId = Currency::where('code', $this->selectedPaymentCurrency)->value('id');
        $cdfId = Currency::where('code', 'CDF')->value('id');

        return (float) (ExchangeRate::query()
            ->where('establishment_id', auth()->user()->establishment_id)
            ->where('from_currency_id', $currencyId)
            ->where('to_currency_id', $cdfId)
            ->whereDate('effective_date', '<=', today())
            ->latest('effective_date')
            ->value('sell_rate') ?: 1);
    }

    public function getUsdCdfRateProperty(): float
    {
        $usdId = Currency::where('code', 'USD')->value('id');
        $cdfId = Currency::where('code', 'CDF')->value('id');

        return (float) (ExchangeRate::query()
            ->where('establishment_id', auth()->user()->establishment_id)
            ->where('from_currency_id', $usdId)
            ->where('to_currency_id', $cdfId)
            ->whereDate('effective_date', '<=', today())
            ->latest('effective_date')->value('sell_rate') ?: 1);
    }

    public function getDisplayCurrencyRateProperty(): float
    {
        return $this->selectedDisplayCurrency === 'USD' ? $this->usd_cdf_rate : 1;
    }

    public function displayAmount(float $amount): float
    {
        return $amount / max($this->display_currency_rate, 0.000001);
    }

    public function getDisplayCurrencySymbolProperty(): string
    {
        return $this->selectedDisplayCurrency === 'CDF' ? 'FC' : $this->selectedDisplayCurrency;
    }

    public function getActiveCashSessionProperty(): ?CashSession
    {
        return $this->currentSessionId
            ? CashSession::with('cashRegister')->find($this->currentSessionId)
            : null;
    }

    public function getSessionCashSalesProperty(): float
    {
        return $this->currentSessionId ? (float) Payment::query()
            ->where('cash_session_id', $this->currentSessionId)
            ->where('payments.status', 'completed')
            ->whereHas('paymentMethod', fn ($query) => $query->where('code', 'cash'))
            ->sum('amount') : 0;
    }

    public function getSessionNonCashSalesProperty(): float
    {
        return $this->currentSessionId ? (float) Payment::query()
            ->where('cash_session_id', $this->currentSessionId)
            ->where('payments.status', 'completed')
            ->whereHas('paymentMethod', fn ($query) => $query->where('code', '!=', 'cash'))
            ->sum('amount') : 0;
    }

    public function getSessionExpectedCashProperty(): float
    {
        return (float) ($this->active_cash_session?->opening_amount ?? 0) + $this->session_cash_sales;
    }

    public function getClosingTotalProperty(): float
    {
        $closingCdfAmount = isset($this->closingCdfAmount) ? (float) $this->closingCdfAmount : 0.0;
        $closingUsdAmount = isset($this->closingUsdAmount) ? (float) $this->closingUsdAmount : 0.0;

        return $closingCdfAmount + ($closingUsdAmount * $this->usd_cdf_rate);
    }

    public function getClosingDifferenceProperty(): float
    {
        return $this->closing_total - $this->session_expected_cash;
    }

    public function getPaymentCurrencyTotalProperty(): float
    {
        return round($this->total / max($this->payment_currency_rate, 0.000001), 2);
    }

    // ── Payment Flow ────────────────────────────────
    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            return;
        }
        $this->cashReceived = 0;
        $this->paymentRef = '';
        $this->paymentSplit = [];
        $enabledCurrencies = auth()->user()->establishment?->getSetting('enabled_currencies', ['CDF', 'USD']) ?? ['CDF', 'USD'];
        $this->selectedPaymentCurrency = in_array($this->selectedDisplayCurrency, $enabledCurrencies, true)
            ? $this->selectedDisplayCurrency
            : ($enabledCurrencies[0] ?? 'CDF');
        $this->showPayment = true;
    }

    public function processPayment(string $methodCode): void
    {
        if (empty($this->cart) || ! $this->currentSessionId) {
            return;
        }

        $this->validate([
            'orderType' => ['required', 'in:dine_in,takeaway,delivery'],
            'discountType' => ['required', 'in:fixed,percentage'],
            'discountAmount' => ['numeric', 'min:0'],
            'cashReceived' => ['numeric', 'min:0'],
            'paymentRef' => ['nullable', 'string', 'max:150'],
            'selectedPaymentCurrency' => ['required', 'in:CDF,USD,EUR'],
            'customerName' => ['nullable', 'string', 'max:150'],
            'customerPhone' => ['nullable', 'string', 'max:40'],
            'deliveryAddress' => ['nullable', 'string', 'max:500'],
            'orderNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sessionIsValid = CashSession::query()
            ->whereKey($this->currentSessionId)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        if (! $sessionIsValid) {
            throw ValidationException::withMessages([
                'currentSessionId' => 'La session de caisse n’est plus active.',
            ]);
        }

        $this->cart = collect($this->cart)->mapWithKeys(function (array $item, string $key) {
            $product = Product::query()->active()->available()->find($item['id'] ?? $key);

            if (! $product) {
                throw ValidationException::withMessages([
                    'cart' => 'Un produit du panier n’est plus disponible.',
                ]);
            }

            $quantity = max(1, min(1000, (int) ($item['qty'] ?? 1)));

            return [$product->id => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->effective_price,
                'original_price' => (float) $product->selling_price,
                'qty' => $quantity,
                'total' => $product->effective_price * $quantity,
                'comment' => mb_substr((string) ($item['comment'] ?? ''), 0, 255),
                'category' => $product->category?->name ?? '',
            ]];
        })->all();

        $maximumDiscount = auth()->user()->isSeller()
            ? config('yeboox.pos.seller_max_discount', 10)
            : config('yeboox.pos.max_discount_percentage', 100);

        if ($this->discountType === 'percentage' && $this->discountAmount > $maximumDiscount) {
            throw ValidationException::withMessages([
                'discountAmount' => "La remise maximale autorisée est de {$maximumDiscount}%.",
            ]);
        }

        $method = PaymentMethod::where('establishment_id', auth()->user()->establishment_id)
            ->where('code', $methodCode)->first();
        if (! $method) {
            return;
        }

        $establishmentId = auth()->user()->establishment_id;
        $enabledCurrencies = auth()->user()->establishment?->getSetting('enabled_currencies', ['CDF', 'USD']) ?? ['CDF', 'USD'];
        if (! in_array($this->selectedPaymentCurrency, $enabledCurrencies, true)) {
            throw ValidationException::withMessages([
                'selectedPaymentCurrency' => 'Cette devise n’est pas activée.',
            ]);
        }
        $paymentCurrency = Currency::where('code', $this->selectedPaymentCurrency)->firstOrFail();
        $exchangeRate = $this->payment_currency_rate;
        $currencyTotal = $this->payment_currency_total;

        if ($methodCode === 'cash' && $this->cashReceived < $currencyTotal) {
            throw ValidationException::withMessages([
                'cashReceived' => 'Le montant reçu est insuffisant.',
            ]);
        }

        // Determine order status and notes based on orderType
        $status = OrderStatus::Confirmed->value;
        $deliveryNotes = '';

        if ($this->orderType === 'takeaway') {
            $deliveryNotes = "À EMPORTER - Client: {$this->customerName}";
        } elseif ($this->orderType === 'delivery') {
            $driverUser = User::find($this->driverId);
            $driverName = $driverUser ? $driverUser->name : 'Non assigné';
            $deliveryNotes = "LIVRAISON\nClient: {$this->customerName}\nTél: {$this->customerPhone}\nAdresse: {$this->deliveryAddress}\nLivreur: {$driverName}\nStatut: ".($this->deliveryStatus === 'delivered' ? 'Livré' : ($this->deliveryStatus === 'cancelled' ? 'Annulé' : 'En cours'));

            if ($this->deliveryStatus === 'delivered') {
                $status = OrderStatus::Completed->value;
            } elseif ($this->deliveryStatus === 'cancelled') {
                $status = OrderStatus::Cancelled->value;
            }
        }

        $finalNotes = $this->orderNotes;
        if ($deliveryNotes) {
            $finalNotes = $finalNotes ? $finalNotes."\n\n".$deliveryNotes : $deliveryNotes;
        }

        $taxAmount = $this->total - ($this->total / 1.16);

        $changeAmount = $methodCode === 'cash' ? max(0, $this->cashReceived - $currencyTotal) : 0;
        $tenderedAmount = $methodCode === 'cash' ? $this->cashReceived : $currencyTotal;

        $kitchenEnabled = auth()->user()->establishment?->kitchenModuleEnabled() ?? false;

        $order = DB::transaction(function () use ($establishmentId, $status, $finalNotes, $taxAmount, $method, $changeAmount, $tenderedAmount, $paymentCurrency, $exchangeRate, $kitchenEnabled) {
            $table = $this->selectedTableId ? Table::find($this->selectedTableId) : null;
            $customer = $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null;

            $order = Order::create([
                'establishment_id' => $establishmentId,
                'branch_id' => auth()->user()->branch_id,
                'order_number' => Order::generateNumber($establishmentId),
                'table_id' => $table?->id,
                'customer_id' => $customer?->id,
                'user_id' => auth()->id(),
                'cash_session_id' => $this->currentSessionId,
                'type' => $this->orderType,
                'status' => $status,
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discount_value,
                'discount_type' => $this->discountAmount > 0 ? $this->discountType : null,
                'tax_amount' => $taxAmount,
                'total' => $this->total,
                'currency_id' => Currency::where('code', 'CDF')->value('id'),
                'notes' => $finalNotes ?: null,
                'is_paid' => true,
            ]);

            foreach ($this->cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'name' => $item['name'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'total' => $item['total'],
                    'comment' => $item['comment'] ?: null,
                    'kitchen_status' => $kitchenEnabled ? 'pending' : 'served',
                    'sent_to_kitchen_at' => $kitchenEnabled ? now() : null,
                    'served_at' => $kitchenEnabled ? null : now(),
                ]);
            }

            Payment::create([
                'establishment_id' => $establishmentId,
                'order_id' => $order->id,
                'payment_method_id' => $method->id,
                'amount' => $this->total,
                'tendered_amount' => $tenderedAmount,
                'currency_id' => $paymentCurrency->id,
                'exchange_rate' => $exchangeRate,
                'reference' => $this->paymentRef ?: null,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'cash_session_id' => $this->currentSessionId,
                'processed_by' => auth()->id(),
            ]);

            $table?->update(['status' => 'occupied']);
            $customer?->recordPurchase($this->total);

            return $order;
        }, 3);

        // Success feedback
        $this->lastOrderNumber = $order->order_number;
        $this->showPayment = false;
        $this->showSuccess = true;
        $this->dispatch('print-receipt', orderId: $order->id);
        $this->clearCart();
    }

    public function closeSuccess(): void
    {
        $this->showSuccess = false;
    }

    // ── Render ────────────────────────────────
    public function render()
    {
        $establishmentId = auth()->user()->establishment_id;

        $categories = Category::where('establishment_id', $establishmentId)
            ->active()->ordered()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $products = Product::where('establishment_id', $establishmentId)
            ->where('is_active', true)
            ->when($this->selectedCategory, fn ($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            }))
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(50)
            ->get();

        $paymentMethods = PaymentMethod::where('establishment_id', $establishmentId)
            ->active()->get();

        $enabledCurrencyCodes = auth()->user()->establishment?->getSetting('enabled_currencies', ['CDF', 'USD']) ?? ['CDF', 'USD'];
        $paymentCurrencies = Currency::active()->whereIn('code', $enabledCurrencyCodes)->orderBy('code')->get();

        $zones = Zone::where('establishment_id', $establishmentId)->active()->ordered()->get();

        $tables = Table::where('establishment_id', $establishmentId)
            ->active()
            ->when($this->selectedZone, fn ($q) => $q->where('zone_id', $this->selectedZone))
            ->with('zone')
            ->withCount(['orders as active_orders_count' => fn ($query) => $query->active()])
            ->orderBy('sort_order')
            ->get();

        $customers = [];
        if ($this->showCustomerSelector) {
            $customers = Customer::where('establishment_id', $establishmentId)
                ->active()
                ->search($this->customerSearch)
                ->limit(20)
                ->get();
        }

        $selectedTable = $this->selectedTableId ? Table::find($this->selectedTableId) : null;
        $selectedCustomer = $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null;

        $todayStats = [
            'orders' => Order::where('establishment_id', $establishmentId)->today()->count(),
            'revenue' => Order::where('establishment_id', $establishmentId)->today()->completed()->sum('total'),
        ];

        $drivers = User::where('establishment_id', $establishmentId)->get();

        $cashRegisters = CashRegister::where('establishment_id', $establishmentId)->active()->orderBy('name')->get();
        $provisionalReceiptEnabled = auth()->user()->establishment?->provisionalReceiptEnabled() ?? false;
        $provisionalReceiptType = auth()->user()->establishment?->getSetting('pos_provisional_receipt_type', 'addition') ?? 'addition';

        return view('livewire.pos.terminal', compact(
            'categories', 'products', 'paymentMethods', 'zones', 'tables',
            'customers', 'selectedTable', 'selectedCustomer', 'todayStats', 'drivers', 'paymentCurrencies',
            'cashRegisters', 'provisionalReceiptEnabled', 'provisionalReceiptType'
        ))->layout('layouts.pos', ['title' => 'Point de Vente']);
    }
}
