<?php

namespace App\Livewire\Stock;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockLevel;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Services\VoucherService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bons internes : achat, décaissement, sortie de matériel.
 * Circuit : brouillon → soumis → approuvé (direction) → exécuté (caisse / magasin).
 */
#[Title('Bons')]
class Vouchers extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url(as: 'type')]
    public string $type = '';

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'chantier')]
    public ?int $siteFilter = null;

    public string $search = '';

    // Formulaire
    public bool $showForm = false;

    public ?int $editing = null;

    public string $formType = 'sortie';

    public string $voucherDate = '';

    public string $purpose = '';

    public string $beneficiary = '';

    public ?int $employeeId = null;

    public ?int $contactId = null;

    public ?int $siteId = null;

    public ?int $warehouseId = null;

    public string $amount = '';

    public string $paymentMethod = 'cash';

    public string $notes = '';

    public array $lines = [];

    // Détail / rejet / exécution
    public ?int $viewing = null;

    public bool $showReject = false;

    public string $rejectReason = '';

    public ?int $executeAccountId = null;

    public function mount(): void
    {
        if (request()->boolean('nouveau')) {
            $this->create($this->type ?: 'sortie');
            $this->siteId = $this->siteFilter;
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['type', 'status', 'search', 'siteFilter'], true)) {
            $this->resetPage();
        }
    }

    // ── Formulaire ──────────────────────────────────────────────────────────
    public function create(string $type = 'sortie'): void
    {
        $this->authorizeModule('vouchers', 'manage');
        $this->reset(['editing', 'purpose', 'beneficiary', 'employeeId', 'contactId', 'siteId', 'amount', 'notes']);
        $this->formType = array_key_exists($type, config('erp.voucher_types')) ? $type : 'sortie';
        $this->voucherDate = now()->toDateString();
        $this->warehouseId = Warehouse::where('company_id', company_id())->where('type', 'central')->value('id');
        $this->paymentMethod = 'cash';
        $this->lines = [$this->blankLine()];
        $this->showForm = true;
    }

    private function blankLine(): array
    {
        return ['product_id' => null, 'description' => '', 'quantity' => 1, 'unit' => 'unité', 'unit_price' => 0];
    }

    public function edit(int $id): void
    {
        $this->authorizeModule('vouchers', 'manage');
        $v = Voucher::with('lines')->where('company_id', company_id())->findOrFail($id);
        $this->editing = $v->id;
        $this->formType = $v->type;
        $this->fill([
            'voucherDate' => $v->voucher_date->toDateString(), 'purpose' => $v->purpose, 'beneficiary' => $v->beneficiary ?? '', 'employeeId' => $v->employee_id,
            'contactId' => $v->contact_id, 'siteId' => $v->site_id, 'warehouseId' => $v->warehouse_id, 'amount' => (string) (float) $v->amount,
            'paymentMethod' => $v->payment_method ?? 'cash', 'notes' => $v->notes ?? '',
        ]);
        $this->lines = $v->lines->map(fn ($l) => ['product_id' => $l->product_id, 'description' => $l->description, 'quantity' => (float) $l->quantity, 'unit' => $l->unit, 'unit_price' => (float) $l->unit_price])->all() ?: [$this->blankLine()];
        $this->viewing = null;
        $this->showForm = true;
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines) ?: [$this->blankLine()];
    }

    public function updatedLines($value, string $key): void
    {
        [$i, $field] = explode('.', $key) + [null, null];
        if ($field === 'product_id' && $value && ($product = Product::where('company_id', company_id())->find($value))) {
            $this->lines[$i] = array_merge($this->lines[$i], ['description' => $product->name, 'unit' => $product->unit, 'unit_price' => (float) $product->purchase_price]);
        }
    }

    public function save(VoucherService $service, bool $submit = false): void
    {
        $this->authorizeModule('vouchers', 'manage');
        $rules = [
            'formType' => ['required', 'in:achat,decaissement,sortie'],
            'voucherDate' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
        if ($this->formType === 'sortie') {
            $rules['warehouseId'] = ['required', 'integer'];
            $rules['lines.*.product_id'] = ['required', 'integer'];
        }
        if ($this->formType === 'achat') {
            $rules['contactId'] = ['required', 'integer'];
        }
        $this->validate($rules, [], ['purpose' => 'objet', 'warehouseId' => 'magasin', 'contactId' => 'fournisseur', 'lines.*.product_id' => 'article', 'lines.*.description' => 'désignation', 'lines.*.quantity' => 'quantité']);

        $data = [
            'voucher_date' => $this->voucherDate, 'purpose' => $this->purpose, 'beneficiary' => $this->beneficiary ?: null,
            'employee_id' => $this->employeeId ?: null, 'contact_id' => $this->contactId ?: null, 'site_id' => $this->siteId ?: null,
            'warehouse_id' => $this->warehouseId ?: null, 'payment_method' => $this->formType === 'decaissement' ? $this->paymentMethod : null,
            'notes' => $this->notes ?: null,
        ];
        $lines = array_map(fn ($l) => ['product_id' => $l['product_id'] ?: null, 'description' => $l['description'], 'quantity' => (float) $l['quantity'], 'unit' => $l['unit'] ?: 'unité', 'unit_price' => (float) ($l['unit_price'] ?: 0)], $this->lines);

        $voucher = $this->attempt(fn () => $this->editing
            ? tap($service->update(Voucher::where('company_id', company_id())->findOrFail($this->editing), $data, $lines), fn ($v) => $submit ? $service->submit($v) : null)
            : $service->create($this->formType, $data, $lines, $submit));

        if ($voucher) {
            $this->showForm = false;
            $this->toast($submit ? "Bon {$voucher->number} soumis pour approbation." : "Bon {$voucher->number} enregistré en brouillon.");
        }
    }

    public function saveAndSubmit(VoucherService $service): void
    {
        $this->save($service, true);
    }

    // ── Circuit d'approbation ──────────────────────────────────────────────
    public function show(int $id): void
    {
        $this->viewing = $id;
        $voucher = $this->current();
        $this->executeAccountId = $voucher->financial_account_id ?? FinancialAccount::where('company_id', company_id())->where('type', 'cash')->value('id');
    }

    private function current(): Voucher
    {
        return Voucher::with('lines.product', 'site', 'warehouse', 'employee', 'contact', 'requester', 'approver', 'executor', 'financialAccount', 'manufacturingOrder')->where('company_id', company_id())->findOrFail($this->viewing);
    }

    public function submit(VoucherService $service): void
    {
        $this->authorizeModule('vouchers', 'manage');
        $this->attempt(fn () => $service->submit($this->current()), 'Bon soumis pour approbation.');
    }

    public function approve(VoucherService $service): void
    {
        abort_unless($this->allowed('vouchers.approve'), 403);
        $this->attempt(fn () => $service->approve($this->current()), 'Bon approuvé.');
    }

    public function reject(VoucherService $service): void
    {
        abort_unless($this->allowed('vouchers.approve'), 403);
        $this->validate(['rejectReason' => ['required', 'string', 'min:4']], [], ['rejectReason' => 'motif']);
        if ($this->attempt(fn () => $service->reject($this->current(), $this->rejectReason), 'Bon rejeté.')) {
            $this->showReject = false;
            $this->rejectReason = '';
        }
    }

    public function execute(VoucherService $service): void
    {
        abort_unless($this->allowed('vouchers.execute'), 403);
        $voucher = $this->current();
        $message = match ($voucher->type) {
            'sortie' => 'Sortie de stock effectuée.', 'decaissement' => 'Décaissement effectué.', default => 'Bon de commande fournisseur généré.'
        };
        $this->attempt(fn () => $service->execute($voucher, $this->executeAccountId), $message);
    }

    public function cancel(VoucherService $service): void
    {
        $this->authorizeModule('vouchers', 'manage');
        $this->attempt(fn () => $service->cancel($this->current()), 'Bon annulé.');
    }

    public function render()
    {
        $cid = company_id();
        $base = Voucher::where('company_id', $cid);
        $viewing = $this->viewing ? $this->current() : null;

        return view('livewire.stock.vouchers', [
            'vouchers' => (clone $base)->with('site', 'requester', 'employee', 'contact')
                ->when($this->type, fn ($q) => $q->where('type', $this->type))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->siteFilter, fn ($q) => $q->where('site_id', $this->siteFilter))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")->orWhere('purpose', 'like', "%{$this->search}%")->orWhere('beneficiary', 'like', "%{$this->search}%")))
                ->latest('voucher_date')->latest('id')->paginate(15),
            'counts' => (clone $base)->selectRaw('status, count(*) as c, sum(amount) as t')->groupBy('status')->get()->keyBy('status'),
            'viewingVoucher' => $viewing,
            'stock' => $viewing && $viewing->type === 'sortie' && $viewing->warehouse_id
                ? StockLevel::where('warehouse_id', $viewing->warehouse_id)->whereIn('product_id', $viewing->lines->pluck('product_id')->filter())->pluck('quantity', 'product_id') : collect(),
            'products' => Product::where('company_id', $cid)->where('is_active', true)->where('type', '!=', 'service')->orderBy('name')->get(['id', 'sku', 'name', 'unit', 'type']),
            'sites' => Site::where('company_id', $cid)->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::where('company_id', $cid)->where('is_active', true)->get(),
            'employees' => Employee::where('company_id', $cid)->where('status', 'active')->orderBy('last_name')->get(),
            'suppliers' => Contact::where('company_id', $cid)->where('type', 'supplier')->orderBy('company_name')->get(),
            'accounts' => FinancialAccount::where('company_id', $cid)->where('is_active', true)->get(),
        ]);
    }
}
