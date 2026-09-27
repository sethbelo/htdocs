<?php

namespace App\Livewire\Admin\Accounting;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Livewire\Component;

class GeneralLedger extends Component
{
    public string $filterAccount = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $eid = auth()->user()->establishment_id;
        $accounts = Account::where('establishment_id', $eid)
            ->where('is_group', false)
            ->orderBy('code')
            ->get();

        $ledgerData = [];

        $query = Account::where('establishment_id', $eid)
            ->where('is_group', false)
            ->orderBy('code');

        if ($this->filterAccount) {
            $query->where('id', $this->filterAccount);
        }

        $filteredAccounts = $query->get();

        foreach ($filteredAccounts as $account) {
            $linesQuery = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('entry', fn ($q) => $q->where('status', 'posted'));

            if ($this->dateFrom) {
                $linesQuery->whereHas('entry', fn ($q) => $q->where('entry_date', '>=', $this->dateFrom));
            }
            if ($this->dateTo) {
                $linesQuery->whereHas('entry', fn ($q) => $q->where('entry_date', '<=', $this->dateTo));
            }

            $lines = $linesQuery->with('entry.journal')->orderBy('created_at')->get();

            if ($lines->isNotEmpty()) {
                $totalDebit = $lines->sum('debit');
                $totalCredit = $lines->sum('credit');
                $balance = in_array($account->type, ['asset', 'expense'])
                    ? $totalDebit - $totalCredit
                    : $totalCredit - $totalDebit;

                $ledgerData[] = [
                    'account' => $account,
                    'lines' => $lines,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'balance' => $balance,
                ];
            }
        }

        // Grand totals
        $grandDebit = collect($ledgerData)->sum('total_debit');
        $grandCredit = collect($ledgerData)->sum('total_credit');

        return view('livewire.admin.accounting.general-ledger', compact(
            'accounts', 'ledgerData', 'grandDebit', 'grandCredit'
        ))->layout('layouts.admin', ['title' => 'Grand Livre']);
    }
}
