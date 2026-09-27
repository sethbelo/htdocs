<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

/**
 * Balance générale, grand livre, compte de résultat et bilan simplifié.
 */
class FinancialStatements
{
    /**
     * @return Collection<int, object{account: Account, debit: float, credit: float, balance: float}>
     */
    public function trialBalance(string $from, string $to): Collection
    {
        $totals = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereDate('journal_entries.entry_date', '>=', $from)->whereDate('journal_entries.entry_date', '<=', $to)
            ->groupBy('account_id')
            ->selectRaw('account_id, SUM(debit) as debit, SUM(credit) as credit')
            ->get()->keyBy('account_id');

        return Account::query()->orderBy('code')->get()
            ->filter(fn (Account $account): bool => $totals->has($account->id))
            ->map(fn (Account $account): object => (object) [
                'account' => $account,
                'debit' => round((float) $totals[$account->id]->debit, 2),
                'credit' => round((float) $totals[$account->id]->credit, 2),
                'balance' => round((float) $totals[$account->id]->debit - (float) $totals[$account->id]->credit, 2),
            ])->values();
    }

    /**
     * @return Collection<int, object{date: string, number: string, label: string, debit: float, credit: float, running: float}>
     */
    public function ledger(Account $account, string $from, string $to): Collection
    {
        $opening = (float) JournalEntryLine::query()->where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->whereDate('entry_date', '<', $from))
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as b')->value('b');

        $running = $opening;

        $lines = JournalEntryLine::query()->with('entry')->where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to))
            ->get()
            ->sortBy(fn (JournalEntryLine $line) => [$line->entry->entry_date->timestamp, $line->id])
            ->map(function (JournalEntryLine $line) use (&$running): object {
                $running += (float) $line->debit - (float) $line->credit;

                return (object) [
                    'date' => $line->entry->entry_date->format('d/m/Y'),
                    'number' => $line->entry->number,
                    'reference' => $line->entry->reference,
                    'label' => $line->label,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'running' => round($running, 2),
                ];
            })->values();

        return $lines->prepend((object) ['date' => '', 'number' => '', 'reference' => '', 'label' => 'Solde d’ouverture', 'debit' => 0.0, 'credit' => 0.0, 'running' => round($opening, 2)]);
    }

    /**
     * @return array{income: Collection<int, object>, expenses: Collection<int, object>, total_income: float, total_expenses: float, result: float}
     */
    public function incomeStatement(string $from, string $to): array
    {
        $balance = $this->trialBalance($from, $to);
        $income = $balance->filter(fn ($row): bool => $row->account->type === AccountType::Income)->map(fn ($row) => (object) ['account' => $row->account, 'amount' => -$row->balance])->values();
        $expenses = $balance->filter(fn ($row): bool => $row->account->type === AccountType::Expense)->map(fn ($row) => (object) ['account' => $row->account, 'amount' => $row->balance])->values();

        $totalIncome = round($income->sum('amount'), 2);
        $totalExpenses = round($expenses->sum('amount'), 2);

        return ['income' => $income, 'expenses' => $expenses, 'total_income' => $totalIncome, 'total_expenses' => $totalExpenses, 'result' => round($totalIncome - $totalExpenses, 2)];
    }

    /**
     * Bilan simplifié à une date : actifs / passifs + capitaux propres (résultat de l'exercice inclus).
     *
     * @return array{assets: Collection<int, object>, liabilities: Collection<int, object>, total_assets: float, total_liabilities: float, result: float}
     */
    public function balanceSheet(string $at): array
    {
        $balance = $this->trialBalance('1900-01-01', $at);
        $assets = $balance->filter(fn ($row): bool => $row->account->type === AccountType::Asset)->map(fn ($row) => (object) ['account' => $row->account, 'amount' => $row->balance])->values();
        $liabilities = $balance->filter(fn ($row): bool => in_array($row->account->type, [AccountType::Liability, AccountType::Equity], true))->map(fn ($row) => (object) ['account' => $row->account, 'amount' => -$row->balance])->values();
        $result = $this->incomeStatement('1900-01-01', $at)['result'];

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'total_assets' => round($assets->sum('amount'), 2),
            'total_liabilities' => round($liabilities->sum('amount') + $result, 2),
            'result' => $result,
        ];
    }
}
