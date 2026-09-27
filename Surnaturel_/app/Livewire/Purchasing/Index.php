<?php

namespace App\Livewire\Purchasing;

use App\Enums\PaymentMethod;
use App\Enums\PurchaseRequestStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\TreasuryAccount;
use App\Models\Warehouse;
use App\Services\Approvals\ApprovalService;
use App\Services\Purchasing\PurchaseService;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Achats')]
class Index extends Component
{
    use InteractsWithToasts;

    #[Url(as: 'vue')]
    public string $tab = 'requests';

    /** @var array<string, mixed> */
    public array $request = ['needed_by' => '', 'justification' => ''];

    /** @var list<array<string, string>> */
    public array $requestItems = [];

    /** @var array<string, mixed> */
    public array $po = ['supplier_id' => '', 'warehouse_id' => '', 'purchase_request_id' => '', 'expected_on' => ''];

    /** @var list<array<string, string>> */
    public array $poItems = [];

    public ?int $receivingId = null;

    /** @var array<int, array{accepted: string, rejected: string, reason: string}> */
    public array $reception = [];

    /** @var array<string, string> */
    public array $invoice = ['purchase_order_id' => '', 'supplier_reference' => '', 'total' => '', 'tax_total' => '0', 'invoice_date' => '', 'due_date' => ''];

    /** @var array<string, string> */
    public array $payment = ['supplier_invoice_id' => '', 'treasury_account_id' => '', 'method' => 'bank_transfer', 'amount' => '', 'reference' => ''];

    public function newRequest(): void
    {
        $this->authorize('purchases.create');
        $this->request = ['needed_by' => now()->addWeek()->toDateString(), 'justification' => ''];
        $this->requestItems = [['product_id' => '', 'description' => '', 'quantity' => '1', 'estimated_unit_price' => '0']];
        $this->modal('request')->show();
    }

    public function addRequestItem(): void
    {
        $this->requestItems[] = ['product_id' => '', 'description' => '', 'quantity' => '1', 'estimated_unit_price' => '0'];
    }

    public function saveRequest(): void
    {
        $this->persistRequest(true);
    }

    public function saveDraftRequest(): void
    {
        $this->persistRequest(false);
    }

    private function persistRequest(bool $submit): void
    {
        $numbers = app(NumberGenerator::class);
        $approvals = app(ApprovalService::class);
        $this->authorize('purchases.create');
        $data = $this->validate([
            'request.needed_by' => ['nullable', 'date'],
            'request.justification' => ['required', 'string', 'max:2000'],
            'requestItems' => ['required', 'array', 'min:1'],
            'requestItems.*.product_id' => ['nullable', 'exists:products,id'],
            'requestItems.*.description' => ['required', 'string', 'max:255'],
            'requestItems.*.quantity' => ['required', 'numeric', 'gt:0'],
            'requestItems.*.estimated_unit_price' => ['required', 'numeric', 'min:0'],
        ], [], ['request.justification' => 'justification']);

        $request = DB::transaction(function () use ($data, $numbers): PurchaseRequest {
            $request = PurchaseRequest::query()->create([
                'number' => $numbers->next(NumberGenerator::PurchaseRequest),
                'site_id' => auth()->user()->default_site_id ?? auth()->user()->accessibleSiteIds()[0],
                'department_id' => auth()->user()->employee?->department_id,
                'requested_by' => auth()->id(),
                'status' => PurchaseRequestStatus::Draft,
                'needed_by' => $data['request']['needed_by'] ?: null,
                'justification' => $data['request']['justification'],
                'estimated_total' => collect($data['requestItems'])->sum(fn ($i) => (float) $i['quantity'] * (float) $i['estimated_unit_price']),
            ]);

            foreach ($data['requestItems'] as $item) {
                $request->items()->create(array_map(fn ($v) => $v === '' ? null : $v, $item));
            }

            return $request;
        });

        if ($submit) {
            $this->attempt(function () use ($approvals, $request) {
                $request->update(['status' => PurchaseRequestStatus::PendingApproval]);

                return $approvals->submit($request, auth()->user());
            });
        }

        $this->modal('request')->close();
        $this->toast("Demande d’achat {$request->number} ".($submit ? 'soumise à validation.' : 'enregistrée.'));
    }

    public function newOrder(?int $requestId = null): void
    {
        $this->authorize('purchases.create');
        $request = $requestId ? PurchaseRequest::query()->with('items.product')->findOrFail($requestId) : null;
        $this->po = ['supplier_id' => (string) ($request?->items->first()?->product?->supplier_id ?? ''), 'warehouse_id' => (string) Warehouse::query()->where('site_id', $request?->site_id ?? auth()->user()->default_site_id)->value('id'), 'purchase_request_id' => (string) $requestId, 'expected_on' => ''];
        $this->poItems = $request
            ? $request->items->map(fn ($item): array => ['product_id' => (string) $item->product_id, 'description' => $item->description, 'quantity' => (string) (float) $item->quantity, 'unit_price' => (string) (float) $item->estimated_unit_price, 'tax_rate' => '16'])->all()
            : [['product_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '0', 'tax_rate' => '16']];
        $this->modal('po')->show();
    }

    public function addPoItem(): void
    {
        $this->poItems[] = ['product_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '0', 'tax_rate' => '16'];
    }

    public function saveOrder(PurchaseService $purchases): void
    {
        $this->authorize('purchases.create');
        $data = $this->validate([
            'po.supplier_id' => ['required', 'exists:suppliers,id'],
            'po.warehouse_id' => ['required', 'exists:warehouses,id'],
            'po.purchase_request_id' => ['nullable', 'exists:purchase_requests,id'],
            'po.expected_on' => ['nullable', 'date'],
            'poItems' => ['required', 'array', 'min:1'],
            'poItems.*.product_id' => ['required', 'exists:products,id'],
            'poItems.*.description' => ['nullable', 'string', 'max:255'],
            'poItems.*.quantity' => ['required', 'numeric', 'gt:0'],
            'poItems.*.unit_price' => ['required', 'numeric', 'min:0'],
            'poItems.*.tax_rate' => ['required', 'numeric', 'between:0,100'],
        ], [], ['po.supplier_id' => 'fournisseur', 'poItems.*.product_id' => 'article']);

        $items = array_map(fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'description' => $item['description'] ?: Product::query()->whereKey($item['product_id'])->value('name'),
            'quantity' => (float) $item['quantity'], 'unit_price' => (float) $item['unit_price'], 'tax_rate' => (float) $item['tax_rate'],
        ], $data['poItems']);

        $order = $this->attempt(fn () => $purchases->createOrder(
            Supplier::query()->findOrFail($data['po']['supplier_id']),
            Warehouse::query()->findOrFail($data['po']['warehouse_id']),
            $items, auth()->user(),
            $data['po']['purchase_request_id'] ? PurchaseRequest::query()->find($data['po']['purchase_request_id']) : null,
            $data['po']['expected_on'] ?: null,
        ));

        if ($order) {
            $this->modal('po')->close();
            $this->toast("Bon de commande {$order->number} émis.");
        }
    }

    public function openReception(int $orderId): void
    {
        $this->authorize('purchases.update');
        $order = PurchaseOrder::query()->with('items')->findOrFail($orderId);
        $this->receivingId = $orderId;
        $this->reception = $order->items->mapWithKeys(fn ($item): array => [$item->id => ['accepted' => (string) $item->remainingQuantity(), 'rejected' => '0', 'reason' => '']])->all();
        $this->modal('reception')->show();
    }

    public function receive(PurchaseService $purchases): void
    {
        $this->authorize('purchases.update');
        $this->validate(['reception.*.accepted' => ['required', 'numeric', 'min:0'], 'reception.*.rejected' => ['required', 'numeric', 'min:0'], 'reception.*.reason' => ['nullable', 'string', 'max:255']]);

        $receipt = $this->attempt(fn () => $purchases->receive(PurchaseOrder::query()->findOrFail($this->receivingId), $this->reception, auth()->user()));

        if ($receipt) {
            $this->modal('reception')->close();
            $this->toast("Bon de réception {$receipt->number} : stock mis à jour.");
        }
    }

    public function openInvoice(int $orderId): void
    {
        $this->authorize('purchases.validate');
        $order = PurchaseOrder::query()->findOrFail($orderId);
        $this->invoice = ['purchase_order_id' => (string) $orderId, 'supplier_reference' => '', 'total' => (string) (float) $order->total, 'tax_total' => (string) (float) $order->tax_total, 'invoice_date' => today()->toDateString(), 'due_date' => ''];
        $this->modal('invoice')->show();
    }

    public function saveInvoice(PurchaseService $purchases): void
    {
        $this->authorize('purchases.validate');
        $data = $this->validate([
            'invoice.purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'invoice.supplier_reference' => ['required', 'string', 'max:100'],
            'invoice.total' => ['required', 'numeric', 'gt:0'],
            'invoice.tax_total' => ['required', 'numeric', 'min:0'],
            'invoice.invoice_date' => ['required', 'date'],
            'invoice.due_date' => ['nullable', 'date'],
        ], [], ['invoice.supplier_reference' => 'référence fournisseur'])['invoice'];

        $this->attempt(fn () => $purchases->recordInvoice(PurchaseOrder::query()->findOrFail($data['purchase_order_id']), $data['supplier_reference'], (float) $data['total'], (float) $data['tax_total'], $data['invoice_date'], $data['due_date'] ?: null), 'Facture fournisseur comptabilisée.');
        $this->modal('invoice')->close();
    }

    public function openPayment(int $invoiceId): void
    {
        $this->authorize('treasury.disburse');
        $invoice = SupplierInvoice::query()->findOrFail($invoiceId);
        $this->payment = ['supplier_invoice_id' => (string) $invoiceId, 'treasury_account_id' => '', 'method' => 'bank_transfer', 'amount' => (string) $invoice->balanceDue(), 'reference' => ''];
        $this->modal('payment')->show();
    }

    public function pay(PurchaseService $purchases): void
    {
        $this->authorize('treasury.disburse');
        $data = $this->validate([
            'payment.supplier_invoice_id' => ['required', 'exists:supplier_invoices,id'],
            'payment.treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
            'payment.method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment.amount' => ['required', 'numeric', 'gt:0'],
            'payment.reference' => ['nullable', 'string', 'max:100'],
        ], [], ['payment.treasury_account_id' => 'compte de trésorerie'])['payment'];

        if ($this->attempt(fn () => $purchases->pay(SupplierInvoice::query()->findOrFail($data['supplier_invoice_id']), TreasuryAccount::query()->findOrFail($data['treasury_account_id']), PaymentMethod::from($data['method']), (float) $data['amount'], auth()->user(), $data['reference'] ?: null), 'Règlement fournisseur enregistré.')) {
            $this->modal('payment')->close();
        }
    }

    public function render()
    {
        $sites = auth()->user()->accessibleSiteIds();

        return view('livewire.purchasing.index', [
            'requests' => PurchaseRequest::query()->with('requester', 'approvalRequest.currentStep', 'items')->whereIn('site_id', $sites)->latest()->limit(50)->get(),
            'orders' => PurchaseOrder::query()->with('supplier', 'warehouse', 'receipts', 'supplierInvoices')->whereIn('site_id', $sites)->latest()->limit(50)->get(),
            'invoices' => SupplierInvoice::query()->with('supplier', 'purchaseOrder')->whereIn('site_id', $sites)->latest('invoice_date')->limit(50)->get(),
            'products' => Product::query()->where('is_active', true)->where('type', '!=', 'service')->orderBy('name')->get(['id', 'name', 'cost_price']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->visibleTo(auth()->user())->get(['id', 'name']),
            'treasuryAccounts' => TreasuryAccount::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('site_id')->orWhereIn('site_id', $sites))->get(),
            'methods' => PaymentMethod::options(),
            'receivingOrder' => $this->receivingId ? PurchaseOrder::query()->with('items')->find($this->receivingId) : null,
        ]);
    }
}
