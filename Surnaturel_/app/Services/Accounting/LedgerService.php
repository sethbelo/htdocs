<?php

namespace App\Services\Accounting;

use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Services\Support\NumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Passation des écritures en partie double (contrôle de l'équilibre débit/crédit).
 */
class LedgerService
{
    public function __construct(private NumberGenerator $numbers) {}

    /**
     * @param  list<array{account: string|Account, label?: string, debit?: float, credit?: float, client_id?: int|null, supplier_id?: int|null}>  $lines
     */
    public function post(string $journalCode, string $description, array $lines, ?Model $source = null, ?int $siteId = null, ?Carbon $date = null, ?string $reference = null): JournalEntry
    {
        $lines = array_values(array_filter($lines, fn (array $line): bool => round((float) ($line['debit'] ?? 0), 2) != 0.0 || round((float) ($line['credit'] ?? 0), 2) != 0.0));

        $totalDebit = round(array_sum(array_map(fn (array $line): float => (float) ($line['debit'] ?? 0), $lines)), 2);
        $totalCredit = round(array_sum(array_map(fn (array $line): float => (float) ($line['credit'] ?? 0), $lines)), 2);

        if ($lines === [] || abs($totalDebit - $totalCredit) > 0.009) {
            throw new BusinessRuleException("Écriture déséquilibrée ({$description}) : débit {$totalDebit} ≠ crédit {$totalCredit}.");
        }

        return DB::transaction(function () use ($journalCode, $description, $lines, $source, $siteId, $date, $reference, $totalDebit, $totalCredit): JournalEntry {
            $entry = JournalEntry::query()->create([
                'number' => $this->numbers->next(NumberGenerator::JournalEntry, $date),
                'journal_id' => Journal::byCode($journalCode)->id,
                'entry_date' => ($date ?? now())->toDateString(),
                'reference' => $reference,
                'description' => $description,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'site_id' => $siteId,
                'status' => 'posted',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $account = $line['account'] instanceof Account ? $line['account'] : Account::byCode($line['account']);

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'label' => $line['label'] ?? $description,
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                    'client_id' => $line['client_id'] ?? null,
                    'supplier_id' => $line['supplier_id'] ?? null,
                ]);
            }

            return $entry;
        });
    }
}
