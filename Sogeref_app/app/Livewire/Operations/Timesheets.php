<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Feuilles de temps : saisie hebdomadaire par projet, soumission et approbation.
 */
#[Title('Feuilles de temps')]
class Timesheets extends Component
{
    use InteractsWithUi;

    #[Url(as: 'semaine')]
    public string $week = '';

    #[Url(as: 'collaborateur')]
    public ?int $userId = null;

    public array $grid = [];

    public ?int $addProjectId = null;

    public function mount(): void
    {
        $this->week = $this->week ?: now()->startOfWeek()->toDateString();
        $this->userId ??= auth()->id();
        $this->loadGrid();
    }

    public function updatedWeek(): void
    {
        $this->loadGrid();
    }

    public function updatedUserId(): void
    {
        $this->loadGrid();
    }

    public function shift(int $weeks): void
    {
        $this->week = Carbon::parse($this->week)->addWeeks($weeks)->startOfWeek()->toDateString();
        $this->loadGrid();
    }

    private function days()
    {
        $start = Carbon::parse($this->week)->startOfWeek();

        return collect(range(0, 5))->map(fn ($d) => $start->copy()->addDays($d)->toDateString());
    }

    private function loadGrid(): void
    {
        $entries = Timesheet::where('company_id', company_id())->where('user_id', $this->userId)->whereBetween('work_date', [$this->days()->first(), $this->days()->last().' 23:59:59'])->get();
        $this->grid = [];
        foreach ($entries as $e) {
            $key = (string) ($e->project_id ?? 0);
            $this->grid[$key][$e->work_date->toDateString()] = (float) ($this->grid[$key][$e->work_date->toDateString()] ?? 0) + (float) $e->hours;
        }
    }

    private function canEdit(): bool
    {
        return (int) $this->userId === auth()->id() || auth()->user()->canManage('timesheets');
    }

    public function addProject(): void
    {
        if ($this->addProjectId && ! isset($this->grid[(string) $this->addProjectId])) {
            $this->grid[(string) $this->addProjectId] = [];
        }
        $this->addProjectId = null;
    }

    public function save(bool $submit = false): void
    {
        abort_unless($this->canEdit(), 403);
        $this->validate(['grid.*.*' => ['nullable', 'numeric', 'between:0,16']], [], ['grid.*.*' => 'heures']);
        $days = $this->days();
        $locked = Timesheet::where('company_id', company_id())->where('user_id', $this->userId)->whereBetween('work_date', [$days->first(), $days->last().' 23:59:59'])->where('status', 'approved')->exists();
        if ($locked && ! auth()->user()->canManage('timesheets')) {
            $this->toast('Cette semaine est déjà approuvée.', 'error');

            return;
        }
        Timesheet::where('company_id', company_id())->where('user_id', $this->userId)->whereBetween('work_date', [$days->first(), $days->last().' 23:59:59'])->delete();
        foreach ($this->grid as $projectId => $values) {
            foreach ($values as $date => $hours) {
                if ((float) $hours > 0 && $days->contains($date)) {
                    Timesheet::create(['company_id' => company_id(), 'user_id' => $this->userId, 'project_id' => (int) $projectId ?: null, 'work_date' => $date, 'hours' => $hours, 'status' => $submit ? 'submitted' : 'draft', 'billable' => (int) $projectId > 0]);
                }
            }
        }
        $this->toast($submit ? 'Feuille de temps soumise pour approbation.' : 'Feuille de temps enregistrée.');
    }

    public function submit(): void
    {
        $this->save(true);
    }

    public function approve(): void
    {
        $this->authorizeModule('timesheets', 'manage');
        Timesheet::where('company_id', company_id())->where('user_id', $this->userId)->whereBetween('work_date', [$this->days()->first(), $this->days()->last().' 23:59:59'])->update(['status' => 'approved']);
        $this->toast('Semaine approuvée.');
    }

    public function render()
    {
        $cid = company_id();
        $days = $this->days();
        $statuses = Timesheet::where('company_id', $cid)->where('user_id', $this->userId)->whereBetween('work_date', [$days->first(), $days->last().' 23:59:59'])->pluck('status')->unique();

        return view('livewire.operations.timesheets', [
            'days' => $days->map(fn ($d) => Carbon::parse($d)),
            'projects' => Project::where('company_id', $cid)->whereIn('status', ['active', 'on_hold'])->orderBy('name')->get()->keyBy('id'),
            'users' => auth()->user()->canManage('timesheets') ? User::whereHas('companies', fn ($q) => $q->where('companies.id', $cid))->where('is_active', true)->orderBy('name')->get() : collect([auth()->user()]),
            'weekStatus' => $statuses->contains('approved') ? 'approved' : ($statuses->contains('submitted') ? 'submitted' : 'draft'),
            'pending' => auth()->user()->canManage('timesheets') ? Timesheet::with('user')->where('company_id', $cid)->where('status', 'submitted')->get()->groupBy('user_id') : collect(),
            'canEdit' => $this->canEdit(),
        ]);
    }
}
