<?php

namespace App\Livewire\Design;

use App\Enums\DesignJobStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\DesignJob;
use App\Models\DesignVersion;
use App\Models\User;
use App\Services\Collaboration\CommentService;
use App\Services\Design\DesignService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Graphisme')]
class Board extends Component
{
    use InteractsWithToasts, WithFileUploads;

    #[Url(as: 'vue')]
    public string $view = 'mine';

    public ?int $jobId = null;

    public $file = null;

    public string $versionComment = '';

    public string $note = '';

    public string $reason = '';

    public string $report = '';

    public ?int $assigneeId = null;

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            'new' => 'Nouvelles',
            'mine' => 'Mes travaux',
            'urgent' => 'Urgences',
            'in_progress' => 'En cours',
            'correction' => 'Corrections',
            'proof' => 'BAT en attente',
            'done' => 'Terminées',
            'blocked' => 'Bloquées',
        ];
    }

    private function baseQuery(): Builder
    {
        return DesignJob::query()->with('order.client', 'designer', 'latestVersion')
            ->whereHas('order', fn (Builder $q) => $q->visibleTo(auth()->user()));
    }

    private function queryFor(string $tab): Builder
    {
        $query = $this->baseQuery();
        $active = [DesignJobStatus::SentToPrint, DesignJobStatus::Cancelled];

        return match ($tab) {
            'new' => $query->where('status', DesignJobStatus::ToDesign)->whereNull('designer_id'),
            'mine' => $query->where('designer_id', auth()->id())->whereNotIn('status', $active),
            'urgent' => $query->whereHas('order', fn (Builder $q) => $q->whereIn('priority', ['urgent', 'high']))->whereNotIn('status', $active),
            'in_progress' => $query->whereIn('status', [DesignJobStatus::InProgress, DesignJobStatus::Proposal]),
            'correction' => $query->where('status', DesignJobStatus::Correction),
            'proof' => $query->where('status', DesignJobStatus::Proof),
            'done' => $query->whereIn('status', [DesignJobStatus::Validated, DesignJobStatus::SentToPrint])->where('updated_at', '>=', now()->subDays(30)),
            'blocked' => $query->where('status', DesignJobStatus::Blocked),
            default => $query,
        };
    }

    /**
     * @return Collection<int, DesignJob>
     */
    #[Computed]
    public function jobs(): Collection
    {
        return $this->queryFor($this->view)->get()->sortBy(fn (DesignJob $job) => [
            ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3][$job->order->priority->value],
            $job->order->due_at?->timestamp ?? PHP_INT_MAX,
        ])->values();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return collect(array_keys($this->tabs()))->mapWithKeys(fn (string $tab): array => [$tab => $this->queryFor($tab)->count()])->all();
    }

    #[Computed]
    public function job(): ?DesignJob
    {
        return $this->jobId ? $this->baseQuery()->with('versions.uploader', 'proofs', 'order.items', 'order.files', 'order.comments.user')->find($this->jobId) : null;
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function designers(): Collection
    {
        return User::permission('design.work')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function open(int $id): void
    {
        $this->jobId = $id;
        $this->reset('file', 'versionComment', 'note', 'reason', 'report');
        $this->assigneeId = $this->job?->designer_id;
    }

    public function assign(DesignService $design): void
    {
        $this->authorize('design.update');
        abort_unless(auth()->user()->can('design.approve') || $this->assigneeId === auth()->id(), 403);
        $designer = User::query()->findOrFail($this->assigneeId);

        $this->attempt(fn () => $design->assign($this->job, $designer, auth()->user()), "Travail assigné à {$designer->name}.");
        unset($this->job, $this->jobs);
    }

    public function takeAndStart(DesignService $design): void
    {
        $this->authorize('design.work');
        $job = $this->job;

        $this->attempt(function () use ($design, $job) {
            if (! $job->designer_id) {
                $design->assign($job, auth()->user(), auth()->user());
            }

            return $design->start($job, auth()->user());
        }, 'Conception démarrée.');
        unset($this->job, $this->jobs);
    }

    public function uploadVersion(DesignService $design): void
    {
        $this->authorize('design.work');
        $this->validate(['file' => ['required', 'file', 'max:204800'], 'versionComment' => ['nullable', 'string', 'max:1000']], [], ['file' => 'fichier']);

        $this->attempt(fn () => $design->uploadVersion($this->job, $this->file, auth()->user(), $this->versionComment ?: null), 'Nouvelle version déposée.');
        $this->reset('file', 'versionComment');
        unset($this->job, $this->jobs);
    }

    public function generateProof(int $versionId, DesignService $design): void
    {
        $this->authorize('proofs.create');
        $version = DesignVersion::query()->where('design_job_id', $this->jobId)->findOrFail($versionId);

        $this->attempt(fn () => $design->generateProof($this->job, $version, auth()->user()), 'BAT généré et soumis à validation.');
        unset($this->job, $this->jobs);
    }

    public function requestCorrection(DesignService $design): void
    {
        $this->validate(['note' => ['required', 'string', 'max:2000']], [], ['note' => 'commentaire']);
        $this->attempt(fn () => $design->requestCorrection($this->job, auth()->user(), $this->note), 'Correction demandée.');
        $this->reset('note');
        unset($this->job, $this->jobs);
    }

    public function askInformation(CommentService $comments): void
    {
        $this->validate(['note' => ['required', 'string', 'max:2000']], [], ['note' => 'question']);
        $salesRep = $this->job->order->salesRep;
        $comments->post($this->job->order, auth()->user(), 'Demande d’informations'.($salesRep ? " @{$salesRep->username}" : '')." : {$this->note}");
        $this->reset('note');
        $this->toast('Question transmise au commercial.');
        unset($this->job);
    }

    public function block(): void
    {
        $this->reportIssue(false);
    }

    public function withdrawIntervention(): void
    {
        $this->reportIssue(true);
    }

    private function reportIssue(bool $withdraw): void
    {
        $design = app(DesignService::class);
        $this->validate(['reason' => ['required', 'string', 'max:255'], 'report' => ['required', 'string', 'max:5000']], [], ['reason' => 'motif', 'report' => 'rapport']);

        $this->attempt(
            fn () => $withdraw ? $design->withdraw($this->job, auth()->user(), $this->reason, $this->report) : $design->block($this->job, auth()->user(), $this->reason, $this->report),
            $withdraw ? 'Intervention annulée.' : 'Impossibilité signalée.',
        );
        $this->modal('block')->close();
        $this->reset('reason', 'report');
        unset($this->job, $this->jobs);
    }

    public function render()
    {
        return view('livewire.design.board', ['tabs' => $this->tabs()]);
    }
}
