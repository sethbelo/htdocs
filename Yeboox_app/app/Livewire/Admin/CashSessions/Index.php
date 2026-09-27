<?php

namespace App\Livewire\Admin\CashSessions;

use App\Models\CashSession;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $sessions = CashSession::query()
            ->whereHas('cashRegister', fn ($q) => $q->where('establishment_id', auth()->user()->establishment_id))
            ->with(['cashRegister', 'user'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('opened_at')
            ->paginate(15);

        $openCount = CashSession::query()
            ->whereHas('cashRegister', fn ($q) => $q->where('establishment_id', auth()->user()->establishment_id))
            ->open()->count();

        return view('livewire.admin.cash-sessions.index', compact('sessions', 'openCount'))
            ->layout('layouts.admin', ['title' => 'Sessions de caisse', 'breadcrumb' => 'Caisses']);
    }
}
