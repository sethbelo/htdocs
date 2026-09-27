<?php

namespace App\Actions\Accounting;

use App\Enums\DocumentType;
use App\Models\BankTransaction;
use App\Models\Document;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SynchronizeAccounting
{
    public function __construct(private readonly PostJournalEntry $postEntry) {}

    public function invoice(Document $document): ?JournalEntry
    {
        if ($document->type !== DocumentType::Invoice) {
            return null;
        }

        $baseTotal = round((float) $document->base_total, 2);
        $baseTax = round((float) $document->tax_total * (float) $document->exchange_rate, 2);
        $baseRevenue = round($baseTotal - $baseTax, 2);

        return $this->post(
            source: $document,
            journalCode: 'VE',
            date: $document->issue_date->toDateString(),
            description: "Facture {$document->number}",
            lines: [
                $this->line($document->company_id, '411', "Client · {$document->number}", $baseTotal, 0, $document->contact_id, $document->currency_code, (float) $document->total),
                $this->line($document->company_id, '706', "Ventes · {$document->number}", 0, $baseRevenue, $document->contact_id),
                ...($baseTax > 0 ? [$this->line($document->company_id, '4431', "TVA facturée · {$document->number}", 0, $baseTax, $document->contact_id)] : []),
            ],
        );
    }

    public function payment(Payment $payment): JournalEntry
    {
        return DB::transaction(function () use ($payment): JournalEntry {
            $payment->loadMissing('allocations.document');

            foreach ($payment->allocations as $allocation) {
                $this->invoice($allocation->document);
            }

            $account = $this->resolveFinancialAccount(
                $payment->company_id,
                $payment->financial_account_id,
                $payment->account_type,
                $payment->currency_code,
            );

            if ($account && $payment->financial_account_id !== $account->id) {
                $payment->update(['financial_account_id' => $account->id]);
            }

            $baseAmount = round((float) $payment->amount * (float) $payment->exchange_rate, 2);
            $treasuryCode = $this->treasuryAccountCode($account?->type ?? $payment->account_type);
            $entry = $this->post(
                source: $payment,
                journalCode: ($account?->type ?? $payment->account_type) === 'cash' ? 'CA' : 'BQ',
                date: $payment->paid_at->toDateString(),
                description: "Encaissement {$payment->reference}",
                lines: [
                    $this->line($payment->company_id, $treasuryCode, "Encaissement {$payment->reference}", $baseAmount, 0, $payment->contact_id, $payment->currency_code, (float) $payment->amount),
                    $this->line($payment->company_id, '411', "Règlement client {$payment->reference}", 0, $baseAmount, $payment->contact_id),
                ],
            );

            if ($account) {
                $this->cashMovement($payment, $account, $payment->paid_at->toDateString(), $payment->reference, "Encaissement {$payment->reference}", (float) $payment->amount, (float) $payment->exchange_rate);
            }

            return $entry;
        });
    }

    public function expense(Expense $expense): JournalEntry
    {
        return DB::transaction(function () use ($expense): JournalEntry {
            $accountType = $this->paymentAccountType($expense->payment_method);
            $account = $this->resolveFinancialAccount($expense->company_id, $expense->financial_account_id, $accountType, $expense->currency_code);

            if ($account && $expense->financial_account_id !== $account->id) {
                $expense->update(['financial_account_id' => $account->id]);
            }

            $baseTotal = round((float) $expense->amount * (float) $expense->exchange_rate, 2);
            $baseTax = round((float) $expense->tax_amount * (float) $expense->exchange_rate, 2);
            $baseExpense = round($baseTotal - $baseTax, 2);
            $treasuryCode = $this->treasuryAccountCode($account?->type ?? $accountType);

            $entry = $this->post(
                source: $expense,
                journalCode: 'AC',
                date: $expense->expense_date->toDateString(),
                description: "Dépense {$expense->reference} · {$expense->description}",
                lines: [
                    $this->line($expense->company_id, $expense->account_code ?: '605', $expense->description, $baseExpense, 0, $expense->supplier_id),
                    ...($baseTax > 0 ? [$this->line($expense->company_id, '4452', "TVA récupérable · {$expense->reference}", $baseTax, 0, $expense->supplier_id)] : []),
                    $this->line($expense->company_id, $treasuryCode, "Décaissement {$expense->reference}", 0, $baseTotal, $expense->supplier_id, $expense->currency_code, (float) $expense->amount),
                ],
            );

            if ($account) {
                $this->cashMovement($expense, $account, $expense->expense_date->toDateString(), $expense->reference, $expense->description, -(float) $expense->amount, (float) $expense->exchange_rate);
            }

            return $entry;
        });
    }

    public function bankTransaction(BankTransaction $transaction, float $exchangeRate = 1): JournalEntry
    {
        $transaction->loadMissing('account');
        $baseAmount = round(abs((float) $transaction->amount) * $exchangeRate, 2);
        $treasuryCode = $this->treasuryAccountCode($transaction->account->type);
        $isIncoming = (float) $transaction->amount > 0;

        return $this->post(
            source: $transaction,
            journalCode: $transaction->account->type === 'cash' ? 'CA' : 'BQ',
            date: $transaction->transaction_date->toDateString(),
            description: $transaction->description,
            lines: $isIncoming
                ? [
                    $this->line($transaction->account->company_id, $treasuryCode, $transaction->description, $baseAmount, 0, null, $transaction->account->currency_code, abs((float) $transaction->amount)),
                    $this->line($transaction->account->company_id, '471', $transaction->description, 0, $baseAmount),
                ]
                : [
                    $this->line($transaction->account->company_id, '471', $transaction->description, $baseAmount, 0),
                    $this->line($transaction->account->company_id, $treasuryCode, $transaction->description, 0, $baseAmount, null, $transaction->account->currency_code, abs((float) $transaction->amount)),
                ],
        );
    }

    /**
     * Écriture générique pour les modules ERP (paie, bons, caisse, POS…).
     * $lines = [[code, libellé, débit, crédit, ?contact_id], ...]
     */
    public function postLines(Model $source, string $journalCode, string $date, string $description, array $lines, ?int $companyId = null): JournalEntry
    {
        $companyId ??= (int) $source->company_id;

        return $this->post(
            source: $source,
            journalCode: $journalCode,
            date: $date,
            description: $description,
            lines: array_map(fn (array $l) => $this->line($companyId, $l[0], $l[1], (float) $l[2], (float) $l[3], $l[4] ?? null), $lines),
        );
    }

    public function remove(Model $source): void
    {
        DB::transaction(function () use ($source): void {
            JournalEntry::query()->where('source_type', $source->getMorphClass())->where('source_id', $source->getKey())->delete();
            BankTransaction::query()->where('reconcilable_type', $source->getMorphClass())->where('reconcilable_id', $source->getKey())->delete();
        });
    }

    private function post(Model $source, string $journalCode, string $date, string $description, array $lines): JournalEntry
    {
        $companyId = (int) ($source->company_id ?? $source->account?->company_id);
        $journalId = $this->journalId($companyId, $journalCode);
        $prefix = match (true) {
            $source instanceof Document => 'FAC',
            $source instanceof Payment => 'ENC',
            $source instanceof Expense => 'DEP',
            default => strtoupper(class_basename($source)),
        };

        return $this->postEntry->handle([
            'company_id' => $companyId,
            'journal_id' => $journalId,
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'created_by' => auth()->id() ?? $source->created_by ?? null,
            'number' => sprintf('AUTO-%s-%06d', $prefix, $source->getKey()),
            'entry_date' => $date,
            'description' => $description,
        ], $lines);
    }

    private function line(int $companyId, string $code, string $label, float $debit, float $credit, ?int $contactId = null, ?string $currency = null, ?float $foreignAmount = null): array
    {
        return [
            'account_id' => $this->accountId($companyId, $code),
            'contact_id' => $contactId,
            'label' => $label,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'currency_code' => $currency,
            'foreign_amount' => $foreignAmount,
        ];
    }

    private function cashMovement(Model $source, FinancialAccount $account, string $date, string $reference, string $description, float $amount, float $exchangeRate): void
    {
        $movementAmount = $amount;
        $baseCurrency = DB::table('companies')->where('id', $account->company_id)->value('base_currency');

        if ($account->currency_code === $baseCurrency && $source->currency_code !== $baseCurrency) {
            $movementAmount = round($amount * $exchangeRate, 2);
        }

        BankTransaction::query()->updateOrCreate([
            'reconcilable_type' => $source->getMorphClass(),
            'reconcilable_id' => $source->getKey(),
        ], [
            'financial_account_id' => $account->id,
            'transaction_date' => $date,
            'reference' => $reference,
            'description' => $description,
            'amount' => $movementAmount,
            'status' => 'reconciled',
            'raw_data' => ['exchange_rate' => $exchangeRate, 'origin' => class_basename($source)],
        ]);
    }

    private function resolveFinancialAccount(int $companyId, ?int $accountId, string $type, string $currency): ?FinancialAccount
    {
        if ($accountId) {
            return FinancialAccount::query()->where('company_id', $companyId)->where('is_active', true)->findOrFail($accountId);
        }

        return FinancialAccount::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('type', $type)
            ->orderByRaw('currency_code = ? desc', [$currency])
            ->first();
    }

    private function paymentAccountType(?string $method): string
    {
        return match ($method) {
            'cash' => 'cash',
            'mobile_money' => 'mobile_money',
            default => 'bank',
        };
    }

    private function treasuryAccountCode(string $type): string
    {
        return match ($type) {
            'cash' => '571',
            'mobile_money' => '581',
            default => '521',
        };
    }

    private function journalId(int $companyId, string $code): int
    {
        $defaults = [
            'VE' => ['Journal des ventes', 'sales'],
            'AC' => ['Journal des achats', 'purchases'],
            'BQ' => ['Journal de banque', 'bank'],
            'CA' => ['Journal de caisse', 'cash'],
        ];
        [$name, $type] = $defaults[$code] ?? ['Opérations diverses', 'general'];
        $existing = DB::table('journals')->where('company_id', $companyId)->where('code', $code)->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('journals')->insertGetId([
            'company_id' => $companyId,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountId(int $companyId, string $code): int
    {
        $defaults = [
            '411' => ['Clients', '4', 'asset'],
            '4431' => ['TVA facturée', '4', 'liability'],
            '4452' => ['TVA récupérable', '4', 'asset'],
            '471' => ['Compte d’attente', '4', 'liability'],
            '521' => ['Banques', '5', 'asset'],
            '571' => ['Caisse', '5', 'asset'],
            '581' => ['Mobile Money', '5', 'asset'],
            '601' => ['Achats et charges', '6', 'expense'],
            '706' => ['Services vendus', '7', 'revenue'],
            '701' => ['Ventes de marchandises', '7', 'revenue'],
            '702' => ['Ventes de produits finis', '7', 'revenue'],
            '707' => ['Produits accessoires (locations)', '7', 'revenue'],
            '401' => ['Fournisseurs', '4', 'liability'],
            '421' => ['Personnel, rémunérations dues', '4', 'liability'],
            '422' => ['Personnel, avances et acomptes', '4', 'asset'],
            '431' => ['Sécurité sociale (CNSS)', '4', 'liability'],
            '447' => ['État, impôts retenus à la source (IPR)', '4', 'liability'],
            '602' => ['Achats de matières premières', '6', 'expense'],
            '605' => ['Autres achats', '6', 'expense'],
            '618' => ['Autres frais de transport', '6', 'expense'],
            '631' => ['Frais bancaires', '6', 'expense'],
            '638' => ['Autres charges externes', '6', 'expense'],
            '661' => ['Rémunérations directes versées au personnel', '6', 'expense'],
            '664' => ['Charges sociales', '6', 'expense'],
        ];
        [$name, $class, $type] = $defaults[$code] ?? ['Compte '.$code, substr($code, 0, 1), match (substr($code, 0, 1)) {
            '6' => 'expense', '7' => 'revenue', '5' => 'asset', default => 'liability'
        }];
        $existing = DB::table('chart_of_accounts')->where('company_id', $companyId)->where('code', $code)->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('chart_of_accounts')->insertGetId([
            'company_id' => $companyId,
            'code' => $code,
            'name' => $name,
            'class' => $class,
            'type' => $type,
            'is_system' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
