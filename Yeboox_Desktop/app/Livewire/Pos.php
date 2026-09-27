<?php

namespace App\Livewire;

use App\Models\AccountingRecord;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\DocumentPreferences;
use App\Support\DisplayCurrency;
use App\Support\LoyaltyProgram;
use App\Services\Sync\OfflineSalePublisher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('Point de vente')]
class Pos extends Component
{
    public string $search = '';

    public string $scanCode = '';

    public ?string $scanFeedback = null;

    public string $scanFeedbackType = 'neutral';

    public bool $showScanner = false;

    public string $category = 'all';

    public string $currency = DisplayCurrency::CDF;

    public string $fulfillmentType = 'dine_in';

    public ?string $tableName = null;

    public ?string $deliveryPhone = null;

    public ?string $deliveryAddress = null;

    public ?int $customerId = null;

    public string $paymentMethod = 'cash';

    public ?string $paymentReference = null;

    public float $discountAmount = 0;

    public float $amountReceived = 0;

    public float $openingBalance = 0;

    public float $openingBalanceCdf = 0;

    public float $closingBalance = 0;

    public float $closingBalanceCdf = 0;

    public ?string $openingNote = null;

    public ?string $sessionNote = null;

    public array $cart = [];

    public bool $showCart = false;

    public bool $showOpenRegister = false;

    public bool $showCloseRegister = false;

    public bool $showPayment = false;

    public bool $showReceipt = false;

    public bool $showTableSelector = false;

    public bool $showAddition = false;

    public bool $showPaymentProvider = false;

    public ?int $lastSaleId = null;

    public string $selectedTableZone = 'all';

    public string $currentOrderKey = 'counter';

    public array $tableOrders = [];

    public array $paymentLines = [];

    public bool $useLoyaltyReward = false;

    public ?int $editingPaymentIndex = null;

    public string $providerSearch = '';

    public string $additionReference = '';

    public float $lastChangeDue = 0;

    public array $lastChangeByCurrency = [];

    /** Requêtes de session et totaux réutilisées pendant un même rendu Livewire. */
    private bool $registerSessionResolved = false;

    private ?RegisterSession $registerSessionCache = null;

    private array $sessionCashSalesCache = [];

    private ?float $sessionNonCashSalesCache = null;

    public function mount(): void
    {
        $this->tableOrders = session()->get($this->ordersSessionKey(), []);
        $this->currentOrderKey = session()->get($this->currentOrderSessionKey(), 'counter');
        if (isset($this->tableOrders[$this->currentOrderKey])) {
            $this->loadOrderState($this->tableOrders[$this->currentOrderKey]);
        } else {
            $this->cart = session()->get($this->cartSessionKey(), []);
        }
        $this->currency = DisplayCurrency::baseCode();
        $this->lastChangeByCurrency = [$this->currency => 0];
        $this->showCloseRegister = false;
        $this->showOpenRegister = ! $this->registerSession();
    }

    public function updatedCurrency(string $value): void
    {
        $this->currency = DisplayCurrency::baseCode();

        session()->put([
            'ui.currency' => $this->currency,
            'pos.currency' => $this->currency,
        ]);
        $this->syncElectron('currency');
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = DisplayCurrency::baseCode();
        $this->updatedCurrency($this->currency);
    }

    public function updatedFulfillmentType(string $value): void
    {
        if (! in_array($value, ['dine_in', 'takeaway', 'delivery'], true)) {
            $this->fulfillmentType = 'dine_in';
        }
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
        $this->syncElectron('category');
    }

    public function setFulfillmentType(string $type): void
    {
        if ($type === 'dine_in') {
            $this->openTableSelector();

            return;
        }

        if (! in_array($type, ['takeaway', 'delivery'], true)) {
            return;
        }

        $this->saveCurrentOrderState();
        $this->currentOrderKey = $type;
        $this->loadOrderState($this->tableOrders[$this->currentOrderKey] ?? ['cart' => []], false);
        $this->fulfillmentType = $type;
        $this->tableName = null;
        $this->persistOrderContext();
        $this->syncElectron('fulfillment');
    }

    public function selectTable(string $table): void
    {
        $available = collect($this->tableZones())->flatMap(fn ($zone) => $zone['tables'])->pluck('name');
        abort_unless($available->contains($table), 404);

        $this->saveCurrentOrderState();
        $this->currentOrderKey = 'table:'.$table;
        $this->loadOrderState($this->tableOrders[$this->currentOrderKey] ?? ['cart' => []], false);
        $this->fulfillmentType = 'dine_in';
        $this->tableName = $table;
        $this->showTableSelector = false;
        $this->persistOrderContext();
        $this->syncElectron('table');
    }

    public function openTableSelector(): void
    {
        $this->saveCurrentOrderState();
        $this->showTableSelector = true;
    }

    public function openRegister(): void
    {
        abort_unless(auth()->user()?->can('manage register'), 403, 'Vous ne pouvez pas ouvrir la caisse.');
        $this->showCloseRegister = false;
        $this->validate([
            'openingBalance' => ['required', 'numeric', 'min:0'],
            'openingNote' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->registerSession()) {
            RegisterSession::create([
                'user_id' => Auth::id(),
                'opened_at' => now(),
                'opening_balance' => round($this->openingBalance, 8),
                'opening_balance_usd' => 0,
                'opening_balance_cdf' => 0,
                'closing_balance' => 0,
                'closing_balance_usd' => 0,
                'closing_balance_cdf' => 0,
                'exchange_rate' => min(9999999999, $this->exchangeRate()),
                'base_currency' => DisplayCurrency::baseCode(),
                'secondary_currency' => DisplayCurrency::secondaryCode(),
                'base_to_secondary_rate' => $this->exchangeRate(),
                'status' => 'open',
                'note' => $this->openingNote,
            ]);
            $this->resetRegisterQueryCache();
        }

        $this->showOpenRegister = false;
        $this->showCloseRegister = false;
        $this->syncElectron('register-opened');
        $this->dispatch('notify', message: 'Caisse ouverte avec succès.');
    }

    public function requestCloseRegister(): void
    {
        abort_unless(auth()->user()?->can('manage register'), 403, 'Vous ne pouvez pas clôturer la caisse.');
        $session = $this->registerSession();
        if (! $session) {
            $this->showCloseRegister = false;
            $this->showOpenRegister = true;

            return;
        }

        $this->saveCurrentOrderState();
        $this->showOpenRegister = false;
        $this->closingBalance = $this->expectedClosingBalance();
        $this->closingBalanceCdf = 0;
        $this->showCloseRegister = true;
    }

    public function closeRegister(): void
    {
        abort_unless(auth()->user()?->can('manage register'), 403, 'Vous ne pouvez pas clôturer la caisse.');
        $this->showOpenRegister = false;
        $this->saveCurrentOrderState();
        if ($this->pendingOrdersCount() > 0) {
            $this->showCloseRegister = true;
            $this->addError('closingBalance', 'Réglez ou videz toutes les commandes en attente avant de clôturer.');

            return;
        }

        $this->validate([
            'closingBalance' => ['required', 'numeric', 'min:0'],
            'sessionNote' => [Rule::requiredIf(abs($this->closingDifference()) >= 0.01), 'nullable', 'string', 'max:1000'],
        ]);
        $session = $this->registerSession();

        if ($session) {
            $session->update([
                'closed_at' => now(),
                'closing_balance' => round($this->closingBalance, 8),
                'closing_balance_usd' => 0,
                'closing_balance_cdf' => 0,
                'status' => 'closed',
                'note' => $this->sessionNote,
            ]);
            $this->resetRegisterQueryCache();
        }

        $this->showCloseRegister = false;
        $this->showOpenRegister = false;
        $this->syncElectron('register-closed');
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function addProduct(int $productId): void
    {
        if (! $this->registerSession()) {
            $this->showCloseRegister = false;
            $this->showOpenRegister = true;

            return;
        }

        $product = Product::query()->whereKey($productId)->where('is_active', true)->firstOrFail();
        $current = (float) ($this->cart[$productId]['quantity'] ?? 0);

        if ($product->type === 'product' && $current + 1 > (float) $product->stock_qty) {
            $this->addError('cart', "Stock insuffisant pour {$product->name}.");

            return;
        }

        $this->cart[$productId] = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            // Les ventes et écritures conservent toujours une base HT.
            'price' => $product->netUnitPrice(),
            'gross_price' => $product->grossUnitPrice(),
            'tax_included' => (bool) $product->tax_included,
            'tax_rate' => (float) $product->sales_tax_rate,
            'quantity' => $current + 1,
            'type' => $product->type,
        ];
        $this->persistCart();
    }

    public function openScanner(): void
    {
        $this->resetValidation('scanCode');
        $this->scanFeedback = null;
        $this->showScanner = true;
        $this->dispatch('scanner-focus');
    }

    /** Ajoute au panier un article lu par scanner code-barres ou QR clavier. */
    public function scanProductCode(): void
    {
        $this->resetValidation(['scanCode', 'cart']);
        if (! $this->registerSession()) {
            $this->showCloseRegister = false;
            $this->showOpenRegister = true;
            $this->scanFeedback = 'Ouvrez la caisse avant de scanner un article.';
            $this->scanFeedbackType = 'error';

            return;
        }

        $code = $this->normalizeScannedCode($this->scanCode);
        if ($code === '') {
            $this->addError('scanCode', 'Scannez ou saisissez un code produit.');
            $this->scanFeedback = 'Aucun code reçu.';
            $this->scanFeedbackType = 'error';
            $this->dispatch('scanner-focus');

            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        if (! $product) {
            $this->addError('scanCode', "Aucun article actif ne correspond au code {$code}.");
            $this->scanFeedback = "Code inconnu : {$code}";
            $this->scanFeedbackType = 'error';
            $this->dispatch('scanner-focus');

            return;
        }

        $this->addProduct($product->id);
        if ($this->getErrorBag()->has('cart')) {
            $this->scanFeedback = "Impossible d’ajouter {$product->name} : stock insuffisant.";
            $this->scanFeedbackType = 'error';
            $this->dispatch('scanner-focus');

            return;
        }

        $this->scanCode = '';
        $this->scanFeedback = "{$product->name} ajouté au panier.";
        $this->scanFeedbackType = 'success';
        $this->showScanner = false;
        $this->dispatch('scanner-focus');
        $this->syncElectron('product-scanned');
    }

    public function updatedCustomerId(): void
    {
        // Un cadeau appartient à un client précis et ne suit jamais un changement de profil.
        $this->useLoyaltyReward = false;
        $this->persistCart();
    }

    public function applyLoyaltyReward(): void
    {
        $customer = $this->customerId ? Customer::find($this->customerId) : null;
        $reward = LoyaltyProgram::rewardProductId()
            ? Product::query()->whereKey(LoyaltyProgram::rewardProductId())->whereIn('type', ['product', 'service'])->where('is_active', true)->first()
            : null;

        if (! LoyaltyProgram::usesServices() || ! $customer || $customer->loyalty_reward_balance < 1 || ! $reward) {
            $this->addError('cart', 'Aucune carte cadeau offerte disponible pour ce client.');

            return;
        }

        if ($reward->type === 'product' && (float) $reward->stock_qty < 1) {
            $this->addError('cart', "Le produit cadeau {$reward->name} est épuisé. Réapprovisionnez-le ou choisissez un autre cadeau.");

            return;
        }

        if (! isset($this->cart[$reward->id])) {
            $this->addProduct($reward->id);
        }
        $this->useLoyaltyReward = isset($this->cart[$reward->id]);
        $this->persistCart();
    }

    public function removeLoyaltyReward(): void
    {
        $this->useLoyaltyReward = false;
        $this->persistCart();
    }

    public function increment(int $productId): void
    {
        $this->addProduct($productId);
    }

    public function decrement(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantity']--;
        if ($this->cart[$productId]['quantity'] <= 0) {
            unset($this->cart[$productId]);
        }
        $this->persistCart();
    }

    public function remove(int $productId): void
    {
        unset($this->cart[$productId]);
        $this->persistCart();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discountAmount = 0;
        $this->useLoyaltyReward = false;
        $this->persistCart();
    }

    public function startPayment(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Ajoutez au moins un article.');

            return;
        }

        if (! $this->registerSession()) {
            $this->showCloseRegister = false;
            $this->showOpenRegister = true;

            return;
        }

        $this->paymentLines = [];
        $this->addPaymentLine('cash');
        $this->amountReceived = $this->total();
        $this->showPayment = true;
    }

    public function openAddition(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Ajoutez au moins un article avant d’imprimer une addition.');

            return;
        }

        $this->additionReference = 'ADD-'.now()->format('Ymd-His');
        $this->showAddition = true;
    }

    public function addPaymentLine(string $method): void
    {
        if (! in_array($method, ['cash', 'mobile_money', 'bank', 'yepay', 'points'], true)) {
            return;
        }
        if ($method === 'points' && ! LoyaltyProgram::usesPoints()) {
            $this->addError('paymentLines', 'Le paiement par points fidélité n’est pas activé.');

            return;
        }

        $currency = DisplayCurrency::baseCode();
        $remaining = max(0, $this->paymentRemaining());
        $this->paymentLines[] = [
            'method' => $method,
            'amount' => $remaining,
            'received' => $method === 'cash' ? $remaining : null,
            'currency' => $currency,
            'provider' => '',
            'reference' => '',
        ];

        $index = array_key_last($this->paymentLines);
        if (! in_array($method, ['cash', 'points'], true)) {
            $this->configurePaymentLine($index);
        }
    }

    public function removePaymentLine(int $index): void
    {
        unset($this->paymentLines[$index]);
        $this->paymentLines = array_values($this->paymentLines);
    }

    public function configurePaymentLine(int $index): void
    {
        if (! isset($this->paymentLines[$index])) {
            return;
        }

        $this->editingPaymentIndex = $index;
        $this->providerSearch = '';
        $this->showPaymentProvider = true;
    }

    public function selectPaymentProvider(string $provider): void
    {
        if ($this->editingPaymentIndex === null || ! isset($this->paymentLines[$this->editingPaymentIndex])) {
            return;
        }

        $allowed = collect($this->providersFor($this->paymentLines[$this->editingPaymentIndex]['method']))->pluck('name');
        abort_unless($allowed->contains($provider), 404);
        $this->paymentLines[$this->editingPaymentIndex]['provider'] = $provider;
        $this->showPaymentProvider = false;
    }

    public function paymentLinesTotal(): float
    {
        return round(collect($this->paymentLines)->sum(fn ($line) => $this->paymentBaseAmount($line)), 8);
    }

    public function paymentRemaining(): float
    {
        return round($this->total() - $this->paymentLinesTotal(), 8);
    }

    public function setPaymentCurrency(int $index, string $currency): void
    {
        if (! isset($this->paymentLines[$index])) {
            return;
        }
        $this->paymentLines[$index]['currency'] = DisplayCurrency::baseCode();
    }

    public function paymentBaseAmount(array $line): float
    {
        return $this->normalizeNumericInput($line['amount'] ?? 0);
    }

    public function cashReceivedTotal(string $currency): float
    {
        return round(collect($this->paymentLines)
            ->where('method', 'cash')
            ->where('currency', $currency)
            ->sum(fn ($line) => $this->normalizeNumericInput($line['received'] ?? 0)), DisplayCurrency::decimals($currency));
    }

    public function changeDue(string $currency): float
    {
        return round(collect($this->paymentLines)
            ->where('method', 'cash')
            ->where('currency', $currency)
            ->sum(fn ($line) => max(0, $this->normalizeNumericInput($line['received'] ?? 0) - $this->normalizeNumericInput($line['amount'] ?? 0))), DisplayCurrency::decimals($currency));
    }

    public function customerPointsValue(): float
    {
        $points = $this->customerId ? (int) Customer::whereKey($this->customerId)->value('points') : 0;

        return LoyaltyProgram::pointsValue($points);
    }

    public function checkout(): void
    {
        foreach ($this->paymentLines as $index => $line) {
            $this->paymentLines[$index]['currency'] = DisplayCurrency::baseCode();
            $this->paymentLines[$index]['amount'] = $this->normalizeNumericInput($line['amount'] ?? 0);
            if ($line['method'] === 'cash') {
                $this->paymentLines[$index]['received'] = $this->normalizeNumericInput($line['received'] ?? $line['amount'] ?? 0);
            }
        }

        $this->validate([
            'customerId' => ['nullable', Rule::exists('customers', 'id')->where('is_active', true)],
            'fulfillmentType' => ['required', Rule::in(['dine_in', 'takeaway', 'delivery'])],
            'tableName' => [Rule::requiredIf($this->fulfillmentType === 'dine_in'), 'nullable', 'string', 'max:50'],
            'deliveryPhone' => [Rule::requiredIf($this->fulfillmentType === 'delivery'), 'nullable', 'string', 'max:40'],
            'deliveryAddress' => [Rule::requiredIf($this->fulfillmentType === 'delivery'), 'nullable', 'string', 'max:500'],
            'discountAmount' => ['numeric', 'min:0', 'max:'.max(0, $this->subtotal() - $this->loyaltyRewardSubtotalDiscount())],
            'paymentLines' => ['required', 'array', 'min:1'],
            'paymentLines.*.method' => ['required', Rule::in(['cash', 'mobile_money', 'bank', 'yepay', 'points'])],
            'paymentLines.*.currency' => ['required', Rule::in([DisplayCurrency::baseCode()])],
            // Une carte cadeau offerte peut produire un ticket valide à zéro.
            'paymentLines.*.amount' => ['required', 'numeric', 'gte:0'],
            'paymentLines.*.received' => ['nullable', 'numeric', 'min:0'],
            'paymentLines.*.provider' => ['nullable', 'string', 'max:100'],
            'paymentLines.*.reference' => ['nullable', 'string', 'max:120'],
        ]);

        if (abs($this->paymentRemaining()) > 0.01) {
            $this->addError('paymentLines', 'La somme des paiements doit correspondre exactement au total de la commande.');

            return;
        }

        foreach ($this->paymentLines as $index => $line) {
            if ($line['method'] === 'cash' && (float) ($line['received'] ?? $line['amount']) < (float) $line['amount']) {
                $this->addError("paymentLines.{$index}.received", 'Le montant reçu doit couvrir la part en espèces.');
            }
            if ($line['method'] !== 'cash' && $line['method'] !== 'points' && trim((string) ($line['provider'] ?? '')) === '') {
                $this->addError("paymentLines.{$index}.provider", 'Sélectionnez une banque ou un opérateur.');
            }
            if (in_array($line['method'], ['mobile_money', 'bank', 'yepay'], true) && trim((string) ($line['reference'] ?? '')) === '') {
                $this->addError("paymentLines.{$index}.reference", 'Saisissez la référence de transaction.');
            }
        }

        $pointsAmount = (float) collect($this->paymentLines)->where('method', 'points')->sum(fn ($line) => $this->paymentBaseAmount($line));
        $pointsToRedeem = LoyaltyProgram::pointsRequired($pointsAmount);
        $customer = $this->customerId ? Customer::find($this->customerId) : null;
        if ($pointsAmount > 0 && ! LoyaltyProgram::usesPoints()) {
            $this->addError('paymentLines', 'Activez la carte par points dans Configuration avant de l’utiliser.');
        } elseif ($pointsAmount > 0 && ! $customer) {
            $this->addError('paymentLines', 'Sélectionnez un client pour utiliser son portefeuille de points.');
        } elseif ($customer && $pointsToRedeem > (int) $customer->points) {
            $this->addError('paymentLines', 'Le solde de points du client est insuffisant.');
        }

        $redeemLoyaltyReward = $this->useLoyaltyReward;
        if ($redeemLoyaltyReward && ! $this->canRedeemLoyaltyReward($customer)) {
            $this->addError('cart', 'La carte cadeau offerte n’est plus disponible. Actualisez la commande.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $session = $this->registerSession();
        if (! $session || $this->cart === []) {
            $this->showPayment = false;
            $this->showCloseRegister = false;
            $this->showOpenRegister = ! $session;

            return;
        }

        $sale = DB::transaction(function () use ($session, $pointsToRedeem, $redeemLoyaltyReward) {
            $subtotal = $this->subtotal() - $this->loyaltyRewardSubtotalDiscount();
            $tax = $this->taxTotal() - $this->loyaltyRewardTaxDiscount();
            $total = $this->total();
            $primaryMethod = count($this->paymentLines) > 1 ? 'multiple' : $this->paymentLines[0]['method'];
            $references = collect($this->paymentLines)->pluck('reference')->filter()->implode(', ');
            $earnedPoints = LoyaltyProgram::usesPoints()
                ? (int) floor((float) collect($this->paymentLines)->where('method', '!=', 'points')->sum(fn ($line) => $this->paymentBaseAmount($line)))
                : 0;
            $sale = Sale::create([
                'customer_id' => $this->customerId,
                'user_id' => Auth::id(),
                'register_session_id' => $session->id,
                'department' => 'pos',
                'fulfillment_type' => $this->fulfillmentType,
                'table_name' => $this->fulfillmentType === 'dine_in' ? $this->tableName : null,
                'delivery_phone' => $this->fulfillmentType === 'delivery' ? $this->deliveryPhone : null,
                'delivery_address' => $this->fulfillmentType === 'delivery' ? $this->deliveryAddress : null,
                'payment_method' => $primaryMethod,
                'payment_reference' => $references ?: null,
                'total_amount' => $total,
                'discount_amount' => $this->discountAmount + $this->loyaltyRewardSubtotalDiscount(),
                'tax_amount' => $tax,
                'paid_amount' => $total,
                'points_earned' => $earnedPoints,
                'points_redeemed' => $pointsToRedeem,
            ]);

            foreach ($this->paymentLines as $line) {
                $method = PaymentMethod::query()->where('code', $line['method'])->first();
                $currency = DisplayCurrency::baseCode();
                $baseAmount = $this->paymentBaseAmount($line);
                $received = (float) ($line['received'] ?? $line['amount']);
                $change = $line['method'] === 'cash' ? max(0, $received - (float) $line['amount']) : 0;
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method_id' => $method?->id,
                    'method' => $line['method'],
                    'provider' => $line['provider'] ?: null,
                    'reference' => $line['reference'] ?: null,
                    'amount' => $line['amount'],
                    'currency' => $currency,
                    'points_redeemed' => $line['method'] === 'points' ? LoyaltyProgram::pointsRequired($baseAmount) : 0,
                    'metadata' => [
                        'base_amount' => $baseAmount,
                        'base_currency' => DisplayCurrency::baseCode(),
                        'secondary_currency' => DisplayCurrency::secondaryCode(),
                        'base_to_secondary_rate' => $this->sessionExchangeRate($session),
                        ...($line['method'] === 'cash' ? ['received' => $received, 'change' => $change] : []),
                    ],
                ]);
            }

            foreach ($this->cart as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line['id']);
                $quantity = (float) $line['quantity'];
                $rewardQuantity = $redeemLoyaltyReward && (int) $product->id === LoyaltyProgram::rewardProductId() ? 1.0 : 0.0;
                $paidQuantity = max(0, $quantity - $rewardQuantity);
                $lineTotal = round((float) $line['price'] * $paidQuantity, 8);
                $lineTax = round($this->cartLineGrossUnit($line) * $paidQuantity - $lineTotal, 8);
                if ($paidQuantity > 0) {
                    $sale->items()->create([
                        'product_id' => $product->id, 'quantity' => $paidQuantity, 'unit_price' => $line['price'],
                        'sales_tax_rate' => $line['tax_rate'], 'total_price' => $lineTotal, 'tax_amount' => $lineTax,
                    ]);
                }
                if ($rewardQuantity > 0) {
                    $sale->items()->create([
                        'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 0,
                        'sales_tax_rate' => 0, 'total_price' => 0, 'tax_amount' => 0,
                    ]);
                }

                if ($product->type === 'product') {
                    $before = (float) $product->stock_qty;
                    if ($before < $quantity) {
                        throw ValidationException::withMessages(['cart' => "Stock insuffisant pour {$product->name}."]);
                    }
                    $product->update(['stock_qty' => $before - $quantity]);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'reason' => 'sale',
                        'reference' => 'POS-'.$sale->id,
                        'quantity' => $quantity,
                        'unit_cost' => $product->cost,
                        'stock_before' => $before,
                        'stock_after' => $before - $quantity,
                        'description' => "Vente POS #{$sale->id}",
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            if ($this->customerId) {
                $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($this->customerId);
                if ($pointsToRedeem > (int) $lockedCustomer->points) {
                    throw ValidationException::withMessages(['paymentLines' => 'Le solde de points du client a changé.']);
                }
                $customerUpdates = ['points' => (int) $lockedCustomer->points - $pointsToRedeem + $earnedPoints];
                if (LoyaltyProgram::usesServices()) {
                    if ($redeemLoyaltyReward && $lockedCustomer->loyalty_reward_balance < 1) {
                        throw ValidationException::withMessages(['cart' => 'Le solde de cadeaux du client a changé.']);
                    }
                    $paidServices = (int) floor(collect($this->cart)
                        ->filter(fn ($line) => ($line['type'] ?? null) === 'service' && (float) ($line['price'] ?? 0) > 0)
                        ->sum('quantity'));
                    $rewardLine = $this->cart[LoyaltyProgram::rewardProductId()] ?? null;
                    // Seule une prestation offerte est exclue du compteur. Un produit
                    // cadeau ne doit pas annuler une prestation réellement payée.
                    if ($redeemLoyaltyReward && ($rewardLine['type'] ?? null) === 'service') {
                        $paidServices--;
                    }
                    $progress = (int) $lockedCustomer->loyalty_service_count + max(0, $paidServices);
                    $earnedRewards = intdiv($progress, LoyaltyProgram::requiredServices());
                    $customerUpdates['loyalty_service_count'] = $progress % LoyaltyProgram::requiredServices();
                    // La carte cadeau est binaire : 1 lorsqu'elle est offerte, 0 après utilisation.
                    $remainingReward = $redeemLoyaltyReward ? 0 : min(1, (int) $lockedCustomer->loyalty_reward_balance);
                    $customerUpdates['loyalty_reward_balance'] = min(1, $remainingReward + $earnedRewards);
                }
                $lockedCustomer->update($customerUpdates);
            }

            $this->postAccounting($sale, $subtotal, $tax, $this->paymentLines);

            // L'outbox est écrite dans la même transaction SQLite : la vente
            // reste encaissée hors ligne et ne peut jamais être oubliée.
            app(OfflineSalePublisher::class)->publish($sale);

            return $sale;
        });

        $this->lastSaleId = $sale->id;
        $this->sessionCashSalesCache = [];
        $this->sessionNonCashSalesCache = null;
        $baseCurrency = DisplayCurrency::baseCode();
        $this->lastChangeByCurrency = [$baseCurrency => $this->changeDue($baseCurrency)];
        $this->lastChangeDue = $this->lastChangeByCurrency[$baseCurrency];
        $this->cart = [];
        $this->discountAmount = 0;
        $this->useLoyaltyReward = false;
        $this->paymentReference = null;
        $this->paymentLines = [];
        $this->amountReceived = 0;
        unset($this->tableOrders[$this->currentOrderKey]);
        $this->currentOrderKey = 'counter';
        $this->tableName = null;
        $this->persistOrderContext();
        $this->persistCart();
        $this->showPayment = false;
        $this->showCart = false;
        $this->showReceipt = true;
        $this->dispatch('pos-receipt-ready', saleId: $sale->id);
    }

    public function subtotal(): float
    {
        return round(collect($this->cart)->sum(fn ($line) => (float) $line['price'] * (float) $line['quantity']), 8);
    }

    public function taxTotal(): float
    {
        return round(collect($this->cart)->sum(fn ($line) => $this->cartLineGrossTotal($line)) - $this->subtotal(), 8);
    }

    public function total(): float
    {
        return max(0, round($this->subtotal() + $this->taxTotal() - $this->discountAmount - $this->loyaltyRewardSubtotalDiscount() - $this->loyaltyRewardTaxDiscount(), 8));
    }

    public function cartLineGrossUnit(array $line): float
    {
        return isset($line['gross_price'])
            ? round((float) $line['gross_price'], 8)
            : round((float) $line['price'] * (1 + ((float) ($line['tax_rate'] ?? 0) / 100)), 8);
    }

    public function cartLineGrossTotal(array $line): float
    {
        return round($this->cartLineGrossUnit($line) * (float) $line['quantity'], 8);
    }

    public function loyaltyRewardSubtotalDiscount(): float
    {
        $rewardId = LoyaltyProgram::rewardProductId();
        $line = $rewardId ? ($this->cart[$rewardId] ?? null) : null;

        return $this->useLoyaltyReward && $line ? round((float) $line['price'], 8) : 0;
    }

    public function loyaltyRewardTaxDiscount(): float
    {
        $rewardId = LoyaltyProgram::rewardProductId();
        $line = $rewardId ? ($this->cart[$rewardId] ?? null) : null;

        return $this->useLoyaltyReward && $line
            ? round($this->cartLineGrossUnit($line) - (float) $line['price'], 8)
            : 0;
    }

    private function canRedeemLoyaltyReward(?Customer $customer): bool
    {
        $rewardId = LoyaltyProgram::rewardProductId();
        $rewardIsAvailable = $rewardId && Product::query()
            ->whereKey($rewardId)
            ->whereIn('type', ['product', 'service'])
            ->where('is_active', true)
            ->exists();

        return LoyaltyProgram::usesServices()
            && $customer
            && $customer->loyalty_reward_balance > 0
            && $rewardIsAvailable
            && isset($this->cart[$rewardId]);
    }

    public function cartCount(): float
    {
        return (float) collect($this->cart)->sum('quantity');
    }

    public function expectedClosingBalance(): float
    {
        $session = $this->registerSession();

        return $session ? round((float) $session->opening_balance + $this->sessionCashSales(), 8) : 0;
    }

    public function expectedClosingBalanceFor(string $currency): float
    {
        return $currency === DisplayCurrency::baseCode() ? $this->expectedClosingBalance() : 0;
    }

    public function sessionCashSales(?string $currency = null): float
    {
        $cacheKey = $currency ?? 'base';
        if (array_key_exists($cacheKey, $this->sessionCashSalesCache)) {
            return $this->sessionCashSalesCache[$cacheKey];
        }

        $session = $this->registerSession();
        if (! $session) {
            return 0;
        }

        $payments = SalePayment::query()->where('method', 'cash')
            ->whereHas('sale', fn ($query) => $query->where('register_session_id', $session->id))->get();
        if ($currency && $currency !== DisplayCurrency::baseCode()) {
            return $this->sessionCashSalesCache[$cacheKey] = 0;
        }
        if ($currency) {
            $split = (float) $payments->where('currency', $currency)->sum('amount');
            $legacy = (float) $session->sales()->whereDoesntHave('payments')->where('payment_method', 'cash')->sum('paid_amount');

            return $this->sessionCashSalesCache[$cacheKey] = round($split + $legacy, 8);
        }

        $split = (float) $payments->sum(fn (SalePayment $payment) => (float) data_get(
            $payment->metadata,
            'base_amount',
            $this->toBaseAmount((float) $payment->amount, $payment->currency),
        ));
        $legacy = (float) $session->sales()->whereDoesntHave('payments')->where('payment_method', 'cash')->sum('paid_amount');

        return $this->sessionCashSalesCache[$cacheKey] = round($split + $legacy, 2);
    }

    public function sessionNonCashSales(): float
    {
        if ($this->sessionNonCashSalesCache !== null) {
            return $this->sessionNonCashSalesCache;
        }

        $session = $this->registerSession();
        if (! $session) {
            return 0;
        }

        $split = (float) SalePayment::query()->where('method', '!=', 'cash')
            ->whereHas('sale', fn ($query) => $query->where('register_session_id', $session->id))->get()
            ->sum(fn (SalePayment $payment) => (float) data_get(
                $payment->metadata,
                'base_amount',
                $this->toBaseAmount((float) $payment->amount, $payment->currency),
            ));
        $legacy = (float) $session->sales()->whereDoesntHave('payments')->where('payment_method', '!=', 'cash')->sum('paid_amount');

        return $this->sessionNonCashSalesCache = round($split + $legacy, 2);
    }

    public function closingDifference(): float
    {
        return round($this->closingBalance - $this->expectedClosingBalance(), 8);
    }

    public function closingDifferenceFor(string $currency): float
    {
        return $currency === DisplayCurrency::baseCode() ? $this->closingDifference() : 0;
    }

    public function pendingOrdersCount(): int
    {
        return collect($this->tableOrders)->filter(fn ($state) => ! empty($state['cart'] ?? []))->count();
    }

    /** @return array<int, array{key:string,label:string,items:int,total:float}> */
    public function pendingOrdersSummary(): array
    {
        return collect($this->tableOrders)
            ->filter(fn ($state) => ! empty($state['cart'] ?? []))
            ->map(function (array $state, string $key): array {
                $cart = collect($state['cart'] ?? []);
                $label = match (true) {
                    str_starts_with($key, 'table:') => str($key)->after('table:')->toString(),
                    $key === 'takeaway' => 'Commande à emporter',
                    $key === 'delivery' => 'Commande en livraison',
                    default => 'Commande comptoir',
                };

                return [
                    'key' => $key,
                    'label' => $label,
                    'items' => (int) $cart->sum(fn ($line) => (float) ($line['quantity'] ?? 0)),
                    'total' => (float) $cart->sum(fn ($line) => $this->cartLineGrossTotal($line)),
                ];
            })->values()->all();
    }

    public function resumePendingOrder(string $key): void
    {
        abort_unless(array_key_exists($key, $this->tableOrders), 404);
        $this->currentOrderKey = $key;
        $this->loadOrderState($this->tableOrders[$key]);
        $this->showCloseRegister = false;
        $this->persistOrderContext();
        $this->syncElectron('order-resumed');
    }

    public function tableHasOrder(string $table): bool
    {
        if ($this->currentOrderKey === 'table:'.$table && $this->cart !== []) {
            return true;
        }

        return ! empty($this->tableOrders['table:'.$table]['cart'] ?? []);
    }

    public function occupiedTablesCount(): int
    {
        return collect($this->tableZones())
            ->flatMap(fn (array $zone) => $zone['tables'])
            ->filter(fn (array $table) => $this->tableHasOrder($table['name']))
            ->count();
    }

    public function availableTablesCount(): int
    {
        $total = collect($this->tableZones())->sum(fn (array $zone) => count($zone['tables']));

        return max(0, $total - $this->occupiedTablesCount());
    }

    public function displayAmount(float $amount): string
    {
        return DisplayCurrency::format($amount, DisplayCurrency::baseCode());
    }

    public function exchangeRate(): float
    {
        return DisplayCurrency::rate();
    }

    public function sessionExchangeRate(?RegisterSession $session = null): float
    {
        $session ??= $this->registerSession();

        return max(0.00000001, (float) ($session?->base_to_secondary_rate ?: $this->exchangeRate()));
    }

    public function toBaseAmount(float $amount, string $currency): float
    {
        return round($currency === DisplayCurrency::secondaryCode() ? $amount / $this->sessionExchangeRate() : $amount, 8);
    }

    public function fromBaseAmount(float $amount, string $currency): float
    {
        return round(
            $currency === DisplayCurrency::secondaryCode() ? $amount * $this->sessionExchangeRate() : $amount,
            DisplayCurrency::decimals($currency),
        );
    }

    public function formatCurrencyAmount(float $amount, string $currency): string
    {
        return number_format($amount, DisplayCurrency::decimals($currency), ',', ' ').' '.$currency;
    }

    private function normalizeNumericInput(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(["\u{00A0}", ' ', ','], ['', '', '.'], trim($value));
        }

        return is_numeric($value) ? max(0, (float) $value) : 0.0;
    }

    public function paymentIcon(PaymentMethod $method): string
    {
        return match ($method->code) {
            'cash' => 'banknotes',
            'bank' => 'building-library',
            'mobile_money', 'mpesa', 'airtel', 'orange' => 'device-phone-mobile',
            'yepay' => 'sparkles',
            'points' => 'star',
            default => 'credit-card',
        };
    }

    public function paymentLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'bank' => 'Banque',
            'yepay' => 'YePay',
            'points' => 'Points client',
            default => $method,
        };
    }

    public function providersFor(string $method): array
    {
        $defaults = match ($method) {
            'bank' => ['Rawbank', 'EquityBCDC', 'Ecobank', 'TMB', 'Access Bank', 'FBNBank', 'UBA', 'Afriland First Bank', 'Bank of Africa'],
            'mobile_money' => ['M-Pesa', 'Airtel Money', 'Orange Money', 'Afrimoney'],
            'yepay' => ['YePay Wallet'],
            default => [],
        };
        $settingKey = $method === 'bank' ? 'pos_banks' : ($method === 'mobile_money' ? 'pos_mobile_providers' : 'pos_yepay_providers');
        $configured = json_decode((string) Setting::get($settingKey, ''), true);
        $names = is_array($configured) && $configured !== [] ? $configured : $defaults;

        return collect($names)->map(function ($item) use ($method) {
            $name = is_array($item) ? (string) ($item['name'] ?? '') : (string) $item;

            return [
                'name' => $name,
                'icon' => match ($method) {
                    'bank' => 'building-library',
                    'mobile_money' => 'device-phone-mobile',
                    'yepay' => 'sparkles',
                    default => 'credit-card',
                },
            ];
        })->filter(fn ($item) => $item['name'] !== '')->values()->all();
    }

    public function render()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->when($this->search, fn ($query) => $query->where(fn ($sub) => $sub->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")->orWhere('barcode', 'like', "%{$this->search}%")))
            ->when($this->category !== 'all', fn ($query) => $query->where('category', $this->category))
            ->orderBy('name')
            ->get();

        return view('livewire.pos', [
            'products' => $products,
            'categories' => Product::query()->where('is_active', true)->whereNotNull('category')->select('category')->selectRaw('count(*) as total')->groupBy('category')->orderBy('category')->get(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']),
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->orderBy('id')->get(),
            'register' => $this->registerSession(),
            'tableZones' => $this->tableZones(),
            'productTotal' => Product::query()->where('is_active', true)->count(),
            'selectedCustomer' => $this->customerId ? Customer::find($this->customerId) : null,
            'loyaltyEnabled' => LoyaltyProgram::enabled(),
            'loyaltyMode' => LoyaltyProgram::mode(),
            'loyaltyPointValueUsd' => LoyaltyProgram::pointValueUsd(),
            'loyaltyRequiredServices' => LoyaltyProgram::requiredServices(),
            'loyaltyRewardProduct' => LoyaltyProgram::rewardProductId() ? Product::find(LoyaltyProgram::rewardProductId()) : null,
            'lastSale' => $this->lastSaleId ? Sale::query()
                ->with(['items.product', 'payments.paymentMethod', 'customer', 'user'])
                ->find($this->lastSaleId) : null,
            'receiptPreferences' => DocumentPreferences::receipt(),
            'receiptStyle' => DocumentPreferences::inlineStyle(DocumentPreferences::receipt()),
            'company' => [
                'name' => Setting::get('company_name', 'Yeboox'),
                'address' => Setting::get('company_address', ''),
                'phone' => Setting::get('company_phone', ''),
                'rccm' => Setting::get('company_rccm', ''),
                'nif' => Setting::get('company_nif', ''),
                'tva' => Setting::get('company_tva', ''),
                'footer' => Setting::get('receipt_footer', 'Merci de votre visite ! À bientôt.'),
            ],
        ]);
    }

    private function registerSession(): ?RegisterSession
    {
        if (! $this->registerSessionResolved) {
            $this->registerSessionCache = RegisterSession::query()
                ->where('user_id', Auth::id())
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();
            $this->registerSessionResolved = true;
        }

        return $this->registerSessionCache;
    }

    private function resetRegisterQueryCache(): void
    {
        $this->registerSessionResolved = false;
        $this->registerSessionCache = null;
        $this->sessionCashSalesCache = [];
        $this->sessionNonCashSalesCache = null;
    }

    private function normalizeScannedCode(string $value): string
    {
        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parts = parse_url($value);
            parse_str((string) ($parts['query'] ?? ''), $query);
            $value = (string) ($query['barcode'] ?? $query['code'] ?? $query['sku'] ?? basename((string) ($parts['path'] ?? '')));
        }
        $value = preg_replace('/^(?:YEBOOX:)?PRODUCT:/i', '', $value) ?? $value;

        return mb_strtoupper(trim($value));
    }

    private function cartSessionKey(): string
    {
        return 'pos.cart.'.Auth::id();
    }

    private function persistCart(): void
    {
        session()->put($this->cartSessionKey(), $this->cart);
        $this->saveCurrentOrderState();
    }

    private function ordersSessionKey(): string
    {
        return 'pos.orders.'.Auth::id();
    }

    private function currentOrderSessionKey(): string
    {
        return 'pos.current_order.'.Auth::id();
    }

    private function saveCurrentOrderState(): void
    {
        $this->tableOrders[$this->currentOrderKey] = [
            'cart' => $this->cart,
            'customerId' => $this->customerId,
            'discountAmount' => $this->discountAmount,
            'useLoyaltyReward' => $this->useLoyaltyReward,
            'fulfillmentType' => $this->fulfillmentType,
            'tableName' => $this->tableName,
            'deliveryPhone' => $this->deliveryPhone,
            'deliveryAddress' => $this->deliveryAddress,
        ];
        $this->persistOrderContext();
    }

    private function loadOrderState(array $state, bool $keepContext = true): void
    {
        $this->cart = $state['cart'] ?? [];
        $this->customerId = $state['customerId'] ?? null;
        $this->discountAmount = (float) ($state['discountAmount'] ?? 0);
        $this->useLoyaltyReward = (bool) ($state['useLoyaltyReward'] ?? false);
        $this->deliveryPhone = $state['deliveryPhone'] ?? null;
        $this->deliveryAddress = $state['deliveryAddress'] ?? null;
        if ($keepContext) {
            $this->fulfillmentType = $state['fulfillmentType'] ?? 'dine_in';
            $this->tableName = $state['tableName'] ?? null;
        }
    }

    private function persistOrderContext(): void
    {
        session()->put($this->ordersSessionKey(), $this->tableOrders);
        session()->put($this->currentOrderSessionKey(), $this->currentOrderKey);
    }

    private function tableZones(): array
    {
        $configured = json_decode((string) Setting::get('restaurant_tables', ''), true);
        if (! is_array($configured) || $configured === []) {
            $configured = [['zone' => 'Salle principale', 'tables' => '1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12']];
        }

        return collect($configured)->map(function ($zone, $zoneIndex) {
            $zoneName = trim((string) ($zone['zone'] ?? 'Zone '.($zoneIndex + 1)));
            $tables = collect(preg_split('/[,;\n]+/', (string) ($zone['tables'] ?? '')))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->map(fn ($name) => [
                    'name' => str_starts_with(mb_strtolower($name), 'table') ? $name : 'Table '.$name,
                    'capacity' => 4,
                ])->values()->all();

            return ['name' => $zoneName ?: 'Zone '.($zoneIndex + 1), 'tables' => $tables];
        })->filter(fn ($zone) => $zone['tables'] !== [])->values()->all();
    }

    private function syncElectron(string $reason): void
    {
        $register = $this->registerSession();
        $this->dispatch('pos-electron-sync', state: [
            'version' => 1,
            'reason' => $reason,
            'register' => [
                'id' => $register?->id,
                'status' => $register?->status ?? 'closed',
                'openedAt' => $register?->opened_at?->toIso8601String(),
                'openingBalance' => (float) ($register?->opening_balance ?? 0),
                'expectedBalance' => $register ? $this->expectedClosingBalance() : 0,
                'baseCurrency' => DisplayCurrency::baseCode(),
                'secondaryCurrency' => DisplayCurrency::secondaryCode(),
            ],
            'exchangeRate' => $this->sessionExchangeRate($register),
            'userId' => Auth::id(),
            'currency' => $this->currency,
            'category' => $this->category,
            'fulfillmentType' => $this->fulfillmentType,
            'tableName' => $this->tableName,
            'pendingOrders' => $this->pendingOrdersCount(),
            'updatedAt' => now()->toIso8601String(),
        ]);
    }

    private function postAccounting(Sale $sale, float $subtotal, float $tax, array $payments): void
    {
        $reference = 'POS-'.now()->format('Ymd').'-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT);
        $common = [
            'reference' => $reference,
            'journal_code' => 'VE',
            'entry_type' => 'sale',
            'record_date' => today(),
            'description' => "Vente point de vente #{$sale->id}",
            'user_id' => Auth::id(),
            'sale_id' => $sale->id,
            'source_type' => $sale->getMorphClass(),
            'source_id' => $sale->id,
        ];

        foreach ($payments as $payment) {
            $method = PaymentMethod::query()->where('code', $payment['method'])->first();
            AccountingRecord::create([
                ...$common,
                'syscohada_code' => $method?->syscohada_code ?: match ($payment['method']) {
                    'cash' => '5711',
                    'bank' => '5211',
                    'mobile_money', 'yepay' => '5218',
                    'points' => '467',
                    default => '5711',
                },
                'debit' => $this->paymentBaseAmount($payment),
                'credit' => 0,
            ]);
        }
        AccountingRecord::create([...$common, 'syscohada_code' => '701', 'debit' => 0, 'credit' => max(0, $subtotal - $this->discountAmount)]);
        if ($tax > 0) {
            AccountingRecord::create([...$common, 'syscohada_code' => '4431', 'debit' => 0, 'credit' => $tax]);
        }
    }
}
