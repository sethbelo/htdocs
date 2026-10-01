<?php

namespace App\Livewire\Sales;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Actions\Documents\CreateDocument;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Document;
use App\Models\FinancialAccount;
use App\Models\Warehouse;
use App\Services\CashService;
use App\Services\OrderWorkflow;
use App\Services\ReplenishmentService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Fiche d'un document : aperçu PDF, changement de modèle, statut,
 * encaissement, réception (achats) et conversion en dossier de commande.
 */
class DocumentShow extends Component
{
    use InteractsWithUi;

    public Document $document;

    public string $template = 'moderne';

    public bool $showPayment = false;

    public bool $showOrder = false;

    public bool $showReceive = false;

    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    public ?int $paymentAccountId = null;

    public string $paymentReference = '';

    public string $orderKind = 'fabrication';

    public string $orderTitle = '';

    public ?int $receiveWarehouseId = null;

    public array $received = [];

    public function mount(Document $document): void
    {
        abort_unless($document->company_id === company_id(), 404);
        $this->document = $document;
        $this->authorizeModule($this->module());
        $this->template = $document->template ?: 'moderne';
    }

    private function module(): string
    {
        return match ($this->document->type->value) {
            'quote' => 'quotes', 'purchase_order' => 'purchases', default => 'invoices'
        };
    }

    public function updatedTemplate(string $value): void
    {
        if (array_key_exists($value, config('erp.document_templates')) && auth()->user()->canManage($this->module())) {
            $this->document->update(['template' => $value]);
        }
    }

    public function markSent(): void
    {
        $this->authorizeModule($this->module(), 'manage');
        $this->document->update(['status' => DocumentStatus::Sent, 'sent_at' => now()]);
        $this->toast('Document marqué comme envoyé.');
    }

    public function reject(): void
    {
        $this->authorizeModule($this->module(), 'manage');
        $this->document->update(['status' => DocumentStatus::Rejected]);
        if (($order = $this->document->relatedOrder()) && $order->stage === 'devis') {
            app(OrderWorkflow::class)->cancel($order, 'Devis refusé par le client.');
        }
        $this->toast('Devis marqué comme refusé.', 'warning');
    }

    public function accept(OrderWorkflow $workflow): void
    {
        $this->authorizeModule($this->module(), 'manage');
        $order = $this->document->relatedOrder();
        $order
            ? $this->attempt(fn () => $workflow->validate($order), 'Devis accepté : le dossier passe en facturation.')
            : $this->document->update(['status' => DocumentStatus::Accepted, 'accepted_at' => now()]);
    }

    public function openOrder(OrderWorkflow $workflow)
    {
        $this->authorizeModule('orders', 'manage');
        $order = $this->attempt(fn () => $workflow->openFromQuote($this->document->load('items', 'contact'), $this->orderKind, $this->orderTitle ?: null), 'Dossier de commande ouvert.');

        return $order ? $this->redirectRoute('orders.show', $order, navigate: true) : null;
    }

    public function openPayment(): void
    {
        $this->authorizeModule('cash', 'manage');
        $this->paymentAmount = number_format($this->document->balance_due, 2, '.', '');
        $this->paymentAccountId = FinancialAccount::where('company_id', company_id())->where('type', 'cash')->value('id');
        $this->showPayment = true;
    }

    public function recordPayment(CashService $cash, OrderWorkflow $workflow): void
    {
        $this->authorizeModule('cash', 'manage');
        $this->validate(['paymentAmount' => ['required', 'numeric', 'gt:0'], 'paymentAccountId' => ['required', 'integer']], [], ['paymentAmount' => 'montant', 'paymentAccountId' => 'compte']);
        $account = FinancialAccount::where('company_id', company_id())->findOrFail($this->paymentAccountId);
        $done = $this->attempt(function () use ($cash, $workflow, $account) {
            $cash->receive($this->document, (float) $this->paymentAmount, $this->paymentMethod, $account, $this->paymentReference ?: null);
            if ($order = $this->document->fresh()->relatedOrder()) {
                $workflow->syncPayment($order);
            }
        }, 'Paiement enregistré.');
        if ($done) {
            $this->showPayment = false;
            $this->document->refresh();
        }
    }

    public function openReceive(): void
    {
        $this->authorizeModule('purchases', 'manage');
        $this->receiveWarehouseId = data_get($this->document->meta, 'warehouse_id') ?? Warehouse::where('company_id', company_id())->where('type', 'central')->value('id');
        $this->received = $this->document->items->mapWithKeys(fn ($i) => [$i->id => max(0, (float) $i->quantity - (float) $i->received_quantity)])->all();
        $this->showReceive = true;
    }

    public function receive(ReplenishmentService $service): void
    {
        $this->authorizeModule('purchases', 'manage');
        $warehouse = Warehouse::where('company_id', company_id())->findOrFail($this->receiveWarehouseId);
        if ($this->attempt(fn () => $service->receive($this->document->load('items.product'), $warehouse, $this->received), 'Réception enregistrée : stock mis à jour.')) {
            $this->showReceive = false;
            $this->document->refresh();
        }
    }

    public function duplicate(CreateDocument $creator)
    {
        $this->authorizeModule($this->module(), 'manage');
        $copy = $creator->handle([
            'company_id' => company_id(), 'contact_id' => $this->document->contact_id, 'created_by' => auth()->id(), 'type' => $this->document->type,
            'issue_date' => now()->toDateString(), 'due_date' => $this->document->type === DocumentType::Quote ? null : now()->addDays(30)->toDateString(),
            'valid_until' => $this->document->type === DocumentType::Quote ? now()->addDays(30)->toDateString() : null,
            'currency_code' => $this->document->currency_code, 'exchange_rate' => $this->document->exchange_rate,
            'notes' => $this->document->notes, 'terms' => $this->document->terms, 'template' => $this->document->template,
        ], $this->document->items->map->only(['product_id', 'description', 'quantity', 'unit', 'unit_price', 'discount_rate', 'tax_rate', 'position'])->all());
        session()->flash('toast', ['type' => 'success', 'message' => "Copie créée : {$copy->number}"]);

        return $this->redirectRoute('documents.show', $copy, navigate: true);
    }

    public function delete(SynchronizeAccounting $accounting)
    {
        $this->authorizeModule($this->module(), 'manage');
        $order = $this->document->relatedOrder();
        if ((float) $this->document->amount_paid > 0 || ($order && $order->stage !== 'devis')) {
            $this->toast('Ce document est lié à un dossier avancé ou à des encaissements : suppression impossible.', 'error');

            return null;
        }
        DB::transaction(function () use ($accounting, $order) {
            $order?->delete();
            $accounting->remove($this->document);
            $this->document->delete();
        });
        session()->flash('toast', ['type' => 'success', 'message' => 'Document supprimé.']);

        return $this->redirectRoute($this->module(), navigate: true);
    }

    public function render()
    {
        $this->document->load('contact', 'items.product', 'allocations.payment', 'creator');
        $type = $this->document->type->value;

        return view('livewire.sales.document-show', [
            'type' => $type,
            'order' => $this->document->relatedOrder(),
            'canManage' => auth()->user()->canManage($this->module()),
            'accounts' => FinancialAccount::where('company_id', company_id())->where('is_active', true)->get(),
            'warehouses' => Warehouse::where('company_id', company_id())->where('is_active', true)->get(),
            'label' => ['quote' => $this->document->isProformaHeading() ? 'Pro forma' : 'Devis', 'invoice' => 'Facture', 'purchase_order' => 'Bon de commande', 'credit_note' => 'Avoir'][$type],
            'backRoute' => $this->module(),
        ])->title($this->document->number);
    }
}
