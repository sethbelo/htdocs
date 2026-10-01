<?php

namespace App\Livewire\Sales;

use App\Actions\Documents\CreateDocument;
use App\Actions\Documents\UpdateDocument;
use App\Enums\DocumentType;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Product;
use App\Services\OrderWorkflow;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Éditeur plein écran des devis, factures et bons de commande fournisseurs.
 */
class DocumentEditor extends Component
{
    use InteractsWithUi;

    public ?Document $document = null;

    public string $type = 'quote';

    public ?int $contact_id = null;

    public string $issue_date = '';

    public string $due_date = '';

    public string $currency_code = 'USD';

    public float $exchange_rate = 1;

    public string $template = 'moderne';

    /** Intitulé d'un devis : « devis » ou « proforma » (facture pro forma). */
    public string $heading = 'devis';

    public string $notes = '';

    public string $terms = '';

    public array $items = [];

    // Ouverture automatique du dossier de commande (devis)
    public bool $openOrder = true;

    public string $orderKind = 'fabrication';

    public string $orderTitle = '';

    public string $deliveryDate = '';

    public function mount(?Document $document = null, string $type = 'quote'): void
    {
        if ($document?->exists) {
            abort_unless($document->company_id === company_id(), 404);
            $this->document = $document;
            $this->type = $document->type->value;
            $this->authorizeModule($this->module(), 'manage');
            $this->fill([
                'contact_id' => $document->contact_id,
                'issue_date' => $document->issue_date->toDateString(),
                'due_date' => ($this->type === 'quote' ? $document->valid_until : $document->due_date)?->toDateString() ?? '',
                'currency_code' => $document->currency_code,
                'exchange_rate' => (float) $document->exchange_rate,
                'template' => $document->template ?: 'moderne',
                'notes' => $document->notes ?? '',
                'terms' => $document->terms ?? '',
                'openOrder' => false,
                'heading' => data_get($document->meta, 'heading') === 'proforma' ? 'proforma' : 'devis',
            ]);
            $this->items = $document->items->map(fn ($i) => [
                'product_id' => $i->product_id, 'description' => $i->description, 'quantity' => (float) $i->quantity, 'unit' => $i->unit,
                'unit_price' => (float) $i->unit_price, 'discount_rate' => (float) $i->discount_rate, 'tax_rate' => (float) $i->tax_rate,
            ])->all();

            return;
        }

        $this->type = $type;
        $this->authorizeModule($this->module(), 'manage');
        $this->issue_date = now()->toDateString();
        $this->due_date = now()->addDays($this->type === 'purchase_order' ? 15 : 30)->toDateString();
        $this->heading = $this->type === 'quote' && request()->query('intitule') === 'proforma' ? 'proforma' : 'devis';
        $this->template = $this->defaultTemplate();
        $this->terms = (string) erp_setting($this->type === 'quote' ? 'quote_terms' : ($this->type === 'invoice' ? 'invoice_terms' : ''), '');
        $this->contact_id = Contact::decodeRouteKey(request()->query('contact'));
        $this->items = [$this->blankLine()];
    }

    /** Modèle par défaut défini par le Master pour ce type (et cet intitulé). */
    private function defaultTemplate(?string $heading = null): string
    {
        $key = $this->type === 'quote' && ($heading ?? $this->heading) === 'proforma' ? 'proforma' : $this->type;

        return (string) erp_setting("templates.{$key}", ['purchase_order' => 'classique', 'proforma' => 'prestige'][$key] ?? 'moderne');
    }

    /** Changer d'intitulé applique le modèle par défaut correspondant, sauf choix manuel. */
    public function updatingHeading(string $value): void
    {
        abort_unless(in_array($value, ['devis', 'proforma'], true), 422);
        if ($this->template === $this->defaultTemplate($this->heading)) {
            $this->template = $this->defaultTemplate($value);
        }
    }

    private function module(): string
    {
        return match ($this->type) {
            'quote' => 'quotes', 'purchase_order' => 'purchases', default => 'invoices'
        };
    }

    private function blankLine(): array
    {
        return ['product_id' => null, 'description' => '', 'quantity' => 1, 'unit' => 'unité', 'unit_price' => 0, 'discount_rate' => 0, 'tax_rate' => $this->type === 'purchase_order' ? 0 : 16];
    }

    // ── Lignes ──────────────────────────────────────────────────────────────
    public function addLine(): void
    {
        $this->items[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items) ?: [$this->blankLine()];
    }

    public function moveLine(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (isset($this->items[$index], $this->items[$target])) {
            [$this->items[$index], $this->items[$target]] = [$this->items[$target], $this->items[$index]];
        }
    }

    public function updatedItems($value, string $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];
        if ($field !== 'product_id' || ! $value) {
            return;
        }
        $product = Product::where('company_id', company_id())->find($value);
        if (! $product) {
            return;
        }
        $discount = $this->type !== 'purchase_order' ? (float) Contact::find($this->contact_id)?->discount_rate : 0;
        $this->items[$index] = array_merge($this->items[$index], [
            'description' => $product->name,
            'unit' => $product->unit,
            'unit_price' => (float) ($this->type === 'purchase_order' ? $product->purchase_price : $product->sale_price),
            'tax_rate' => $this->type === 'purchase_order' ? 0 : (float) $product->tax_rate,
            'discount_rate' => $discount,
        ]);
        if ($this->type === 'quote' && $product->is_manufactured) {
            $this->orderKind = 'fabrication';
        }
    }

    #[Computed]
    public function totals(): array
    {
        $subtotal = $discount = $tax = 0;
        foreach ($this->items as $item) {
            $gross = round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2);
            $lineDiscount = round($gross * (float) ($item['discount_rate'] ?? 0) / 100, 2);
            $subtotal += $gross;
            $discount += $lineDiscount;
            $tax += round(($gross - $lineDiscount) * (float) ($item['tax_rate'] ?? 0) / 100, 2);
        }

        return ['subtotal' => $subtotal, 'discount' => $discount, 'net' => $subtotal - $discount, 'tax' => $tax, 'total' => $subtotal - $discount + $tax];
    }

    // ── Enregistrement ──────────────────────────────────────────────────────
    public function save(CreateDocument $creator, UpdateDocument $updater, OrderWorkflow $workflow)
    {
        $this->authorizeModule($this->module(), 'manage');
        $this->validate([
            'contact_id' => ['required', 'integer'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency_code' => ['required', 'in:USD,CDF,EUR'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'template' => ['required', 'in:'.implode(',', array_keys(config('erp.document_templates')))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'orderTitle' => ['nullable', 'string', 'max:200'],
            'deliveryDate' => ['nullable', 'date'],
        ], [], [
            'contact_id' => $this->type === 'purchase_order' ? 'fournisseur' : 'client', 'items.*.description' => 'désignation',
            'items.*.quantity' => 'quantité', 'items.*.unit_price' => 'prix unitaire', 'due_date' => 'échéance',
        ]);
        abort_unless(Contact::where('company_id', company_id())->whereKey($this->contact_id)->exists(), 422);

        $payload = [
            'contact_id' => $this->contact_id,
            'issue_date' => $this->issue_date,
            'due_date' => $this->type === 'quote' ? null : ($this->due_date ?: null),
            'valid_until' => $this->type === 'quote' ? ($this->due_date ?: null) : null,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'template' => $this->template,
            'notes' => $this->notes ?: null,
            'terms' => $this->terms ?: null,
        ];
        if ($this->type === 'quote') {
            $payload['meta'] = array_merge((array) $this->document?->meta, ['heading' => $this->heading === 'proforma' ? 'proforma' : 'devis']);
        }
        $items = array_map(fn ($i) => [
            'product_id' => $i['product_id'] ?: null, 'description' => $i['description'], 'quantity' => (float) $i['quantity'], 'unit' => $i['unit'] ?: 'unité',
            'unit_price' => (float) $i['unit_price'], 'discount_rate' => (float) ($i['discount_rate'] ?: 0), 'tax_rate' => (float) ($i['tax_rate'] ?: 0),
        ], $this->items);

        $document = $this->attempt(function () use ($creator, $updater, $workflow, $payload, $items) {
            if ($this->document) {
                return $updater->handle($this->document, $payload, $items);
            }
            $document = $creator->handle($payload + ['company_id' => company_id(), 'created_by' => auth()->id(), 'type' => DocumentType::from($this->type)], array_values(array_map(fn ($i, $p) => $i + ['position' => $p], $items, array_keys($items))));
            if ($this->type === 'quote' && $this->openOrder) {
                $order = $workflow->openFromQuote($document->load('items', 'contact'), $this->orderKind, $this->orderTitle ?: null);
                $order->update(['delivery_date' => $this->deliveryDate ?: null]);
            }

            return $document;
        });

        if (! $document) {
            return null;
        }
        session()->flash('toast', ['type' => 'success', 'message' => ($this->document ? 'Document mis à jour : ' : 'Document créé : ').$document->number]);

        return $this->redirectRoute('documents.show', $document, navigate: true);
    }

    public function render()
    {
        $partyType = $this->type === 'purchase_order' ? 'supplier' : 'client';
        $labels = ['quote' => $this->heading === 'proforma' ? ['Pro forma', 'Nouvelle pro forma'] : ['Devis', 'Nouveau devis'], 'invoice' => ['Facture', 'Nouvelle facture'], 'purchase_order' => ['Bon de commande fournisseur', 'Nouveau bon de commande']][$this->type];

        return view('livewire.sales.document-editor', [
            'labels' => $labels,
            'contacts' => Contact::where('company_id', company_id())->where('type', $partyType)->where('is_active', true)->orderByRaw('COALESCE(company_name, last_name)')->get(),
            'products' => Product::where('company_id', company_id())->where('is_active', true)->where($this->type === 'purchase_order' ? 'is_purchasable' : 'is_sellable', true)->orderBy('name')->get(['id', 'sku', 'name', 'type']),
            'contact' => $this->contact_id ? Contact::find($this->contact_id) : null,
            'backUrl' => $this->document ? route('documents.show', $this->document) : route(match ($this->type) {
                'quote' => 'quotes', 'purchase_order' => 'purchases', default => 'invoices'
            }),
        ])->title($this->document ? $labels[0].' '.$this->document->number : $labels[1]);
    }
}
