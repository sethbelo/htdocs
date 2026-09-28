<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntryLine;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Analyse comptable « façon Power BI » : indicateurs clés avec comparaison à la période précédente,
 * évolution mensuelle, matrice comptes × mois, répartitions par compte, journal et siège.
 * Produits = classe 7 (crédit − débit), charges = classe 6 (débit − crédit), trésorerie = classe 5.
 */
class AccountingAnalytics
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $from, string $to, ?int $siteId = null, ?string $journalCode = null): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        $days = max(1, (int) $start->diffInDays($end) + 1);
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        $accounts = Account::query()->get()->keyBy('id');
        $daily = $this->dailyTotals($start, $end, $siteId, $journalCode);
        $previous = $this->dailyTotals($previousStart, $previousEnd, $siteId, $journalCode);

        $current = $this->summarize($daily, $accounts);
        $before = $this->summarize($previous, $accounts);

        $treasury = $this->balanceAt($end, $siteId, fn (Builder $q) => $q->where('accounts.class', 5));
        $treasuryBefore = $this->balanceAt($previousEnd, $siteId, fn (Builder $q) => $q->where('accounts.class', 5));
        $receivables = $this->balanceAt($end, $siteId, fn (Builder $q) => $q->where('accounts.code', 'like', '411%'));
        $payables = 0 - $this->balanceAt($end, $siteId, fn (Builder $q) => $q->where('accounts.code', 'like', '401%'));
        $vat = 0 - $this->balanceAt($end, $siteId, fn (Builder $q) => $q->where('accounts.code', 'like', '443%'))
            - $this->balanceAt($end, $siteId, fn (Builder $q) => $q->where('accounts.code', 'like', '445%'));

        $months = $this->months($start, $end);
        $monthly = $months->map(function (Carbon $month) use ($daily, $accounts, $end, $siteId): array {
            $rows = $daily->filter(fn ($row) => str_starts_with((string) $row->entry_date, $month->format('Y-m')));
            $sum = $this->summarize($rows, $accounts);
            $monthEnd = $month->copy()->endOfMonth()->min($end);

            return [
                'label' => ucfirst($month->translatedFormat('M Y')),
                'key' => $month->format('Y-m'),
                'revenue' => $sum['revenue'],
                'expenses' => $sum['expenses'],
                'result' => $sum['result'],
                'treasury' => $this->balanceAt($monthEnd, $siteId, fn (Builder $q) => $q->where('accounts.class', 5)),
            ];
        })->values();

        $matrix = $this->matrix($daily, $accounts, $months);

        return [
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString(), 'previous_from' => $previousStart->toDateString(), 'previous_to' => $previousEnd->toDateString()],
            'kpis' => [
                'revenue' => ['label' => 'Chiffre d’affaires', 'value' => $current['revenue'], 'previous' => $before['revenue'], 'good' => 'up'],
                'expenses' => ['label' => 'Charges', 'value' => $current['expenses'], 'previous' => $before['expenses'], 'good' => 'down'],
                'result' => ['label' => 'Résultat net', 'value' => $current['result'], 'previous' => $before['result'], 'good' => 'up'],
                'margin' => ['label' => 'Marge nette', 'value' => $current['revenue'] > 0 ? round($current['result'] / $current['revenue'] * 100, 1) : 0.0, 'previous' => $before['revenue'] > 0 ? round($before['result'] / $before['revenue'] * 100, 1) : 0.0, 'good' => 'up', 'percent' => true],
                'treasury' => ['label' => 'Trésorerie', 'value' => $treasury, 'previous' => $treasuryBefore, 'good' => 'up'],
                'receivables' => ['label' => 'Créances clients', 'value' => $receivables, 'previous' => null, 'good' => 'down'],
                'payables' => ['label' => 'Dettes fournisseurs', 'value' => $payables, 'previous' => null, 'good' => 'down'],
                'vat' => ['label' => 'TVA à reverser', 'value' => round($vat, 2), 'previous' => null, 'good' => 'down'],
            ],
            'monthly' => $monthly,
            'matrix' => $matrix,
            'revenueByAccount' => $current['byAccount']->where('type', AccountType::Income)->sortByDesc('amount')->values(),
            'expensesByAccount' => $current['byAccount']->where('type', AccountType::Expense)->sortByDesc('amount')->values(),
            'byJournal' => $this->byJournal($start, $end, $siteId, $journalCode),
            'bySite' => $this->bySite($start, $end, $journalCode),
        ];
    }

    /**
     * Totaux débit / crédit par compte et par jour.
     *
     * @return Collection<int, object{account_id: int, entry_date: string, debit: float, credit: float}>
     */
    private function dailyTotals(Carbon $start, Carbon $end, ?int $siteId, ?string $journalCode): Collection
    {
        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereDate('journal_entries.entry_date', '>=', $start->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $end->toDateString())
            ->when($siteId, fn (Builder $q) => $q->where('journal_entries.site_id', $siteId))
            ->when($journalCode, fn (Builder $q) => $q->whereIn('journal_entries.journal_id', Journal::query()->where('code', $journalCode)->select('id')))
            ->groupBy('journal_entry_lines.account_id', 'journal_entries.entry_date')
            ->selectRaw('journal_entry_lines.account_id as account_id, journal_entries.entry_date as entry_date, SUM(journal_entry_lines.debit) as debit, SUM(journal_entry_lines.credit) as credit')
            ->toBase()->get();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, Account>  $accounts
     * @return array{revenue: float, expenses: float, result: float, byAccount: Collection<int, array{code: string, name: string, type: AccountType, amount: float}>}
     */
    private function summarize(Collection $rows, Collection $accounts): array
    {
        $byAccount = $rows->groupBy('account_id')->map(function (Collection $items, int|string $accountId) use ($accounts): ?array {
            $account = $accounts->get((int) $accountId);
            if (! $account || ! in_array($account->type, [AccountType::Income, AccountType::Expense], true)) {
                return null;
            }
            $net = (float) $items->sum('debit') - (float) $items->sum('credit');

            return ['code' => $account->code, 'name' => $account->name, 'type' => $account->type, 'amount' => round($account->type === AccountType::Income ? -$net : $net, 2)];
        })->filter()->values();

        $revenue = round((float) $byAccount->where('type', AccountType::Income)->sum('amount'), 2);
        $expenses = round((float) $byAccount->where('type', AccountType::Expense)->sum('amount'), 2);

        return ['revenue' => $revenue, 'expenses' => $expenses, 'result' => round($revenue - $expenses, 2), 'byAccount' => $byAccount];
    }

    /**
     * Solde (débit − crédit) cumulé à une date des comptes filtrés.
     */
    private function balanceAt(Carbon $at, ?int $siteId, callable $accountFilter): float
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereDate('journal_entries.entry_date', '<=', $at->toDateString())
            ->when($siteId, fn (Builder $q) => $q->where('journal_entries.site_id', $siteId));
        $accountFilter($query);

        return round((float) $query->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) as balance')->value('balance'), 2);
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function months(Carbon $start, Carbon $end): Collection
    {
        $months = collect();
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end) && $months->count() < 24) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * Matrice comptes de gestion (lignes) × mois (colonnes), avec totaux.
     *
     * @param  Collection<int, object>  $daily
     * @param  Collection<int, Account>  $accounts
     * @param  Collection<int, Carbon>  $months
     * @return array{columns: list<string>, sections: list<array{title: string, rows: list<array{code: string, name: string, values: list<float>, total: float}>, totals: list<float>, total: float}>}
     */
    private function matrix(Collection $daily, Collection $accounts, Collection $months): array
    {
        $keys = $months->map(fn (Carbon $m) => $m->format('Y-m'))->all();
        $sections = [];

        foreach ([[AccountType::Income, 'Produits (classe 7)'], [AccountType::Expense, 'Charges (classe 6)']] as [$type, $title]) {
            $rows = $daily->filter(fn ($row) => $accounts->get((int) $row->account_id)?->type === $type)
                ->groupBy('account_id')
                ->map(function (Collection $items, int|string $accountId) use ($accounts, $keys, $type): array {
                    $account = $accounts->get((int) $accountId);
                    $values = array_map(function (string $key) use ($items, $type): float {
                        $monthRows = $items->filter(fn ($row) => str_starts_with((string) $row->entry_date, $key));
                        $net = (float) $monthRows->sum('debit') - (float) $monthRows->sum('credit');

                        return round($type === AccountType::Income ? -$net : $net, 2);
                    }, $keys);

                    return ['code' => $account->code, 'name' => $account->name, 'values' => $values, 'total' => round(array_sum($values), 2)];
                })->sortBy('code')->values()->all();

            $totals = array_map(fn (int $i): float => round(array_sum(array_column(array_column($rows, 'values'), $i)), 2), array_keys($keys));
            $sections[] = ['title' => $title, 'rows' => $rows, 'totals' => $totals, 'total' => round(array_sum($totals), 2)];
        }

        return ['columns' => $months->map(fn (Carbon $m) => ucfirst($m->translatedFormat('M y')))->all(), 'sections' => $sections];
    }

    /**
     * @return Collection<int, array{code: string, name: string, entries: int, amount: float}>
     */
    private function byJournal(Carbon $start, Carbon $end, ?int $siteId, ?string $journalCode): Collection
    {
        return Journal::query()
            ->when($journalCode, fn (Builder $q) => $q->where('code', $journalCode))
            ->withCount(['entries' => fn (Builder $q) => $this->scopeEntries($q, $start, $end, $siteId)])
            ->withSum(['entries' => fn (Builder $q) => $this->scopeEntries($q, $start, $end, $siteId)], 'total_debit')
            ->orderBy('code')->get()
            ->filter(fn (Journal $journal) => $journal->entries_count > 0)
            ->map(fn (Journal $journal) => ['code' => $journal->code, 'name' => $journal->name, 'entries' => (int) $journal->entries_count, 'amount' => round((float) $journal->entries_sum_total_debit, 2)])
            ->values();
    }

    private function scopeEntries(Builder $query, Carbon $start, Carbon $end, ?int $siteId): Builder
    {
        return $query->whereDate('entry_date', '>=', $start->toDateString())->whereDate('entry_date', '<=', $end->toDateString())->when($siteId, fn (Builder $q) => $q->where('site_id', $siteId));
    }

    /**
     * @return Collection<int, array{name: string, revenue: float, expenses: float, result: float}>
     */
    private function bySite(Carbon $start, Carbon $end, ?string $journalCode): Collection
    {
        $accounts = Account::query()->get()->keyBy('id');

        return Site::query()->orderBy('name')->get()->map(function (Site $site) use ($start, $end, $journalCode, $accounts): array {
            $sum = $this->summarize($this->dailyTotals($start, $end, $site->id, $journalCode), $accounts);

            return ['name' => $site->name, 'revenue' => $sum['revenue'], 'expenses' => $sum['expenses'], 'result' => $sum['result']];
        })->filter(fn (array $row) => $row['revenue'] != 0 || $row['expenses'] != 0)->values();
    }

    /**
     * Variation en % entre deux valeurs (null si non calculable).
     */
    public static function variation(float $current, ?float $previous): ?float
    {
        if ($previous === null || abs($previous) < 0.005) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }
}
