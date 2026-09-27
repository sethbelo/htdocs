<?php

namespace App\Livewire\Admin\Accounting;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Livewire\Component;
use Livewire\WithPagination;

class JournalEntries extends Component
{
    use WithPagination;

    public string $filterJournal = '';

    public string $filterStatus = '';

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterJournal()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function postEntry(string $id): void
    {
        $entry = JournalEntry::find($id);
        if (! $entry || $entry->status !== 'draft') {
            return;
        }

        $success = $entry->post(auth()->id());

        if (! $success) {
            session()->flash('error', 'Écriture déséquilibrée : Débit ≠ Crédit');
        }
    }

    public function cancelEntry(string $id): void
    {
        $entry = JournalEntry::find($id);
        if (! $entry || $entry->status !== 'draft') {
            return;
        }

        $entry->update(['status' => 'cancelled']);
    }

    public function render()
    {
        $eid = auth()->user()->establishment_id;

        $query = JournalEntry::where('establishment_id', $eid)
            ->with(['journal', 'lines.account', 'creator']);

        if ($this->filterJournal) {
            $query->whereHas('journal', fn ($q) => $q->where('code', $this->filterJournal));
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('entry_number', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%")
            );
        }

        $entries = $query->orderByDesc('entry_date')->orderByDesc('created_at')->paginate(15);
        $journals = Journal::where('establishment_id', $eid)->active()->get();

        // KPIs
        $totalEntries = JournalEntry::where('establishment_id', $eid)->count();
        $postedEntries = JournalEntry::where('establishment_id', $eid)->posted()->count();
        $draftEntries = JournalEntry::where('establishment_id', $eid)->draft()->count();
        $totalDebit = JournalEntryLine::whereHas('entry', fn ($q) => $q->where('establishment_id', $eid)->posted()
        )->sum('debit');

        return view('livewire.admin.accounting.journal-entries', compact(
            'entries', 'journals', 'totalEntries', 'postedEntries', 'draftEntries', 'totalDebit'
        ))->layout('layouts.admin', ['title' => 'Journal Comptable']);
    }
}
