<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\AccountingService;
use App\Support\DocumentPreferences;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    // Filters & Pagination
    public $search = '';

    public $filterStatus = '';

    public $filterStartDate = '';

    public $filterEndDate = '';

    public $perPage = 15;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    // Modal Control
    public $showFormModal = false;

    public $showViewModal = false;

    public $editingInvoiceId = null;

    public $selectedInvoice = null;

    public $showPaymentModal = false;

    public $showCreditModal = false;

    public $showDeleteModal = false;

    public $actionInvoiceId = null;

    public $paymentDate;

    public $paymentAmount;

    public $paymentMethod = 'cash';

    public $paymentTreasuryAccount = '5711';

    public $paymentReference = '';

    public $creditDate;

    public $creditSubtotal;

    public $creditTaxAmount = 0;

    public $creditReason = '';

    // Form fields
    public $customer_id = '';

    public $invoice_date;

    public $due_date;

    public $status = 'draft';

    public $tax_percent = 0;

    public $discount_amount = 0;

    public $notes = '';

    public $items = [];

    public function mount()
    {
        $this->invoice_date = Carbon::today()->toDateString();
        $this->due_date = Carbon::today()->addDays(30)->toDateString();
        $this->paymentDate = Carbon::today()->toDateString();
        $this->creditDate = Carbon::today()->toDateString();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatingFilterEndDate()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterStartDate', 'filterEndDate']);
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if (! in_array($field, ['number', 'invoice_date', 'due_date', 'status', 'total_amount'], true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->editingInvoiceId = null;
        $this->customer_id = '';
        $this->invoice_date = Carbon::today()->toDateString();
        $this->due_date = Carbon::today()->addDays(30)->toDateString();
        $this->status = 'draft';
        $this->tax_percent = 0;
        $this->discount_amount = 0;
        $this->notes = '';
        $this->items = [
            ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0],
        ];
        $this->showFormModal = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $invoice = Invoice::with('items')->findOrFail($id);
        if ($invoice->posted_at) {
            $this->dispatch('notify', 'Une facture validée ne peut plus être modifiée. Créez un avoir.');

            return;
        }
        $this->editingInvoiceId = $invoice->id;
        $this->customer_id = $invoice->customer_id ?? '';
        $this->invoice_date = $invoice->invoice_date->format('Y-m-d');
        $this->due_date = $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '';
        $this->status = in_array($invoice->status, ['draft', 'unpaid'], true) ? $invoice->status : 'draft';
        $this->tax_percent = (float) $invoice->tax_percent;
        $this->discount_amount = (float) $invoice->discount_amount;
        $this->notes = $invoice->notes ?? '';

        $this->items = [];
        foreach ($invoice->items as $item) {
            $this->items[] = [
                'product_id' => $item->product_id ?? '',
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ];
        }

        if (empty($this->items)) {
            $this->items[] = ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0];
        }

        $this->showFormModal = true;
    }

    public function openViewModal($id)
    {
        $this->selectedInvoice = Invoice::with(['customer', 'user', 'items.product', 'payments.user', 'creditNotes'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function openPaymentModal($id)
    {
        $invoice = Invoice::with('creditNotes')->findOrFail($id);
        $this->resetValidation();
        $this->actionInvoiceId = $invoice->id;
        $this->paymentDate = Carbon::today()->toDateString();
        $this->paymentAmount = $invoice->balance_due;
        $this->paymentMethod = 'cash';
        $this->paymentTreasuryAccount = '5711';
        $this->paymentReference = '';
        $this->showPaymentModal = true;
    }

    public function updatedPaymentMethod($method)
    {
        $this->paymentTreasuryAccount = match ($method) {
            'bank' => '5211',
            'mobile' => '5218',
            default => '5711',
        };
    }

    public function recordPayment(AccountingService $service)
    {
        $data = $this->validate([
            'actionInvoiceId' => 'required|exists:invoices,id',
            'paymentDate' => 'required|date',
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|in:cash,bank,mobile',
            'paymentTreasuryAccount' => 'required|string|max:20',
            'paymentReference' => 'nullable|string|max:100',
        ]);
        $invoice = Invoice::findOrFail($data['actionInvoiceId']);
        $payment = $service->recordPayment($invoice, [
            'payment_date' => $data['paymentDate'], 'amount' => $data['paymentAmount'],
            'method' => $data['paymentMethod'], 'treasury_account' => $data['paymentTreasuryAccount'],
            'reference' => $data['paymentReference'] ?: null, 'notes' => null,
        ]);
        ActivityLog::log('Facturation', "Paiement {$payment->number} enregistré sur {$invoice->number}.");
        $this->reset(['actionInvoiceId', 'paymentAmount', 'paymentReference', 'showPaymentModal']);
        $this->dispatch('notify', "Paiement {$payment->number} enregistré.");
    }

    public function openCreditModal($id)
    {
        $invoice = Invoice::with('creditNotes')->findOrFail($id);
        $this->resetValidation();
        $this->actionInvoiceId = $invoice->id;
        $this->creditDate = Carbon::today()->toDateString();
        $this->creditSubtotal = $invoice->balance_due;
        $this->creditTaxAmount = 0;
        $this->creditReason = '';
        $this->showCreditModal = true;
    }

    public function createCreditNote(AccountingService $service)
    {
        $data = $this->validate([
            'actionInvoiceId' => 'required|exists:invoices,id',
            'creditDate' => 'required|date',
            'creditSubtotal' => 'required|numeric|min:0.01',
            'creditTaxAmount' => 'nullable|numeric|min:0',
            'creditReason' => 'required|string|max:1000',
        ]);
        $invoice = Invoice::findOrFail($data['actionInvoiceId']);
        $credit = $service->createCreditNote($invoice, [
            'credit_date' => $data['creditDate'], 'subtotal' => $data['creditSubtotal'],
            'tax_amount' => $data['creditTaxAmount'] ?: 0, 'reason' => $data['creditReason'],
        ]);
        ActivityLog::log('Facturation', "Avoir {$credit->number} créé sur {$invoice->number}.");
        $this->reset(['actionInvoiceId', 'creditSubtotal', 'creditTaxAmount', 'creditReason', 'showCreditModal']);
        $this->dispatch('notify', "Avoir {$credit->number} comptabilisé.");
    }

    public function duplicateInvoice($id)
    {
        $source = Invoice::with('items')->findOrFail($id);
        $invoice = DB::transaction(function () use ($source) {
            $copy = Invoice::create([
                'number' => Invoice::generateNumber(), 'customer_id' => $source->customer_id,
                'invoice_date' => today(), 'due_date' => today()->addDays(30), 'status' => 'draft',
                'subtotal' => $source->subtotal, 'tax_percent' => $source->tax_percent,
                'tax_amount' => $source->tax_amount, 'discount_amount' => $source->discount_amount,
                'total_amount' => $source->total_amount, 'paid_amount' => 0,
                'notes' => $source->notes, 'user_id' => auth()->id(),
            ]);
            foreach ($source->items as $item) {
                $copy->items()->create($item->only(['product_id', 'description', 'quantity', 'unit_price', 'total_price']));
            }

            return $copy;
        });
        ActivityLog::log('Facturation', "Facture {$source->number} dupliquée en {$invoice->number}.");
        $this->dispatch('notify', "Brouillon {$invoice->number} créé.");
    }

    public function askDeleteInvoice($id)
    {
        $invoice = Invoice::findOrFail($id);
        if ($invoice->posted_at) {
            $this->dispatch('notify', 'Une facture comptabilisée doit être corrigée par un avoir.');

            return;
        }
        $this->actionInvoiceId = $id;
        $this->showDeleteModal = true;
    }

    public function confirmDeleteInvoice()
    {
        if ($this->actionInvoiceId) {
            $this->deleteInvoice($this->actionInvoiceId);
        }
        $this->reset(['actionInvoiceId', 'showDeleteModal']);
    }

    public function addItem()
    {
        $this->items[] = ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function selectProductForItem($index, $productId)
    {
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->items[$index]['product_id'] = $product->id;
                $this->items[$index]['description'] = $product->name;
                // Une facture travaille en HT, même si le catalogue contient un prix TTC.
                $this->items[$index]['unit_price'] = $product->netUnitPrice();
                $this->tax_percent = (float) $product->sales_tax_rate;
            }
        }
    }

    public function calculateSubtotal()
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $subtotal += ($qty * $price);
        }

        return $subtotal;
    }

    public function calculateTotal()
    {
        $subtotal = $this->calculateSubtotal();
        $tax = $subtotal * ((float) $this->tax_percent / 100);
        $discount = (float) $this->discount_amount;

        return max(0, $subtotal + $tax - $discount);
    }

    public function saveInvoice()
    {
        $this->validate([
            'customer_id' => [Rule::requiredIf($this->status === 'unpaid'), 'nullable', 'exists:customers,id'],
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'status' => 'required|in:draft,unpaid',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $subtotal = $this->calculateSubtotal();
        if ((float) $this->discount_amount > $subtotal) {
            $this->addError('discount_amount', 'La remise ne peut pas dépasser le sous-total.');

            return;
        }
        $taxAmount = $subtotal * ((float) $this->tax_percent / 100);
        $totalAmount = max(0, $subtotal + $taxAmount - (float) $this->discount_amount);
        $paidAmount = 0;

        if ($this->editingInvoiceId) {
            $invoice = Invoice::findOrFail($this->editingInvoiceId);
            $invoice->update([
                'customer_id' => $this->customer_id ?: null,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date ?: null,
                'status' => $this->status,
                'subtotal' => $subtotal,
                'tax_percent' => $this->tax_percent,
                'tax_amount' => $taxAmount,
                'discount_amount' => $this->discount_amount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $this->notes,
            ]);

            $invoice->items()->delete();
            ActivityLog::log('Facturation', "Facture #{$invoice->number} mise à jour.");
        } else {
            $invoice = Invoice::create([
                'number' => Invoice::generateNumber(),
                'customer_id' => $this->customer_id ?: null,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date ?: null,
                'status' => $this->status,
                'subtotal' => $subtotal,
                'tax_percent' => $this->tax_percent,
                'tax_amount' => $taxAmount,
                'discount_amount' => $this->discount_amount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $this->notes,
                'user_id' => auth()->id(),
            ]);

            ActivityLog::log('Facturation', "Nouvelle Facture #{$invoice->number} créée pour \${$totalAmount}.");
        }

        foreach ($this->items as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'] ?: null,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
            ]);
        }

        $this->showFormModal = false;
        $this->dispatch('notify', 'Facture enregistrée avec succès.');

        if ($this->status !== 'draft') {
            app(AccountingService::class)->postInvoice($invoice);
        }
    }

    public function postInvoice($id)
    {
        $invoice = Invoice::findOrFail($id);
        app(AccountingService::class)->postInvoice($invoice);
        ActivityLog::log('Comptabilité', "Facture #{$invoice->number} validée dans le journal des ventes.");
        $this->dispatch('notify', "Facture #{$invoice->number} validée.");
    }

    public function markAsPaid($id)
    {
        $this->openPaymentModal($id);
    }

    public function deleteInvoice($id)
    {
        $invoice = Invoice::findOrFail($id);
        if ($invoice->posted_at) {
            $this->dispatch('notify', 'Suppression refusée : cette facture est déjà comptabilisée.');

            return;
        }
        $num = $invoice->number;
        $invoice->delete();

        ActivityLog::log('Facturation', "Facture #{$num} supprimée.");
        $this->dispatch('notify', "Facture #{$num} supprimée.");
    }

    public function render()
    {
        // Printed invoices use the same active A4 model as administrative reports.
        $documentPreferences = DocumentPreferences::report();
        $query = Invoice::with(['customer', 'user'])->withSum('creditNotes', 'total_amount');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('customer', function ($cq) {
                        $cq->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->filterStatus === 'overdue') {
            $query->where('status', 'unpaid')->where('due_date', '<', today()->toDateString());
        } elseif ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterStartDate) {
            $query->where('invoice_date', '>=', $this->filterStartDate);
        }

        if ($this->filterEndDate) {
            $query->where('invoice_date', '<=', $this->filterEndDate);
        }

        // Totals calculations
        $totalsQuery = clone $query;
        $allInvoices = $totalsQuery->without(['customer', 'user'])->get();

        $totalInvoiced = $allInvoices->where('status', '!=', 'draft')->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalPaid = $allInvoices->sum('paid_amount');
        $totalUnpaid = $allInvoices->whereNotNull('posted_at')->sum(fn ($invoice) => $invoice->balance_due);
        $totalDraft = $allInvoices->where('status', 'draft')->count();
        $totalOverdue = $allInvoices->filter(fn ($invoice) => $invoice->status === 'unpaid' && $invoice->due_date?->isPast())->sum(fn ($invoice) => $invoice->balance_due);

        $invoices = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);

        return view('livewire.invoices', [
            'invoices' => $invoices,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalUnpaid' => $totalUnpaid,
            'totalDraft' => $totalDraft,
            'totalOverdue' => $totalOverdue,
            'currency' => Setting::get('company_currency', 'CDF'),
            'documentPreferences' => $documentPreferences,
            'documentTheme' => $documentPreferences['theme'],
            'documentTemplateMeta' => $documentPreferences['template_meta'],
        ])->layout('layouts.app');
    }
}
