<?php

namespace App\Livewire\Cash;

use App\Livewire\Concerns\ScopesToSite;
use App\Models\CashSession;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Sessions de caisse')]
class Sessions extends Component
{
    use ScopesToSite, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public ?int $selectedId = null;

    public function show(int $id): void
    {
        $this->selectedId = $id;
        $this->modal('session-detail')->show();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.cash.sessions', [
            'sessions' => CashSession::query()
                ->with('register.site', 'cashier', 'closer')
                ->whereHas('register', fn ($q) => $q->whereIn('site_id', $this->siteId ? [$this->siteId] : $user->accessibleSiteIds()))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest('opened_at')
                ->paginate(20),
            'selected' => $this->selectedId ? CashSession::query()->with('movements.user', 'movements.authorizer', 'register')->find($this->selectedId) : null,
        ]);
    }
}
