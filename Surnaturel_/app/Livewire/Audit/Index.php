<?php

namespace App\Livewire\Audit;

use App\Models\LoginHistory;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Title('Audit')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'vue')]
    public string $tab = 'activity';

    #[Url(as: 'module')]
    public string $logName = '';

    #[Url(as: 'utilisateur')]
    public ?int $userId = null;

    #[Url(as: 'du')]
    public ?string $from = null;

    public function render()
    {
        return view('livewire.audit.index', [
            'activities' => $this->tab === 'activity' ? Activity::query()->with('causer', 'subject')
                ->when($this->logName, fn ($q) => $q->where('log_name', $this->logName))
                ->when($this->userId, fn ($q) => $q->where('causer_type', 'user')->where('causer_id', $this->userId))
                ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
                ->latest()->paginate(30) : null,
            'logins' => $this->tab === 'logins' && auth()->user()->can('audit.view_logins') ? LoginHistory::query()->with('user')
                ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
                ->when($this->from, fn ($q) => $q->whereDate('logged_in_at', '>=', $this->from))
                ->latest('logged_in_at')->paginate(30) : null,
            'logNames' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
