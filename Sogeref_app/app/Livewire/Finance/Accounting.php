<?php

namespace App\Livewire\Finance;

use App\Actions\Accounting\PostJournalEntry;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\ReportService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Comptabilité SYSCOHADA : journaux, grand livre, balance, plan comptable
 * et saisie d'écritures d'opérations diverses.
 */
#[Title('Comptabilité')]
class Accounting extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url(as: 'onglet')]
    public string $tab = 'journal';

    #[Url(as: 'journal')]
    public ?int $journalId = null;

    public string $from = '';

    public string $to = '';

    public ?int $accountId = null;

    public string $search = '';

    // Saisie manuelle
    public bool $showEntry = false;

    public array $entry = [];

    // Plan comptable
    public bool $showAccount = false;

    public array $account = [];

    public ?int $expanded = null;

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    public function updating($name): void
    {
        if (in_array($name, ['journalId', 'from', 'to', 'search', 'accountId', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function newEntry(): void
    {
        abort_unless($this->allowed('accounting.post'), 403);
        $this->entry = ['journal_id' => Journal::where('company_id', company_id())->where('code', 'OD')->value('id'), 'entry_date' => now()->toDateString(), 'description' => '', 'lines' => [['account_id' => null, 'label' => '', 'debit' => '', 'credit' => ''], ['account_id' => null, 'label' => '', 'debit' => '', 'credit' => '']]];
        $this->showEntry = true;
    }

    public function addEntryLine(): void
    {
        $this->entry['lines'][] = ['account_id' => null, 'label' => $this->entry['description'] ?? '', 'debit' => '', 'credit' => ''];
    }

    public function saveEntry(PostJournalEntry $post): void
    {
        abort_unless($this->allowed('accounting.post'), 403);
        $this->validate(['entry.journal_id' => ['required', 'integer'], 'entry.entry_date' => ['required', 'date'], 'entry.description' => ['required', 'string', 'max:255'], 'entry.lines' => ['required', 'array', 'min:2'], 'entry.lines.*.account_id' => ['required', 'integer'], 'entry.lines.*.debit' => ['nullable', 'numeric', 'min:0'], 'entry.lines.*.credit' => ['nullable', 'numeric', 'min:0']], [], ['entry.description' => 'libellé', 'entry.lines.*.account_id' => 'compte']);
        try {
            $post->handle([
                'company_id' => company_id(), 'journal_id' => $this->entry['journal_id'], 'created_by' => auth()->id(),
                'number' => 'OD-'.now()->format('Y').'-'.str_pad((string) (JournalEntry::where('company_id', company_id())->count() + 1), 5, '0', STR_PAD_LEFT),
                'entry_date' => $this->entry['entry_date'], 'description' => $this->entry['description'],
            ], array_map(fn ($l) => ['account_id' => $l['account_id'], 'label' => $l['label'] ?: $this->entry['description'], 'debit' => (float) ($l['debit'] ?: 0), 'credit' => (float) ($l['credit'] ?: 0)], $this->entry['lines']));
        } catch (DomainException $e) {
            $this->addError('entry.lines', $e->getMessage());

            return;
        }
        $this->showEntry = false;
        $this->toast('Écriture comptabilisée.');
    }

    public function editAccount(?int $id = null): void
    {
        $this->authorizeModule('accounting', 'manage');
        $a = $id ? ChartOfAccount::where('company_id', company_id())->findOrFail($id) : null;
        $this->account = ['id' => $a?->id, 'code' => $a?->code ?? '', 'name' => $a?->name ?? '', 'type' => $a?->type ?? 'expense', 'is_active' => $a?->is_active ?? true];
        $this->showAccount = true;
    }

    public function saveAccount(): void
    {
        $this->authorizeModule('accounting', 'manage');
        $data = $this->validate(['account.code' => ['required', 'regex:/^[1-9][0-9]{1,7}$/', Rule::unique('chart_of_accounts', 'code')->where('company_id', company_id())->ignore($this->account['id'] ?? null)], 'account.name' => ['required', 'string', 'max:160'], 'account.type' => ['required', 'in:asset,liability,equity,revenue,expense'], 'account.is_active' => ['boolean']], [], ['account.code' => 'numéro de compte'])['account'];
        ChartOfAccount::updateOrCreate(['id' => $this->account['id'] ?? null], $data + ['company_id' => company_id(), 'class' => substr($data['code'], 0, 1)]);
        $this->showAccount = false;
        $this->toast('Compte enregistré.');
    }

    public function render(ReportService $reports)
    {
        $cid = company_id();
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();
        $data = [
            'journals' => Journal::where('company_id', $cid)->orderBy('code')->get(),
            'accounts' => ChartOfAccount::where('company_id', $cid)->orderBy('code')->get(),
        ];

        if ($this->tab === 'journal') {
            $data['entries'] = JournalEntry::with('journal', 'lines.account')->where('company_id', $cid)
                ->when($this->journalId, fn ($q) => $q->where('journal_id', $this->journalId))
                ->whereBetween('entry_date', [$from, $to])
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%")))
                ->latest('entry_date')->latest('id')->paginate(20);
        } elseif ($this->tab === 'grand-livre') {
            $this->accountId ??= $data['accounts']->firstWhere('code', '571')?->id;
            $opening = JournalLine::where('account_id', $this->accountId)->whereHas('entry', fn ($q) => $q->where('entry_date', '<', $from))->selectRaw('SUM(debit) - SUM(credit) as s')->value('s');
            $lines = JournalLine::with('entry.journal', 'contact')->where('account_id', $this->accountId)->whereHas('entry', fn ($q) => $q->whereBetween('entry_date', [$from, $to]))
                ->get()->sortBy(fn ($l) => $l->entry->entry_date->format('Ymd').str_pad((string) $l->id, 10, '0', STR_PAD_LEFT))->values();
            $running = (float) $opening;
            $data['ledger'] = $lines->map(function ($l) use (&$running) {
                $running += (float) $l->debit - (float) $l->credit;
                $l->running = $running;

                return $l;
            });
            $data['opening'] = (float) $opening;
        } elseif ($this->tab === 'balance') {
            $data['report'] = $reports->build('balance', $from, $to);
        }

        $totals = JournalLine::whereHas('entry', fn ($q) => $q->where('company_id', $cid)->whereBetween('entry_date', [$from, $to]));
        $data['kpis'] = [
            'entries' => JournalEntry::where('company_id', $cid)->whereBetween('entry_date', [$from, $to])->count(),
            'debit' => (float) (clone $totals)->sum('debit'),
            'credit' => (float) (clone $totals)->sum('credit'),
            'result' => (float) (clone $totals)->whereHas('account', fn ($q) => $q->where('class', '7'))->selectRaw('SUM(credit) - SUM(debit) as s')->value('s') - (float) (clone $totals)->whereHas('account', fn ($q) => $q->where('class', '6'))->selectRaw('SUM(debit) - SUM(credit) as s')->value('s'),
        ];

        return view('livewire.finance.accounting', $data);
    }
}
