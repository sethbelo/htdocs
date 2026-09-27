<?php

namespace App\Livewire\Admin\Accounting;

use App\Models\Account;
use Livewire\Component;

class ChartOfAccounts extends Component
{
    public int $filterClass = 0; // 0 = all classes

    public string $search = '';

    public function render()
    {
        $eid = auth()->user()->establishment_id;

        $query = Account::where('establishment_id', $eid);

        if ($this->filterClass > 0) {
            $query->where('class', $this->filterClass);
        }

        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
            );
        }

        $accounts = $query->orderBy('code')->get();

        // Group by class
        $grouped = $accounts->groupBy('class');

        // Class summaries
        $classSummaries = [];
        foreach ($grouped as $class => $classAccounts) {
            $detailAccounts = $classAccounts->where('is_group', false);
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($detailAccounts as $a) {
                $balance = $a->balance;
                if ($balance > 0) {
                    $totalDebit += $balance;
                } else {
                    $totalCredit += abs($balance);
                }
            }
            $classSummaries[$class] = [
                'count' => $classAccounts->count(),
                'debit' => $totalDebit,
                'credit' => $totalCredit,
            ];
        }

        return view('livewire.admin.accounting.chart-of-accounts', compact('grouped', 'classSummaries'))
            ->layout('layouts.admin', ['title' => 'Plan Comptable OHADA']);
    }
}
