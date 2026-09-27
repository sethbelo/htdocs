<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public bool $editing = false;

    public string $name = '';

    public string $sku = '';

    public string $barcode = '';

    public string $description = '';

    public string $category_id = '';

    public string $tax_id = '';

    public string $unit_id = '';

    public string $product_type = 'simple';

    public string $cost_price = '0';

    public string $selling_price = '0';

    public string $promo_price = '';

    public string $alert_quantity = '5';

    public bool $is_active = true;

    public bool $track_stock = true;

    public bool $is_pos_visible = true;

    public int $sort_order = 0;

    public string $product_icon = 'cube';

    public $image;

    public ?string $existing_image_url = null;

    public array $variants = [];

    public array $components = [];

    public array $availableIcons = [
        'cube' => 'Produit', 'shopping-bag' => 'Article', 'cake' => 'Aliment',
        'beaker' => 'Boisson', 'sparkles' => 'Beauté',
        'wrench-screwdriver' => 'Service', 'scissors' => 'Salon', 'heart' => 'Soin',
    ];

    protected function rules(): array
    {
        $establishmentId = auth()->user()->establishment_id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->where('establishment_id', $establishmentId)->ignore($this->product?->id)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->where('establishment_id', $establishmentId)->ignore($this->product?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('establishment_id', $establishmentId)],
            'tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('establishment_id', $establishmentId)],
            'unit_id' => ['nullable', Rule::exists('units', 'id')->where('establishment_id', $establishmentId)],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'promo_price' => ['nullable', 'numeric', 'min:0'],
            'alert_quantity' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'], 'track_stock' => ['boolean'], 'is_pos_visible' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'product_icon' => ['nullable', Rule::in(array_keys($this->availableIcons))],
            'image' => ['nullable', 'image', 'max:3072'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'uuid'],
            'variants.*.name' => ['required_if:product_type,variable', 'string', 'max:100'],
            'variants.*.sku' => ['nullable', 'string', 'max:50', 'distinct'],
            'variants.*.barcode' => ['nullable', 'string', 'max:50', 'distinct'],
            'variants.*.selling_price' => ['required_if:product_type,variable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['required_if:product_type,variable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'components' => ['array'],
            'components.*.product_id' => ['required_if:product_type,composite', 'distinct', Rule::exists('products', 'id')->where('establishment_id', $establishmentId)],
            'components.*.quantity' => ['required_if:product_type,composite', 'numeric', 'gt:0'],
            'components.*.unit_id' => ['nullable', Rule::exists('units', 'id')->where('establishment_id', $establishmentId)],
        ];

        if ($this->product_type === ProductType::Variable->value) {
            $rules['variants'][] = 'required';
            $rules['variants'][] = 'min:1';
        }

        if ($this->product_type === ProductType::Composite->value) {
            $rules['components'][] = 'required';
            $rules['components'][] = 'min:1';
        }

        return $rules;
    }

    protected $validationAttributes = [
        'name' => 'nom', 'sku' => 'SKU', 'barcode' => 'code-barres',
        'category_id' => 'catégorie', 'unit_id' => 'unité de mesure',
        'cost_price' => 'prix d’achat', 'selling_price' => 'prix de vente',
        'promo_price' => 'prix promotionnel', 'alert_quantity' => 'seuil d’alerte',
        'variants.*.name' => 'nom de la variante',
        'components.*.product_id' => 'produit composant',
        'components.*.quantity' => 'quantité du composant',
    ];

    public function mount(?string $productId = null): void
    {
        if (! $productId) {
            return;
        }

        $this->product = Product::with(['variants', 'components'])->findOrFail($productId);
        $this->editing = true;
        $this->fill([
            'name' => $this->product->name, 'sku' => $this->product->sku ?? '',
            'barcode' => $this->product->barcode ?? '', 'description' => $this->product->description ?? '',
            'category_id' => $this->product->category_id, 'tax_id' => $this->product->tax_id ?? '',
            'unit_id' => $this->product->unit_id ?? '',
            'product_type' => $this->product->type?->value ?? ProductType::Simple->value,
            'cost_price' => (string) $this->product->cost_price,
            'selling_price' => (string) $this->product->selling_price,
            'promo_price' => $this->product->promo_price ? (string) $this->product->promo_price : '',
            'alert_quantity' => (string) $this->product->alert_quantity,
            'is_active' => $this->product->is_active, 'track_stock' => $this->product->track_stock,
            'is_pos_visible' => $this->product->is_available, 'sort_order' => $this->product->sort_order,
            'product_icon' => $this->product->icon ?: ($this->product->type?->icon() ?? 'cube'),
            'existing_image_url' => $this->product->image_url,
        ]);
        $this->variants = $this->product->variants->map(fn ($variant) => [
            'id' => $variant->id, 'name' => $variant->name, 'sku' => $variant->sku ?? '',
            'barcode' => $variant->barcode ?? '', 'selling_price' => (string) $variant->selling_price,
            'cost_price' => (string) $variant->cost_price, 'stock_quantity' => (string) $variant->stock_quantity,
        ])->values()->all();
        $this->components = $this->product->components->map(fn ($component) => [
            'product_id' => $component->component_product_id, 'quantity' => (string) $component->quantity,
            'unit_id' => $component->unit_id ?? '',
        ])->values()->all();
    }

    public function updatedProductType(string $type): void
    {
        if ($type === ProductType::Service->value) {
            $this->track_stock = false;
            $this->alert_quantity = '0';
            $this->product_icon = 'wrench-screwdriver';
        }
        if ($type === ProductType::Variable->value && $this->variants === []) {
            $this->addVariant();
        }
        if ($type === ProductType::Composite->value && $this->components === []) {
            $this->addComponent();
        }
    }

    public function generateSku(): void
    {
        if ($this->sku !== '') {
            return;
        }
        $prefix = Str::upper(Str::substr(Str::slug($this->name ?: 'produit', ''), 0, 6)) ?: 'PRD';
        do {
            $candidate = $prefix.'-'.random_int(1000, 9999);
        } while (Product::where('sku', $candidate)->exists() || ProductVariant::where('sku', $candidate)->exists());
        $this->sku = $candidate;
    }

    public function generateBarcode(): void
    {
        if ($this->barcode !== '') {
            return;
        }
        do {
            $base = '2'.str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
            $candidate = $base.$this->ean13CheckDigit($base);
        } while (Product::where('barcode', $candidate)->exists() || ProductVariant::where('barcode', $candidate)->exists());
        $this->barcode = $candidate;
    }

    public function addVariant(): void
    {
        $this->variants[] = ['id' => null, 'name' => '', 'sku' => '', 'barcode' => '',
            'selling_price' => $this->selling_price, 'cost_price' => $this->cost_price, 'stock_quantity' => '0'];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function generateVariantSku(int $index): void
    {
        if (! isset($this->variants[$index]) || $this->variants[$index]['sku'] !== '') {
            return;
        }
        $parent = $this->sku ?: Str::upper(Str::substr(Str::slug($this->name ?: 'PRD', ''), 0, 6));
        $suffix = Str::upper(Str::substr(Str::slug($this->variants[$index]['name'] ?: 'VAR', ''), 0, 4));
        $this->variants[$index]['sku'] = trim($parent.'-'.$suffix.'-'.random_int(10, 99), '-');
    }

    public function generateVariantBarcode(int $index): void
    {
        if (! isset($this->variants[$index]) || $this->variants[$index]['barcode'] !== '') {
            return;
        }

        do {
            $base = '2'.str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
            $candidate = $base.$this->ean13CheckDigit($base);
        } while (Product::where('barcode', $candidate)->exists() || ProductVariant::where('barcode', $candidate)->exists());

        $this->variants[$index]['barcode'] = $candidate;
    }

    public function addComponent(): void
    {
        $this->components[] = ['product_id' => '', 'quantity' => '1', 'unit_id' => $this->unit_id];
    }

    public function removeComponent(int $index): void
    {
        unset($this->components[$index]);
        $this->components = array_values($this->components);
    }

    public function save(): void
    {
        if ($this->product_type === ProductType::Service->value) {
            $this->track_stock = false;
            $this->alert_quantity = '0';
        }
        $validated = $this->validate();

        if (! $this->mainReferencesAreUnique($validated) || ! $this->variantReferencesAreUnique($validated)) {
            return;
        }

        DB::transaction(function () use ($validated) {
            $imagePath = $this->product?->image_path;
            if ($this->image) {
                $newPath = $this->image->store('products', 'public');
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $newPath;
            }
            $data = [
                'name' => $validated['name'], 'sku' => $validated['sku'] ?: null,
                'barcode' => $validated['barcode'] ?: null, 'description' => $validated['description'] ?: null,
                'category_id' => $validated['category_id'], 'tax_id' => $validated['tax_id'] ?: null,
                'unit_id' => $validated['unit_id'] ?: null, 'type' => $validated['product_type'],
                'cost_price' => $validated['cost_price'], 'selling_price' => $validated['selling_price'],
                'promo_price' => $validated['promo_price'] ?: null, 'alert_quantity' => $validated['alert_quantity'],
                'is_active' => $validated['is_active'], 'is_available' => $validated['is_pos_visible'],
                'track_stock' => $validated['track_stock'], 'sort_order' => $validated['sort_order'],
                'image_path' => $imagePath, 'icon' => $validated['product_icon'] ?: null,
            ];
            if ($this->editing) {
                $this->product->update($data);
            } else {
                $data['establishment_id'] = auth()->user()->establishment_id;
                $data['created_by'] = auth()->id();
                $this->product = Product::create($data);
            }
            $this->syncVariants($validated);
            $this->syncComponents($validated);
        });

        session()->flash('success', $this->editing ? 'Produit modifié avec succès.' : 'Produit créé avec succès.');
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    private function syncVariants(array $validated): void
    {
        if ($validated['product_type'] !== ProductType::Variable->value) {
            $this->product->variants()->delete();

            return;
        }
        $keptIds = [];
        foreach ($validated['variants'] as $variant) {
            $saved = $this->product->variants()->updateOrCreate(['id' => $variant['id'] ?? null], [
                'name' => $variant['name'], 'sku' => $variant['sku'] ?: null,
                'barcode' => $variant['barcode'] ?: null, 'selling_price' => $variant['selling_price'],
                'cost_price' => $variant['cost_price'],
                'stock_quantity' => $this->track_stock ? ($variant['stock_quantity'] ?: 0) : 0, 'is_active' => true,
            ]);
            $keptIds[] = $saved->id;
        }
        $this->product->variants()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncComponents(array $validated): void
    {
        $this->product->components()->delete();
        if ($validated['product_type'] !== ProductType::Composite->value) {
            return;
        }
        foreach ($validated['components'] as $component) {
            $this->product->components()->create([
                'component_product_id' => $component['product_id'], 'quantity' => $component['quantity'],
                'unit_id' => $component['unit_id'] ?: null,
            ]);
        }
    }

    private function ean13CheckDigit(string $digits): int
    {
        $sum = 0;
        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }

    private function variantReferencesAreUnique(array $validated): bool
    {
        if ($validated['product_type'] !== ProductType::Variable->value) {
            return true;
        }

        $valid = true;
        foreach ($validated['variants'] as $index => $variant) {
            foreach (['sku' => 'SKU', 'barcode' => 'code-barres'] as $field => $label) {
                if (empty($variant[$field])) {
                    continue;
                }

                $usedByProduct = Product::where($field, $variant[$field])->exists();
                $usedByVariant = ProductVariant::where($field, $variant[$field])
                    ->when($variant['id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
                    ->exists();

                if ($usedByProduct || $usedByVariant) {
                    $this->addError("variants.{$index}.{$field}", "Ce {$label} est déjà utilisé.");
                    $valid = false;
                }
            }
        }

        return $valid;
    }

    private function mainReferencesAreUnique(array $validated): bool
    {
        $valid = true;
        foreach (['sku' => 'SKU', 'barcode' => 'code-barres'] as $field => $label) {
            if (! empty($validated[$field]) && ProductVariant::where($field, $validated[$field])->exists()) {
                $this->addError($field, "Ce {$label} est déjà utilisé par une variante.");
                $valid = false;
            }
        }

        return $valid;
    }

    public function render()
    {
        $categories = Category::active()->ordered()->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $componentProducts = Product::active()
            ->when($this->product, fn ($query) => $query->whereKeyNot($this->product->id))
            ->where('type', '!=', ProductType::Composite->value)->orderBy('name')->get();

        return view('livewire.admin.products.form', compact('categories', 'taxes', 'units', 'componentProducts'))
            ->layout('layouts.admin', ['title' => $this->editing ? 'Modifier le produit' : 'Nouveau produit',
                'breadcrumb' => $this->editing ? 'Modifier' : 'Nouveau']);
    }
}
