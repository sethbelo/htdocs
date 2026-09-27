<?php

namespace App\Services;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Actions\Documents\CreateDocument;
use App\Enums\DocumentType;
use App\Models\BankTransaction;
use App\Models\FinancialAccount;
use App\Models\ManufacturingMaterial;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Bons internes avec circuit d'approbation :
 *   brouillon → soumis → approuvé → exécuté   (ou rejeté / annulé)
 *
 * Exécution :
 *   • sortie        : sortie de stock (et consommation de l'OF lié) ;
 *   • décaissement  : sortie de caisse + écriture comptable ;
 *   • achat         : génération d'un bon de commande fournisseur.
 */
class VoucherService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly StockService $stock,
        private readonly SynchronizeAccounting $accounting,
        private readonly CreateDocument $documents,
    ) {}

    public function create(string $type, array $data, array $lines, bool $submit = false): Voucher
    {
        if (! array_key_exists($type, config('erp.voucher_types'))) {
            throw new DomainException('Type de bon inconnu.');
        }

        return DB::transaction(function () use ($type, $data, $lines, $submit) {
            $companyId = $data['company_id'] ?? company_id();
            $voucher = Voucher::create($data + [
                'company_id' => $companyId,
                'type' => $type,
                'number' => $this->sequences->next("voucher_{$type}", $companyId),
                'requested_by' => auth()->id(),
                'status' => $submit ? 'submitted' : 'draft',
            ]);
            $this->syncLines($voucher, $lines);

            return $voucher->load('lines');
        });
    }

    public function update(Voucher $voucher, array $data, array $lines): Voucher
    {
        if (! in_array($voucher->status, ['draft', 'rejected'], true)) {
            throw new DomainException('Seul un bon en brouillon ou rejeté est modifiable.');
        }

        return DB::transaction(function () use ($voucher, $data, $lines) {
            $voucher->update($data + ['status' => 'draft']);
            $voucher->lines()->delete();
            $this->syncLines($voucher, $lines);

            return $voucher->load('lines');
        });
    }

    private function syncLines(Voucher $voucher, array $lines): void
    {
        $total = 0;
        foreach ($lines as $line) {
            $lineTotal = round((float) $line['quantity'] * (float) ($line['unit_price'] ?? 0), 2);
            $total += $lineTotal;
            $voucher->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit' => $line['unit'] ?? 'unité',
                'unit_price' => $line['unit_price'] ?? 0,
                'total' => $lineTotal,
            ]);
        }
        if ($voucher->type !== 'decaissement' || $total > 0) {
            $voucher->update(['amount' => $total]);
        }
    }

    public function submit(Voucher $voucher): void
    {
        $this->expect($voucher, ['draft', 'rejected']);
        $voucher->update(['status' => 'submitted']);
    }

    public function approve(Voucher $voucher): void
    {
        $this->expect($voucher, ['submitted']);
        $voucher->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
    }

    public function reject(Voucher $voucher, string $reason): void
    {
        $this->expect($voucher, ['submitted', 'approved']);
        $voucher->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now(), 'notes' => trim(($voucher->notes ? $voucher->notes."\n" : '').'Motif du rejet : '.$reason)]);
    }

    public function cancel(Voucher $voucher): void
    {
        $this->expect($voucher, ['draft', 'submitted', 'rejected']);
        $voucher->update(['status' => 'cancelled']);
    }

    public function execute(Voucher $voucher, ?int $financialAccountId = null): void
    {
        $this->expect($voucher, ['approved']);

        DB::transaction(function () use ($voucher, $financialAccountId) {
            match ($voucher->type) {
                'sortie' => $this->executeIssue($voucher),
                'decaissement' => $this->executeDisbursement($voucher, $financialAccountId),
                'achat' => $this->executePurchase($voucher),
            };
            $voucher->update(['status' => 'executed', 'executed_by' => auth()->id(), 'executed_at' => now()]);
        });
    }

    private function executeIssue(Voucher $voucher): void
    {
        $warehouse = $voucher->warehouse ?? throw new DomainException('Précisez le magasin de sortie.');
        $siteWarehouse = $voucher->site_id ? Warehouse::where('site_id', $voucher->site_id)->first() : null;

        foreach ($voucher->lines as $line) {
            if (! $line->product_id) {
                continue;
            }
            $product = Product::findOrFail($line->product_id);
            if (! $product->track_stock) {
                continue;
            }
            $siteWarehouse && ! $siteWarehouse->is($warehouse)
                ? $this->stock->transfer($product, $warehouse, $siteWarehouse, (float) $line->quantity, $voucher, $voucher->number, $voucher->purpose)
                : $this->stock->issue($product, $warehouse, (float) $line->quantity, $voucher->manufacturing_order_id ? 'consume' : 'out', $voucher, $voucher->number, $voucher->purpose);

            if ($voucher->manufacturing_order_id) {
                $material = ManufacturingMaterial::where('manufacturing_order_id', $voucher->manufacturing_order_id)->where('product_id', $line->product_id)->first();
                $material?->update(['quantity_consumed' => (float) $material->quantity_consumed + (float) $line->quantity]);
            }
        }
    }

    private function executeDisbursement(Voucher $voucher, ?int $financialAccountId): void
    {
        $account = FinancialAccount::where('company_id', $voucher->company_id)->findOrFail($financialAccountId ?? $voucher->financial_account_id);
        $amount = round((float) $voucher->amount, 2);
        if ($amount <= 0) {
            throw new DomainException('Le montant du décaissement doit être positif.');
        }
        if ($account->balance + 1e-6 < $amount) {
            throw new DomainException('Solde insuffisant sur '.$account->name.' ('.money($account->balance, $account->currency_code).').');
        }

        $voucher->update(['financial_account_id' => $account->id]);
        $transaction = BankTransaction::create([
            'financial_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'reference' => $voucher->number,
            'description' => "Décaissement {$voucher->number} · {$voucher->purpose}",
            'amount' => -$amount,
            'status' => 'reconciled',
            'reconcilable_type' => $voucher->getMorphClass(),
            'reconcilable_id' => $voucher->id,
        ]);
        $treasury = match ($account->type) {
            'cash' => '571', 'mobile_money' => '581', default => '521'
        };
        $this->accounting->postLines($transaction, $account->type === 'cash' ? 'CA' : 'BQ', now()->toDateString(), "Décaissement {$voucher->number}", [
            [$voucher->employee_id && ! $voucher->site_id ? '422' : '638', $voucher->purpose, $amount, 0],
            [$treasury, 'Sortie de trésorerie '.$voucher->number, 0, $amount],
        ], $voucher->company_id);
    }

    private function executePurchase(Voucher $voucher): void
    {
        if (! $voucher->contact_id) {
            throw new DomainException('Sélectionnez le fournisseur avant d’exécuter le bon d’achat.');
        }
        $order = $this->documents->handle([
            'company_id' => $voucher->company_id,
            'contact_id' => $voucher->contact_id,
            'created_by' => auth()->id(),
            'type' => DocumentType::PurchaseOrder,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'currency_code' => $voucher->currency_code,
            'exchange_rate' => 1,
            'notes' => "Issu du {$voucher->number} — {$voucher->purpose}",
            'meta' => ['voucher_id' => $voucher->id, 'warehouse_id' => $voucher->warehouse_id],
        ], $voucher->lines->map(fn ($l, $i) => ['product_id' => $l->product_id, 'description' => $l->description, 'quantity' => $l->quantity, 'unit' => $l->unit, 'unit_price' => $l->unit_price, 'tax_rate' => 0, 'position' => $i])->all());
        $voucher->update(['notes' => trim(($voucher->notes ? $voucher->notes."\n" : '')."Bon de commande fournisseur : {$order->number}")]);
    }

    private function expect(Voucher $voucher, array $statuses): void
    {
        if (! in_array($voucher->status, $statuses, true)) {
            [$label] = erp_status('voucher', $voucher->status);
            throw new DomainException("Action impossible sur un bon « {$label} ».");
        }
    }
}
