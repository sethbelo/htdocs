<?php

namespace App\Livewire\Inventory;

use App\Enums\ProductType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Articles')]
class Products extends Component
{
    use InteractsWithToasts, WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $type = '';

    public ?int $categoryId = null;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public $image = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('products.create');
        $this->editingId = null;
        $this->image = null;
        $this->form = ['sku' => '', 'barcode' => '', 'rfid_tag' => '', 'name' => '', 'description' => '', 'type' => 'merchandise', 'product_category_id' => '', 'unit_id' => '', 'supplier_id' => '', 'cost_price' => '0', 'sale_price' => '0', 'tax_rate' => '16', 'min_stock' => '0', 'max_stock' => '', 'is_stockable' => true, 'is_sellable_pos' => true, 'is_favorite' => false, 'is_active' => true];
        $this->modal('product')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('products.update');
        $product = Product::query()->findOrFail($id);
        $this->editingId = $id;
        $this->image = null;
        $this->form = $product->only(['sku', 'barcode', 'rfid_tag', 'name', 'description', 'product_category_id', 'unit_id', 'supplier_id', 'cost_price', 'sale_price', 'tax_rate', 'min_stock', 'max_stock', 'is_stockable', 'is_sellable_pos', 'is_favorite', 'is_active']) + ['type' => $product->type->value];
        $this->modal('product')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'products.update' : 'products.create');

        $data = $this->validate([
            'form.sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->editingId)],
            'form.barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($this->editingId)],
            'form.rfid_tag' => ['nullable', 'string', 'max:64', Rule::unique('products', 'rfid_tag')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
            'form.type' => ['required', Rule::enum(ProductType::class)],
            'form.product_category_id' => ['nullable', 'exists:product_categories,id'],
            'form.unit_id' => ['nullable', 'exists:units,id'],
            'form.supplier_id' => ['nullable', 'exists:suppliers,id'],
            'form.cost_price' => ['required', 'numeric', 'min:0'],
            'form.sale_price' => ['required', 'numeric', 'min:0'],
            'form.tax_rate' => ['required', 'numeric', 'between:0,100'],
            'form.min_stock' => ['required', 'numeric', 'min:0'],
            'form.max_stock' => ['nullable', 'numeric', 'min:0'],
            'form.is_stockable' => ['boolean'],
            'form.is_sellable_pos' => ['boolean'],
            'form.is_favorite' => ['boolean'],
            'form.is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ], [], ['form.sku' => 'SKU', 'form.name' => 'nom'])['form'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->image) {
            $data['image_path'] = $this->image->store('products', 'public');
        }

        Product::query()->updateOrCreate(['id' => $this->editingId], $data);
        $this->modal('product')->close();
        $this->toast('Article enregistré.');
    }

    public function render()
    {
        return view('livewire.inventory.products', [
            'products' => Product::query()->with('category', 'unit')->withSum('stockLevels as stock_total', 'quantity')
                ->search($this->search ?: null)
                ->when($this->type, fn ($q) => $q->where('type', $this->type))
                ->when($this->categoryId, fn ($q) => $q->where('product_category_id', $this->categoryId))
                ->orderBy('name')->paginate(25),
            'types' => ProductType::options(),
            'categories' => ProductCategory::query()->orderBy('position')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
