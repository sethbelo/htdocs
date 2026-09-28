<?php

namespace App\Livewire\Finance;

use App\Enums\AccountType;
use App\Exports\TableExport;
use App\Exports\WorkbookExport;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Site;
use App\Services\Accounting\AccountingAnalytics;
use App\Services\Accounting\FinancialStatements;
use App\Services\Accounting\LedgerService;
use App\Services\Printing\PdfRenderer;
use App\Services\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Comptabilité')]
class Accounting extends Component
{
    use InteractsWithToasts, ScopesToSite, WithPagination;

    #[Url(as: 'vue')]
    public string $tab = 'dashboard';

    public string $preset = 'year';

    #[Url(as: 'du')]
    public string $from = '';

    #[Url(as: 'au')]
    public string $to = '';

    #[Url(as: 'journal')]
    public string $journalCode = '';

    public ?int $accountId = null;

    /** @var array<string, mixed> */
    public array $entry = ['journal' => 'OD', 'date' => '', 'description' => '', 'reference' => ''];

    /** @var list<array<string, string>> */
    public array $lines = [];

    /** @var array<string, string> */
    public array $newAccount = ['code' => '', 'name' => '', 'type' => 'expense'];

    /** @var array<string, string> Onglets de l'écran. */
    public const TABS = [
        'dashboard' => 'Tableau de bord', 'journal' => 'Journaux', 'ledger' => 'Grand livre', 'balance' => 'Balance',
        'income' => 'Compte de résultat', 'sheet' => 'Bilan', 'reconcile' => 'Rapprochement', 'chart' => 'Plan comptable',
    ];

    /** @var array<string, string> Périodes rapides (segments « slicers »). */
    public const PRESETS = ['month' => 'Ce mois', 'last_month' => 'Mois dernier', 'quarter' => 'Trimestre', 'year' => 'Année', 'last12' => '12 derniers mois'];

    public function mount(): void
    {
        $this->from = $this->from ?: today()->startOfYear()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
        $this->preset = $this->from === today()->startOfYear()->toDateString() && $this->to === today()->toDateString() ? 'year' : 'custom';
    }

    public function applyPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'month' => [today()->startOfMonth(), today()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'quarter' => [today()->firstOfQuarter(), today()],
            'last12' => [today()->subMonths(11)->startOfMonth(), today()],
            default => [today()->startOfYear(), today()],
        };
        $this->preset = array_key_exists($preset, self::PRESETS) ? $preset : 'year';
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        unset($this->analytics);
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
    }

    public function updatedTo(): void
    {
        $this->preset = 'custom';
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function analytics(): array
    {
        return app(AccountingAnalytics::class)->build($this->from, $this->to, $this->siteId, $this->journalCode ?: null);
    }

    /**
     * Configurations Chart.js des visuels du tableau de bord.
     *
     * @return array<string, array<string, mixed>>
     */
    private function charts(array $a): array
    {
        $palette = ['#DB0D17', '#53BDC5', '#FFCE34', '#134A63', '#8b5cf6', '#10b981', '#f97316', '#64748b', '#ec4899', '#0ea5e9'];
        $expenses = $a['expensesByAccount']->take(8);

        return [
            'trend' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $a['monthly']->pluck('label'),
                    'datasets' => [
                        ['type' => 'line', 'label' => 'Résultat', 'data' => $a['monthly']->pluck('result'), 'borderColor' => '#134A63', 'backgroundColor' => '#134A63', 'tension' => .35, 'pointRadius' => 3, 'order' => 0],
                        ['label' => 'Produits', 'data' => $a['monthly']->pluck('revenue'), 'backgroundColor' => '#53BDC5', 'borderRadius' => 6, 'order' => 1],
                        ['label' => 'Charges', 'data' => $a['monthly']->pluck('expenses'), 'backgroundColor' => '#DB0D17', 'borderRadius' => 6, 'order' => 1],
                    ],
                ],
                'options' => ['maintainAspectRatio' => false, 'interaction' => ['mode' => 'index', 'intersect' => false], 'plugins' => ['legend' => ['position' => 'bottom']]],
            ],
            'treasury' => [
                'type' => 'line',
                'data' => ['labels' => $a['monthly']->pluck('label'), 'datasets' => [['label' => 'Trésorerie fin de mois', 'data' => $a['monthly']->pluck('treasury'), 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16,185,129,.15)', 'fill' => true, 'tension' => .35]]],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
            ],
            'expenses' => [
                'type' => 'doughnut',
                'data' => ['labels' => $expenses->map(fn ($r) => $r['code'].' '.Str::limit($r['name'], 22)), 'datasets' => [['data' => $expenses->pluck('amount'), 'backgroundColor' => $palette, 'borderWidth' => 0]]],
                'options' => ['maintainAspectRatio' => false, 'cutout' => '62%', 'plugins' => ['legend' => ['position' => 'right', 'labels' => ['boxWidth' => 10]]]],
            ],
            'revenue' => [
                'type' => 'bar',
                'data' => ['labels' => $a['revenueByAccount']->map(fn ($r) => $r['code'].' '.Str::limit($r['name'], 18)), 'datasets' => [['label' => 'Produits', 'data' => $a['revenueByAccount']->pluck('amount'), 'backgroundColor' => '#53BDC5', 'borderRadius' => 6]]],
                'options' => ['maintainAspectRatio' => false, 'indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]],
            ],
            'sites' => [
                'type' => 'bar',
                'data' => ['labels' => $a['bySite']->pluck('name'), 'datasets' => [
                    ['label' => 'Produits', 'data' => $a['bySite']->pluck('revenue'), 'backgroundColor' => '#53BDC5', 'borderRadius' => 6],
                    ['label' => 'Charges', 'data' => $a['bySite']->pluck('expenses'), 'backgroundColor' => '#DB0D17', 'borderRadius' => 6],
                ]],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]],
            ],
        ];
    }

    public function newEntry(): void
    {
        $this->authorize('accounting.create');
        $this->entry = ['journal' => 'OD', 'date' => today()->toDateString(), 'description' => '', 'reference' => ''];
        $this->lines = [['account_id' => '', 'label' => '', 'debit' => '', 'credit' => ''], ['account_id' => '', 'label' => '', 'debit' => '', 'credit' => '']];
        $this->modal('entry')->show();
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => '', 'label' => '', 'debit' => '', 'credit' => ''];
    }

    public function saveEntry(LedgerService $ledger): void
    {
        $this->authorize('accounting.create');
        $this->validate([
            'entry.journal' => ['required', 'exists:journals,code'],
            'entry.date' => ['required', 'date'],
            'entry.description' => ['required', 'string', 'max:255'],
            'entry.reference' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ], [], ['lines.*.account_id' => 'compte']);

        $lines = array_map(fn (array $line): array => [
            'account' => Account::query()->findOrFail($line['account_id']),
            'label' => $line['label'] ?: $this->entry['description'],
            'debit' => (float) $line['debit'],
            'credit' => (float) $line['credit'],
        ], $this->lines);

        if ($this->attempt(fn () => $ledger->post($this->entry['journal'], $this->entry['description'], $lines, null, auth()->user()->default_site_id, Carbon::parse($this->entry['date']), $this->entry['reference'] ?: null), 'Écriture passée.')) {
            $this->modal('entry')->close();
        }
    }

    public function saveAccount(): void
    {
        $this->authorize('accounting.update');
        $data = $this->validate([
            'newAccount.code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'newAccount.name' => ['required', 'string', 'max:255'],
            'newAccount.type' => ['required', Rule::enum(AccountType::class)],
        ])['newAccount'];

        Account::query()->create($data + ['class' => (int) substr($data['code'], 0, 1)]);
        $this->reset('newAccount');
        $this->toast('Compte ajouté au plan comptable.');
    }

    /**
     * Tableaux de l'onglet courant : [titre, en-têtes, lignes, index des lignes de total].
     *
     * @return list<array{0: string, 1: list<string>, 2: Collection<int, array<int, mixed>>, 3: list<int>}>
     */
    private function exportTables(FinancialStatements $statements): array
    {
        $a = fn () => $this->analytics;

        return match ($this->tab) {
            'dashboard' => (function () use ($a): array {
                $data = $a();
                $kpis = collect($data['kpis'])->map(fn (array $k) => [$k['label'], (float) $k['value'], $k['previous'] === null ? null : (float) $k['previous'], AccountingAnalytics::variation((float) $k['value'], $k['previous'] === null ? null : (float) $k['previous'])])->values();
                $monthly = $data['monthly']->map(fn (array $m) => [$m['label'], $m['revenue'], $m['expenses'], $m['result'], $m['revenue'] > 0 ? round($m['result'] / $m['revenue'] * 100, 1) : 0.0, $m['treasury']]);
                $monthly->push(['TOTAL', (float) $monthly->sum(1), (float) $monthly->sum(2), (float) $monthly->sum(3), null, null]);
                $matrixRows = collect();
                $totalIndexes = [];
                foreach ($data['matrix']['sections'] as $section) {
                    foreach ($section['rows'] as $row) {
                        $matrixRows->push([$row['code'], $row['name'], ...$row['values'], $row['total']]);
                    }
                    $totalIndexes[] = $matrixRows->count();
                    $matrixRows->push(['', 'Total '.$section['title'], ...$section['totals'], $section['total']]);
                }

                return [
                    ['Indicateurs', ['Indicateur', 'Période', 'Période précédente', 'Variation %'], $kpis, []],
                    ['Évolution mensuelle', ['Mois', 'Produits', 'Charges', 'Résultat', 'Marge %', 'Trésorerie fin de mois'], $monthly, [$monthly->count() - 1]],
                    ['Matrice comptes x mois', ['Compte', 'Intitulé', ...$data['matrix']['columns'], 'Total'], $matrixRows, $totalIndexes],
                    ['Charges par compte', ['Compte', 'Intitulé', 'Montant', 'Part %'], $data['expensesByAccount']->map(fn ($r) => [$r['code'], $r['name'], $r['amount'], round($r['amount'] / max(0.01, $data['kpis']['expenses']['value']) * 100, 1)]), []],
                    ['Produits par compte', ['Compte', 'Intitulé', 'Montant', 'Part %'], $data['revenueByAccount']->map(fn ($r) => [$r['code'], $r['name'], $r['amount'], round($r['amount'] / max(0.01, $data['kpis']['revenue']['value']) * 100, 1)]), []],
                    ['Par siège', ['Siège', 'Produits', 'Charges', 'Résultat'], $data['bySite']->map(fn ($r) => array_values($r)), []],
                    ['Par journal', ['Code', 'Journal', 'Écritures', 'Montant'], $data['byJournal']->map(fn ($r) => array_values($r)), []],
                ];
            })(),
            'journal' => [['Journal des écritures', ['Date', 'N°', 'Journal', 'Libellé', 'Référence', 'Compte', 'Intitulé', 'Débit', 'Crédit'],
                JournalEntry::query()->with('journal', 'lines.account')
                    ->whereDate('entry_date', '>=', $this->from)->whereDate('entry_date', '<=', $this->to)
                    ->when($this->journalCode, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('code', $this->journalCode)))
                    ->when($this->siteId, fn ($q) => $q->where('site_id', $this->siteId))
                    ->orderBy('entry_date')->orderBy('id')->get()
                    ->flatMap(fn (JournalEntry $e) => $e->lines->map(fn (JournalEntryLine $l) => [$e->entry_date->format('d/m/Y'), $e->number, $e->journal->code, $e->description, $e->reference, $l->account->code, $l->account->name, (float) $l->debit, (float) $l->credit])), []]],
            'ledger' => $this->accountId ? (function () use ($statements): array {
                $account = Account::query()->findOrFail($this->accountId);
                $rows = $statements->ledger($account, $this->from, $this->to)->map(fn ($l) => [$l->date, $l->number, $l->label, $l->debit, $l->credit, $l->running]);

                return [["Grand livre {$account->code}", ['Date', 'N°', 'Libellé', 'Débit', 'Crédit', 'Solde'], $rows, [0]]];
            })() : [],
            'income' => (function () use ($statements): array {
                $is = $statements->incomeStatement($this->from, $this->to);
                $rows = collect([['PRODUITS', '', null]])
                    ->concat($is['income']->map(fn ($r) => [$r->account->code, $r->account->name, $r->amount]))
                    ->push(['', 'Total produits', $is['total_income']])->push(['CHARGES', '', null])
                    ->concat($is['expenses']->map(fn ($r) => [$r->account->code, $r->account->name, $r->amount]))
                    ->push(['', 'Total charges', $is['total_expenses']])->push(['', 'RÉSULTAT NET', $is['result']])->values();
                $totals = $rows->keys()->filter(fn ($i) => in_array($rows[$i][1], ['Total produits', 'Total charges', 'RÉSULTAT NET'], true) || in_array($rows[$i][0], ['PRODUITS', 'CHARGES'], true))->values()->all();

                return [['Compte de résultat', ['Compte', 'Intitulé', 'Montant'], $rows, $totals]];
            })(),
            'sheet' => (function () use ($statements): array {
                $bs = $statements->balanceSheet($this->to);
                $rows = collect([['ACTIF', '', null]])
                    ->concat($bs['assets']->map(fn ($r) => [$r->account->code, $r->account->name, $r->amount]))
                    ->push(['', 'Total actif', $bs['total_assets']])->push(['PASSIF', '', null])
                    ->concat($bs['liabilities']->map(fn ($r) => [$r->account->code, $r->account->name, $r->amount]))
                    ->push(['', 'Résultat de l’exercice', $bs['result']])->push(['', 'Total passif', $bs['total_liabilities']])->values();
                $totals = $rows->keys()->filter(fn ($i) => in_array($rows[$i][1], ['Total actif', 'Total passif'], true) || in_array($rows[$i][0], ['ACTIF', 'PASSIF'], true))->values()->all();

                return [['Bilan au '.Carbon::parse($this->to)->format('d-m-Y'), ['Compte', 'Intitulé', 'Montant'], $rows, $totals]];
            })(),
            'chart' => [['Plan comptable', ['Code', 'Intitulé', 'Classe', 'Type'], Account::query()->orderBy('code')->get()->map(fn (Account $a) => [$a->code, $a->name, $a->class, $a->type->label()]), []]],
            default => (function () use ($statements): array {
                $rows = $statements->trialBalance($this->from, $this->to)->map(fn ($row): array => [$row->account->code, $row->account->name, $row->debit, $row->credit, $row->balance]);
                $rows->push(['', 'TOTAUX', (float) $rows->sum(2), (float) $rows->sum(3), (float) $rows->sum(4)]);

                return [['Balance générale', ['Compte', 'Intitulé', 'Débit', 'Crédit', 'Solde'], $rows, [$rows->count() - 1]]];
            })(),
        };
    }

    public function exportExcel(FinancialStatements $statements): ?BinaryFileResponse
    {
        $this->authorize('accounting.export');
        $tables = $this->exportTables($statements);

        if ($tables === []) {
            $this->toast('Sélectionnez un compte pour exporter le grand livre.', 'warning');

            return null;
        }

        $sheets = array_map(fn (array $t) => new TableExport($t[1], collect($t[2]), $t[0], $t[3]), $tables);

        return Excel::download(new WorkbookExport($sheets), "comptabilite-{$this->tab}-{$this->from}-{$this->to}.xlsx");
    }

    public function exportPdf(FinancialStatements $statements, PdfRenderer $renderer): ?StreamedResponse
    {
        $this->authorize('accounting.export');
        $tables = $this->exportTables($statements);

        if ($tables === []) {
            $this->toast('Sélectionnez un compte pour exporter le grand livre.', 'warning');

            return null;
        }

        $money = fn ($v) => Money::format($v);
        $kpis = $this->tab === 'dashboard'
            ? collect($this->analytics['kpis'])->take(5)->map(fn (array $k) => ['label' => $k['label'], 'value' => ($k['percent'] ?? false) ? $k['value'].' %' : $money($k['value'])])->values()->all()
            : [];
        $sections = array_map(fn (array $t) => ['title' => $t[0], 'headings' => $t[1], 'rows' => collect($t[2])->map(fn ($row) => array_map(fn ($cell) => $cell ?? '', $row))->all(), 'totals' => null], $tables);
        $wide = collect($tables)->max(fn (array $t) => count($t[1])) > 6;

        $pdf = $renderer->make('pdf.report', [
            'title' => $this->tab === 'dashboard' ? 'Tableau de bord comptable' : self::TABS[$this->tab],
            'from' => $this->from, 'to' => $this->to,
            'site' => $this->siteId ? Site::query()->find($this->siteId)?->name : 'Tous les sièges',
            'reference' => $this->journalCode ? 'Journal '.$this->journalCode : null,
            'kpis' => $kpis,
            'sections' => $sections,
        ], $wide ? 'landscape' : 'a4');

        return response()->streamDownload(fn () => print ($pdf->output()), "comptabilite-{$this->tab}-{$this->from}-{$this->to}.pdf");
    }

    public function render(FinancialStatements $statements)
    {
        $accounts = Account::query()->orderBy('code')->get();

        return view('livewire.finance.accounting', [
            'accounts' => $accounts,
            'journals' => Journal::query()->orderBy('code')->get(),
            'entries' => $this->tab === 'journal' ? JournalEntry::query()->with('journal', 'lines.account')
                ->whereDate('entry_date', '>=', $this->from)->whereDate('entry_date', '<=', $this->to)
                ->when($this->journalCode, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('code', $this->journalCode)))
                ->when($this->siteId, fn ($q) => $q->where('site_id', $this->siteId))
                ->latest('entry_date')->latest('id')->paginate(20) : null,
            'ledger' => $this->tab === 'ledger' && $this->accountId ? $statements->ledger(Account::query()->findOrFail($this->accountId), $this->from, $this->to) : collect(),
            'trialBalance' => $this->tab === 'balance' ? $statements->trialBalance($this->from, $this->to) : collect(),
            'incomeStatement' => $this->tab === 'income' ? $statements->incomeStatement($this->from, $this->to) : null,
            'balanceSheet' => $this->tab === 'sheet' ? $statements->balanceSheet($this->to) : null,
            'unreconciled' => $this->tab === 'reconcile' ? JournalEntryLine::query()->with('entry', 'account')->where('is_reconciled', false)->whereHas('account', fn ($q) => $q->where('class', 5))->latest('id')->limit(100)->get() : collect(),
            'types' => AccountType::options(),
            'charts' => $this->tab === 'dashboard' ? $this->charts($this->analytics) : [],
            'sites' => $this->accessibleSites(),
        ]);
    }

    public function reconcile(int $lineId): void
    {
        $this->authorize('accounting.validate');
        JournalEntryLine::query()->whereKey($lineId)->update(['is_reconciled' => true, 'reconciled_at' => now()]);
    }
}
