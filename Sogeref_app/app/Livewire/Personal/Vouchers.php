<?php

namespace App\Livewire\Personal;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Personal\Concerns\ResolvesEmployee;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Site;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Services\VoucherService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * « Mes bons » : l'employé émet un bon (décaissement, achat, sortie matériel)
 * et le soumet à signature. Même circuit et numérotation que le module Bons :
 * brouillon → soumis → approuvé → exécuté.
 */
#[Title('Mes bons')]
class Vouchers extends Component
{
    use InteractsWithUi, ResolvesEmployee, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public bool $showForm = false;

    public ?int $editing = null;

    public string $formType = 'decaissement';

    public string $voucherDate = '';

    public string $purpose = '';

    public string $beneficiary = '';

    public ?int $contactId = null;

    public ?int $siteId = null;

    public ?int $warehouseId = null;

    public string $paymentMethod = 'cash';

    public string $notes = '';

    public array $lines = [];

    public ?int $viewing = null;

    public function mount(): void
    {
        if (request()->boolean('nouveau')) {
            $this->create();
        }
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function create(string $type = 'decaissement'): void
    {
        $this->reset(['editing', 'purpose', 'contactId', 'siteId', 'notes', 'viewing']);
        $this->resetValidation();
        $this->formType = array_key_exists($type, config('erp.voucher_types')) ? $type : 'decaissement';
        $this->voucherDate = now()->toDateString();
        $this->beneficiary = $this->employee()?->full_name ?? auth()->user()->name;
        $this->warehouseId = Warehouse::where('company_id', company_id())->where('type', 'central')->value('id');
        $this->paymentMethod = 'cash';
        $this->lines = [$this->blankLine()];
        $this->showForm = true;
    }

    public function updatedFormType(string $type): void
    {
        if (! array_key_exists($type, config('erp.voucher_types'))) {
            $this->formType = 'decaissement';
        }
        $this->resetValidation();
    }

    private function blankLine(): array
    {
        return ['product_id' => null, 'description' => '', 'quantity' => 1, 'unit' => 'unité', 'unit_price' => 0];
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

    public function edit(int $id): void
    {
        $v = $this->mine($id)->load('lines');
        abort_unless(in_array($v->status, ['draft', 'rejected'], true), 403);
        $this->resetValidation();
        $this->editing = $v->id;
        $this->formType = $v->type;
        $this->fill([
            'voucherDate' => $v->voucher_date->toDateString(), 'purpose' => $v->purpose, 'beneficiary' => $v->beneficiary ?? '',
            'contactId' => $v->contact_id, 'siteId' => $v->site_id, 'warehouseId' => $v->warehouse_id,
            'paymentMethod' => $v->payment_method ?? 'cash', 'notes' => $v->notes ?? '',
        ]);
        $this->lines = $v->lines->map(fn ($l) => ['product_id' => $l->product_id, 'description' => $l->description, 'quantity' => (float) $l->quantity, 'unit' => $l->unit, 'unit_price' => (float) $l->unit_price])->all() ?: [$this->blankLine()];
        $this->viewing = null;
        $this->showForm = true;
    }

    public function save(VoucherService $service, bool $submit = false): void
    {
        $rules = [
            'formType' => ['required', 'in:'.implode(',', array_keys(config('erp.voucher_types')))],
            'voucherDate' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:255'],
            'beneficiary' => ['nullable', 'string', 'max:255'],
            'siteId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
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

        $cid = company_id();
        // Les références saisies doivent appartenir à la société courante.
        $siteId = $this->siteId && Site::where('company_id', $cid)->whereKey($this->siteId)->exists() ? $this->siteId : null;
        $warehouseId = $this->warehouseId && Warehouse::where('company_id', $cid)->whereKey($this->warehouseId)->exists() ? $this->warehouseId : null;
        $contactId = $this->formType === 'achat' && $this->contactId && Contact::where('company_id', $cid)->whereKey($this->contactId)->exists() ? $this->contactId : null;
        $validProducts = Product::where('company_id', $cid)->whereIn('id', collect($this->lines)->pluck('product_id')->filter())->pluck('id')->all();

        $data = [
            'voucher_date' => $this->voucherDate, 'purpose' => $this->purpose, 'beneficiary' => $this->beneficiary ?: null,
            'employee_id' => $this->employee()?->id, 'contact_id' => $contactId, 'site_id' => $siteId,
            'warehouse_id' => in_array($this->formType, ['sortie', 'achat'], true) ? $warehouseId : null,
            'payment_method' => $this->formType === 'decaissement' ? $this->paymentMethod : null,
            'notes' => $this->notes ?: null,
        ];
        $lines = array_map(fn ($l) => [
            'product_id' => in_array((int) ($l['product_id'] ?? 0), $validProducts, true) && $this->formType !== 'decaissement' ? (int) $l['product_id'] : null,
            'description' => $l['description'], 'quantity' => (float) $l['quantity'], 'unit' => $l['unit'] ?: 'unité', 'unit_price' => (float) ($l['unit_price'] ?: 0),
        ], $this->lines);

        $voucher = $this->attempt(fn () => $this->editing
            ? tap($service->update($this->mine($this->editing), $data, $lines), fn ($v) => $submit ? $service->submit($v) : null)
            : $service->create($this->formType, $data, $lines, $submit));

        if ($voucher) {
            $this->showForm = false;
            $this->toast($submit ? "Bon {$voucher->number} soumis pour signature." : "Bon {$voucher->number} enregistré en brouillon.");
        }
    }

    public function saveAndSubmit(VoucherService $service): void
    {
        $this->save($service, true);
    }

    public function show(int $id): void
    {
        $this->viewing = $this->mine($id)->id;
    }

    public function submit(int $id): void
    {
        $this->attempt(fn () => app(VoucherService::class)->submit($this->mine($id)), 'Bon soumis pour signature.');
    }

    public function cancel(int $id): void
    {
        $this->attempt(fn () => app(VoucherService::class)->cancel($this->mine($id)), 'Bon annulé.');
    }

    /** Bons émis par l'utilisateur connecté (404 sinon). */
    private function mine(int $id): Voucher
    {
        return Voucher::where('company_id', company_id())->where('requested_by', auth()->id())->findOrFail($id);
    }

    public function render()
    {
        $cid = company_id();
        $base = Voucher::where('company_id', $cid)->where('requested_by', auth()->id());

        return view('livewire.personal.vouchers', [
            'vouchers' => (clone $base)->with('site')->when($this->status, fn ($q) => $q->where('status', $this->status))->latest('voucher_date')->latest('id')->paginate(12),
            'counts' => (clone $base)->selectRaw('status, count(*) as c, sum(amount) as t')->groupBy('status')->get()->keyBy('status'),
            'viewingVoucher' => $this->viewing ? (clone $base)->with('lines.product', 'site', 'warehouse', 'contact', 'requester', 'approver', 'executor')->find($this->viewing) : null,
            'employee' => $this->employee(),
            'sites' => $this->showForm ? Site::where('company_id', $cid)->whereIn('status', ['planned', 'in_progress'])->orderBy('name')->get(['id', 'code', 'name']) : collect(),
            'warehouses' => $this->showForm ? Warehouse::where('company_id', $cid)->where('is_active', true)->get(['id', 'name']) : collect(),
            'suppliers' => $this->showForm && $this->formType === 'achat' ? Contact::where('company_id', $cid)->where('type', 'supplier')->orderBy('company_name')->get(['id', 'company_name']) : collect(),
            'products' => $this->showForm && $this->formType !== 'decaissement' ? Product::where('company_id', $cid)->where('is_active', true)->where('type', '!=', 'service')->orderBy('name')->get(['id', 'sku', 'name']) : collect(),
        ]);
    }
}
