<?php

namespace App\Livewire\Approvals;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\ApprovalRequest;
use App\Services\Approvals\ApprovalService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mes validations')]
class Inbox extends Component
{
    use InteractsWithToasts;

    public ?int $requestId = null;

    public string $comment = '';

    public string $reason = '';

    public function open(int $id): void
    {
        $this->requestId = $id;
        $this->reset('comment', 'reason');
        $this->modal('decision')->show();
    }

    public function approve(ApprovalService $approvals): void
    {
        $this->authorize('approvals.approve');
        if ($this->attempt(fn () => $approvals->approve(ApprovalRequest::query()->findOrFail($this->requestId), auth()->user(), $this->comment ?: null), 'Approuvé.')) {
            $this->modal('decision')->close();
        }
    }

    public function reject(ApprovalService $approvals): void
    {
        $this->authorize('approvals.approve');
        $this->validate(['reason' => ['required', 'string', 'max:255'], 'comment' => ['required', 'string', 'max:2000']], [], ['reason' => 'motif', 'comment' => 'commentaire']);

        if ($this->attempt(fn () => $approvals->reject(ApprovalRequest::query()->findOrFail($this->requestId), auth()->user(), $this->reason, $this->comment), 'Refusé.')) {
            $this->modal('decision')->close();
        }
    }

    public function render(ApprovalService $approvals)
    {
        return view('livewire.approvals.inbox', [
            'pending' => $approvals->pendingFor(auth()->user()),
            'mine' => ApprovalRequest::query()->with('workflow', 'currentStep', 'approvable', 'actions.user', 'actions.step')->where('requested_by', auth()->id())->latest()->limit(20)->get(),
            'history' => ApprovalRequest::query()->with('workflow', 'approvable', 'actions.user', 'actions.step')->whereHas('actions', fn ($q) => $q->where('user_id', auth()->id()))->latest()->limit(20)->get(),
            'current' => $this->requestId ? ApprovalRequest::query()->with('workflow.steps', 'currentStep', 'approvable', 'requester', 'actions.user', 'actions.step')->find($this->requestId) : null,
        ]);
    }
}
