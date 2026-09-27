<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Component;

/**
 * Projet : tâches en kanban (glisser-déposer), heures saisies et budget.
 */
class ProjectShow extends Component
{
    use InteractsWithUi;

    public Project $project;

    public bool $showTask = false;

    public ?int $editing = null;

    public array $task = [];

    public string $status = 'active';

    public function mount(Project $project): void
    {
        abort_unless($project->company_id === company_id(), 404);
        $this->project = $project;
        $this->status = $project->status;
    }

    public function move(int $id, string $status, array $orderedIds = []): void
    {
        $this->authorizeModule('projects', 'manage');
        abort_unless(array_key_exists($status, config('erp.statuses.task')), 422);
        Task::where('project_id', $this->project->id)->findOrFail($id)->update(['status' => $status]);
        foreach ($orderedIds as $position => $taskId) {
            Task::where('project_id', $this->project->id)->whereKey($taskId)->update(['position' => $position]);
        }
        $this->project->update(['progress' => $this->project->computedProgress()]);
    }

    public function editTask(?int $id = null, string $status = 'todo'): void
    {
        $this->authorizeModule('projects', 'manage');
        $t = $id ? Task::where('project_id', $this->project->id)->findOrFail($id) : null;
        $this->editing = $t?->id;
        $this->task = ['title' => $t?->title ?? '', 'description' => $t?->description ?? '', 'assigned_to' => $t?->assigned_to, 'status' => $t?->status ?? $status, 'priority' => $t?->priority ?? 'normale', 'due_date' => $t?->due_date?->toDateString() ?? '', 'estimated_hours' => (float) ($t?->estimated_hours ?? 0)];
        $this->showTask = true;
    }

    public function saveTask(): void
    {
        $this->authorizeModule('projects', 'manage');
        $data = $this->validate(['task.title' => ['required', 'string', 'max:200'], 'task.description' => ['nullable', 'string', 'max:3000'], 'task.assigned_to' => ['nullable', 'integer'], 'task.status' => ['required', 'in:todo,in_progress,review,done'], 'task.priority' => ['required', 'in:basse,normale,haute,urgente'], 'task.due_date' => ['nullable', 'date'], 'task.estimated_hours' => ['numeric', 'min:0']], [], ['task.title' => 'titre'])['task'];
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        Task::updateOrCreate(['id' => $this->editing], $data + ['company_id' => company_id(), 'project_id' => $this->project->id, 'position' => $this->editing ? Task::find($this->editing)->position : $this->project->tasks()->max('position') + 1]);
        $this->project->update(['progress' => $this->project->computedProgress()]);
        $this->showTask = false;
    }

    public function deleteTask(): void
    {
        $this->authorizeModule('projects', 'manage');
        Task::where('project_id', $this->project->id)->whereKey($this->editing)->delete();
        $this->showTask = false;
    }

    public function updatedStatus(string $value): void
    {
        $this->authorizeModule('projects', 'manage');
        if (array_key_exists($value, config('erp.statuses.project'))) {
            $this->project->update(['status' => $value]);
            $this->toast('Statut du projet mis à jour.');
        }
    }

    public function render()
    {
        $this->project->load('contact', 'manager', 'salesOrder', 'site', 'tasks.assignee');
        $hours = $this->project->timesheets()->with('user')->get();

        return view('livewire.operations.project-show', [
            'columns' => $this->project->tasks->groupBy('status'),
            'hours' => $hours,
            'hoursByUser' => $hours->groupBy('user.name')->map->sum('hours')->sortDesc(),
            'users' => User::whereHas('companies', fn ($q) => $q->where('companies.id', company_id()))->orderBy('name')->get(),
        ])->title($this->project->code.' · Projets');
    }
}
