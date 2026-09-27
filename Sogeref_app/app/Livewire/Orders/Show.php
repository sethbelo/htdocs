<?php

namespace App\Livewire\Orders;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashService;
use App\Services\OrderWorkflow;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Fiche d'un dossier de commande : étapes, actions contextuelles,
 * pièces liées, encaissements et journal de suivi.
 */
class Show extends Component
{
    use InteractsWithUi;

    public SalesOrder $order;

    // Fenêtres
    public bool $showPayment = false;

    public bool $showLaunch = false;

    public bool $showCancel = false;

    public bool $showEdit = false;

    // Encaissement
    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    public ?int $paymentAccountId = null;

    public string $paymentReference = '';

    // Lancement
    public ?int $responsibleId = null;

    public ?int $warehouseId = null;

    public ?int $managerUserId = null;

    public string $siteAddress = '';

    public string $siteCity = '';

    // Divers
    public string $note = '';

    public string $cancelReason = '';

    public string $deliveryDate = '';

    public string $priority = 'normale';

    public string $title = '';

    public function mount(SalesOrder $order): void
    {
        abort_unless($order->company_id === company_id(), 404);
        $this->order = $order;
        $this->fillDetails();
    }

    private function fillDetails(): void
    {
        $this->deliveryDate = $this->order->delivery_date?->toDateString() ?? '';
        $this->priority = $this->order->priority;
        $this->title = $this->order->title;
    }

    #[Computed]
    public function workflow(): OrderWorkflow
    {
        return app(OrderWorkflow::class);
    }

    // ── Actions du parcours ─────────────────────────────────────────────────
    public function validateQuote(): void
    {
        abort_unless($this->allowed('orders.validate') || auth()->user()->canManage('orders'), 403);
        $this->attempt(fn () => $this->workflow->validate($this->order), 'Devis validé : le dossier passe en facturation.');
        $this->order->refresh();
    }

    public function issueInvoice(): void
    {
        $this->authorizeModule('orders', 'manage');
        $invoice = $this->attempt(fn () => $this->workflow->invoice($this->order));
        if ($invoice) {
            $this->toast("Facture {$invoice->number} émise. Encaissement attendu à la caisse.");
        }
        $this->order->refresh();
    }

    public function openPayment(): void
    {
        $this->authorizeModule('cash', 'manage');
        $invoice = $this->order->invoice;
        $deposit = round((float) $invoice->total * $this->workflow->depositRate() / 100 - (float) $invoice->amount_paid, 2);
        $this->paymentAmount = number_format($this->order->stage === 'facture' && $deposit > 0 ? $deposit : $invoice->balance_due, 2, '.', '');
        $this->paymentAccountId = FinancialAccount::where('company_id', company_id())->where('type', 'cash')->where('is_active', true)->value('id');
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->showPayment = true;
    }

    public function updatedPaymentMethod(): void
    {
        $type = match ($this->paymentMethod) {
            'cash' => 'cash', 'mobile_money' => 'mobile_money', default => 'bank'
        };
        $this->paymentAccountId = FinancialAccount::where('company_id', company_id())->where('type', $type)->where('is_active', true)->value('id');
    }

    public function recordPayment(CashService $cash): void
    {
        $this->authorizeModule('cash', 'manage');
        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'gt:0'],
            'paymentMethod' => ['required', 'in:cash,mobile_money,bank_transfer,card,cheque'],
            'paymentAccountId' => ['required', 'integer'],
        ], [], ['paymentAmount' => 'montant', 'paymentAccountId' => 'compte']);

        $account = FinancialAccount::where('company_id', company_id())->findOrFail($this->paymentAccountId);
        $done = $this->attempt(function () use ($cash, $account) {
            $cash->receive($this->order->invoice, (float) $this->paymentAmount, $this->paymentMethod, $account, $this->paymentReference ?: null);
            $this->workflow->syncPayment($this->order->fresh());
        }, 'Paiement de '.money($this->paymentAmount).' enregistré.');

        if ($done) {
            $this->showPayment = false;
            $this->order->refresh();
        }
    }

    public function openLaunch(): void
    {
        $this->authorizeModule('orders', 'manage');
        $this->warehouseId = Warehouse::where('company_id', company_id())->where('type', $this->order->kind === 'fabrication' ? 'atelier' : 'central')->value('id');
        $this->managerUserId = auth()->id();
        $this->siteCity = $this->order->contact?->city ?? '';
        $this->showLaunch = true;
    }

    public function launch(): void
    {
        $this->authorizeModule('orders', 'manage');
        $done = $this->attempt(fn () => $this->workflow->launch($this->order, array_filter([
            'responsible_id' => $this->responsibleId,
            'warehouse_id' => $this->warehouseId,
            'site_manager_id' => $this->responsibleId,
            'manager_user_id' => $this->managerUserId,
            'address' => $this->siteAddress ?: null,
            'city' => $this->siteCity ?: null,
        ])), match ($this->order->kind) {
            'fabrication' => 'Ordre de fabrication transmis à l’atelier.',
            'construction' => 'Chantier, projet et dossier de construction créés.',
            'service' => 'Projet de prestation ouvert.',
            default => 'Commande préparée et livrée.',
        });
        if ($done) {
            $this->showLaunch = false;
            $this->order->refresh();
        }
    }

    public function deliver(): void
    {
        $this->authorizeModule('orders', 'manage');
        $this->attempt(fn () => $this->workflow->deliver($this->order, $this->note ?: null), 'Livraison enregistrée.');
        $this->note = '';
        $this->order->refresh();
    }

    public function close(): void
    {
        $this->authorizeModule('orders', 'manage');
        $this->attempt(fn () => $this->workflow->close($this->order), 'Dossier clôturé.');
        $this->order->refresh();
    }

    public function cancel(): void
    {
        $this->authorizeModule('orders', 'manage');
        $this->validate(['cancelReason' => ['required', 'string', 'min:5']], [], ['cancelReason' => 'motif']);
        if ($this->attempt(fn () => $this->workflow->cancel($this->order, $this->cancelReason), 'Commande annulée.')) {
            $this->showCancel = false;
            $this->order->refresh();
        }
    }

    public function addNote(): void
    {
        $this->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->workflow->note($this->order, $this->note);
        $this->note = '';
        $this->toast('Note ajoutée au journal.');
    }

    public function saveDetails(): void
    {
        $this->authorizeModule('orders', 'manage');
        $data = $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'deliveryDate' => ['nullable', 'date'],
            'priority' => ['required', 'in:basse,normale,haute,urgente'],
        ]);
        $this->order->update(['title' => $data['title'], 'delivery_date' => $data['deliveryDate'] ?: null, 'priority' => $data['priority']]);
        $this->showEdit = false;
        $this->toast('Dossier mis à jour.');
    }

    public function render()
    {
        $this->order->load('contact.loyaltyCard', 'quote.items.product', 'invoice.allocations.payment', 'manufacturingOrder.materials', 'project', 'site', 'owner', 'events.user', 'vouchers');

        return view('livewire.orders.show', [
            'steps' => collect($this->order->steps())->mapWithKeys(fn ($s) => [$s => erp_status('order_stage', $s)[0]])->all(),
            'actions' => $this->workflow->nextActions($this->order),
            'employees' => Employee::where('company_id', company_id())->where('status', 'active')->where('pay_type', 'monthly')->orderBy('last_name')->get(),
            'warehouses' => Warehouse::where('company_id', company_id())->where('is_active', true)->orderBy('name')->get(),
            'users' => User::whereHas('companies', fn ($q) => $q->where('companies.id', company_id()))->where('is_active', true)->orderBy('name')->get(),
            'accounts' => FinancialAccount::where('company_id', company_id())->where('is_active', true)->orderBy('type')->get(),
        ])->title($this->order->number.' · Suivi des commandes');
    }
}
