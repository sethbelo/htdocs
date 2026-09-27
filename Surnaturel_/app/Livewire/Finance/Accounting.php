<?php

namespace App\Livewire\Finance;

use App\Enums\AccountType;
use App\Exports\TableExport;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Accounting\FinancialStatements;
use App\Services\Accounting\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Comptabilité')]
class Accounting extends Component
{
    use InteractsWithToasts, WithPagination;

    #[Url(as: 'vue')]
    public string $tab = 'journal';

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

    public function mount(): void
    {
        $this->from = $this->from ?: today()->startOfYear()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
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

    public function export(FinancialStatements $statements): BinaryFileResponse
    {
        $this->authorize('accounting.export');
        $rows = $statements->trialBalance($this->from, $this->to)->map(fn ($row): array => [$row->account->code, $row->account->name, $row->debit, $row->credit, $row->balance]);

        return Excel::download(new TableExport(['Compte', 'Intitulé', 'Débit', 'Crédit', 'Solde'], $rows, 'Balance'), "balance-{$this->from}-{$this->to}.xlsx");
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
                ->latest('entry_date')->latest('id')->paginate(20) : null,
            'ledger' => $this->tab === 'ledger' && $this->accountId ? $statements->ledger(Account::query()->findOrFail($this->accountId), $this->from, $this->to) : collect(),
            'trialBalance' => $this->tab === 'balance' ? $statements->trialBalance($this->from, $this->to) : collect(),
            'incomeStatement' => $this->tab === 'income' ? $statements->incomeStatement($this->from, $this->to) : null,
            'balanceSheet' => $this->tab === 'sheet' ? $statements->balanceSheet($this->to) : null,
            'unreconciled' => $this->tab === 'reconcile' ? JournalEntryLine::query()->with('entry', 'account')->where('is_reconciled', false)->whereHas('account', fn ($q) => $q->where('class', 5))->latest('id')->limit(100)->get() : collect(),
            'types' => AccountType::options(),
        ]);
    }

    public function reconcile(int $lineId): void
    {
        $this->authorize('accounting.validate');
        JournalEntryLine::query()->whereKey($lineId)->update(['is_reconciled' => true, 'reconciled_at' => now()]);
    }
}
