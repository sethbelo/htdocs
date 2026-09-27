<?php

namespace App\Services;

use App\Actions\Documents\ConvertQuoteToInvoice;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Parcours d'une commande client SOGEREF :
 *
 *   devis ─► valide ─► facture ─► paye ─┬─► fabrication (atelier) ─┐
 *                                       ├─► execution (chantier)   ├─► livre ─► cloture
 *                                       └─► (vente simple) ────────┘
 */
class OrderWorkflow
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ConvertQuoteToInvoice $converter,
        private readonly ManufacturingService $manufacturing,
        private readonly StockService $stock,
    ) {}

    /** Seuil d'acompte (en %) qui débloque la mise en production. */
    public function depositRate(): float
    {
        return (float) erp_setting('orders.deposit_rate', 50);
    }

    // ── 1. Ouverture du dossier depuis un devis ──────────────────────────
    public function openFromQuote(Document $quote, string $kind, ?string $title = null, ?string $description = null): SalesOrder
    {
        if ($quote->type !== DocumentType::Quote) {
            throw new DomainException('Un dossier de commande s’ouvre à partir d’un devis.');
        }
        if ($existing = SalesOrder::where('quote_id', $quote->id)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($quote, $kind, $title, $description) {
            $order = SalesOrder::create([
                'company_id' => $quote->company_id,
                'branch_id' => $quote->branch_id,
                'contact_id' => $quote->contact_id,
                'number' => $this->sequences->next('sales_order', $quote->company_id),
                'title' => $title ?: ($quote->items->first()?->description ?? "Commande {$quote->number}"),
                'description' => $description ?: $quote->notes,
                'kind' => $kind,
                'channel' => $quote->contact?->segment ?? 'b2b',
                'stage' => 'devis',
                'quote_id' => $quote->id,
                'total' => $quote->total,
                'currency_code' => $quote->currency_code,
                'owner_id' => auth()->id() ?? $quote->created_by,
            ]);
            $this->log($order, null, 'devis', "Dossier ouvert depuis le devis {$quote->number}");

            return $order;
        });
    }

    // ── 2. Validation client du devis ────────────────────────────────────
    public function validate(SalesOrder $order, ?string $note = null): void
    {
        $this->expect($order, ['devis']);
        DB::transaction(function () use ($order, $note) {
            $order->quote?->update(['status' => DocumentStatus::Accepted, 'accepted_at' => now()]);
            $this->move($order, 'valide', 'Devis validé par le client', $note, ['validated_at' => now()]);
        });
    }

    // ── 3. Facturation ───────────────────────────────────────────────────
    public function invoice(SalesOrder $order): Document
    {
        $this->expect($order, ['valide']);

        return DB::transaction(function () use ($order) {
            $invoice = $this->converter->handle($order->quote()->with('items', 'contact')->firstOrFail());
            $invoice->update(['status' => DocumentStatus::Sent, 'sent_at' => now()]);
            $this->move($order, 'facture', "Facture {$invoice->number} émise", null, ['invoice_id' => $invoice->id, 'total' => $invoice->total]);

            return $invoice;
        });
    }

    // ── 4. Encaissement (appelé par la caisse après chaque paiement) ─────
    public function syncPayment(SalesOrder $order): void
    {
        $invoice = $order->invoice()->first();
        if (! $invoice) {
            return;
        }
        $order->update(['amount_paid' => $invoice->amount_paid]);
        if ($order->stage === 'facture' && $order->paidRatio() * 100 + 1e-6 >= $this->depositRate()) {
            $label = $order->balanceDue() > 0
                ? sprintf('Acompte encaissé (%d %%) — production débloquée', round($order->paidRatio() * 100))
                : 'Facture intégralement encaissée';
            $this->move($order, 'paye', $label);
        }
    }

    // ── 5. Lancement : atelier, chantier ou livraison directe ────────────
    public function launch(SalesOrder $order, array $options = []): void
    {
        $this->expect($order, ['paye']);

        DB::transaction(function () use ($order, $options) {
            match ($order->kind) {
                'fabrication' => $this->launchManufacturing($order, $options),
                'construction', 'service' => $this->launchExecution($order, $options),
                default => $this->launchDirectSale($order),
            };
        });
    }

    private function launchDirectSale(SalesOrder $order): void
    {
        $this->deliverGoods($order);
        $this->move($order, 'livre', 'Commande préparée et remise au client', null, ['delivered_at' => now()]);
    }

    private function launchManufacturing(SalesOrder $order, array $options): void
    {
        $mo = $this->manufacturing->createFromOrder($order, $options);
        $this->move($order, 'fabrication', "Ordre de fabrication {$mo->number} transmis à l’atelier", null, ['manufacturing_order_id' => $mo->id]);
    }

    private function launchExecution(SalesOrder $order, array $options): void
    {
        $project = Project::create([
            'company_id' => $order->company_id,
            'contact_id' => $order->contact_id,
            'sales_order_id' => $order->id,
            'manager_id' => $options['manager_user_id'] ?? auth()->id(),
            'code' => $this->sequences->next('project', $order->company_id),
            'name' => $order->title,
            'description' => $order->description,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => $order->delivery_date,
            'budget' => $order->total,
        ]);
        $attributes = ['project_id' => $project->id];

        if ($order->kind === 'construction') {
            $site = Site::create([
                'company_id' => $order->company_id,
                'contact_id' => $order->contact_id,
                'sales_order_id' => $order->id,
                'project_id' => $project->id,
                'manager_id' => $options['site_manager_id'] ?? null,
                'code' => $this->sequences->next('site', $order->company_id),
                'name' => $order->title,
                'address' => $options['address'] ?? null,
                'city' => $options['city'] ?? $order->contact?->city,
                'description' => $order->description,
                'status' => 'planned',
                'start_date' => now()->toDateString(),
                'planned_end_date' => $order->delivery_date,
                'budget' => $order->total,
            ]);
            $attributes['site_id'] = $site->id;
            $mo = $this->manufacturing->createFromOrder($order, ['type' => 'construction'] + $options);
            $attributes['manufacturing_order_id'] = $mo->id;
        }

        $this->move($order, 'execution', $order->kind === 'construction' ? 'Chantier et dossier de construction ouverts' : 'Projet de prestation ouvert', null, $attributes);
    }

    // ── 6. Livraison & clôture ───────────────────────────────────────────
    public function deliver(SalesOrder $order, ?string $note = null): void
    {
        $this->expect($order, ['fabrication', 'execution']);
        if ($order->manufacturingOrder && $order->kind === 'fabrication' && $order->manufacturingOrder->status !== 'done') {
            throw new DomainException('L’ordre de fabrication doit être terminé avant la livraison.');
        }
        DB::transaction(function () use ($order, $note) {
            if ($order->kind === 'fabrication') {
                $this->deliverGoods($order);
            }
            $this->move($order, 'livre', 'Commande livrée / réceptionnée par le client', $note, ['delivered_at' => now()]);
        });
    }

    /**
     * Sortie de stock des articles livrés : produits finis depuis l'atelier
     * (ou le showroom), marchandises et matériaux depuis le dépôt central.
     */
    private function deliverGoods(SalesOrder $order): void
    {
        $items = $order->quote?->items ?? collect();
        foreach ($items as $item) {
            $product = $item->product;
            if (! $product || ! $product->track_stock) {
                continue;
            }
            $warehouse = $product->is_manufactured
                ? ($order->manufacturingOrder?->warehouse ?? Warehouse::where('company_id', $order->company_id)->where('type', 'atelier')->first())
                : Warehouse::where('company_id', $order->company_id)->where('type', 'central')->first();
            if ($warehouse) {
                $this->stock->issue($product, $warehouse, (float) $item->quantity, 'sale', $order, $order->number, "Livraison {$order->number}", allowNegative: true);
            }
        }
    }

    public function close(SalesOrder $order): void
    {
        $this->expect($order, ['livre']);
        if ($order->balanceDue() > 0) {
            throw new DomainException('Le solde de la facture doit être encaissé avant la clôture ('.money($order->balanceDue(), $order->currency_code).' restant).');
        }
        $this->move($order, 'cloture', 'Dossier clôturé');
    }

    public function cancel(SalesOrder $order, string $reason): void
    {
        if (in_array($order->stage, ['cloture', 'annule'], true)) {
            throw new DomainException('Ce dossier ne peut plus être annulé.');
        }
        if ((float) $order->amount_paid > 0) {
            throw new DomainException('Des encaissements existent : établissez un avoir avant d’annuler.');
        }
        $this->move($order, 'annule', 'Commande annulée', $reason);
    }

    public function note(SalesOrder $order, string $note): void
    {
        $this->log($order, $order->stage, $order->stage, 'Note interne', $note);
    }

    /** Actions disponibles à l'étape courante (pour l'interface). */
    public function nextActions(SalesOrder $order): array
    {
        return match ($order->stage) {
            'devis' => ['validate'],
            'valide' => ['invoice'],
            'facture' => ['collect'],
            'paye' => ['launch'],
            'fabrication', 'execution' => ['deliver'],
            'livre' => $order->balanceDue() > 0 ? ['collect'] : ['close'],
            default => [],
        };
    }

    // ── Outils internes ──────────────────────────────────────────────────
    private function expect(SalesOrder $order, array $stages): void
    {
        if (! in_array($order->stage, $stages, true)) {
            [$label] = erp_status('order_stage', $order->stage);
            throw new DomainException("Action impossible : la commande est à l’étape « {$label} ».");
        }
    }

    private function move(SalesOrder $order, string $to, string $title, ?string $note = null, array $attributes = []): void
    {
        $from = $order->stage;
        $order->update(['stage' => $to] + $attributes);
        $this->log($order, $from, $to, $title, $note);
    }

    private function log(SalesOrder $order, ?string $from, ?string $to, string $title, ?string $note = null): void
    {
        $order->events()->create(['user_id' => auth()->id(), 'from_stage' => $from, 'to_stage' => $to, 'title' => $title, 'note' => $note]);
    }
}
