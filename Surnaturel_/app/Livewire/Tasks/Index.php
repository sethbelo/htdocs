<?php

namespace App\Livewire\Tasks;

use App\Enums\OrderPriority;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tâches')]
class Index extends Component
{
    use InteractsWithToasts;

    public string $scope = 'mine';

    /** @var array<string, string> */
    public array $form = ['title' => '', 'description' => '', 'assigned_to' => '', 'priority' => 'normal', 'due_at' => '', 'remind_at' => ''];

    public function save(): void
    {
        $this->authorize('tasks.create');
        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:3000'],
            'form.assigned_to' => ['nullable', 'exists:users,id'],
            'form.priority' => ['required', Rule::enum(OrderPriority::class)],
            'form.due_at' => ['nullable', 'date'],
            'form.remind_at' => ['nullable', 'date'],
        ])['form'];

        $task = Task::query()->create(array_map(fn ($v) => $v === '' ? null : $v, $data) + ['created_by' => auth()->id(), 'status' => TaskStatus::Todo]);

        if ($task->assignee && $task->assigned_to !== auth()->id()) {
            $task->assignee->notify(new TaskAssigned($task));
        }

        $this->reset('form');
        $this->modal('task')->close();
        $this->toast('Tâche créée.');
    }

    public function move(int $taskId, int $position, string $status): void
    {
        $task = Task::query()->where(fn ($q) => $q->where('assigned_to', auth()->id())->orWhere('created_by', auth()->id()))->findOrFail($taskId);
        $status = TaskStatus::from($status);
        $task->update(['status' => $status, 'completed_at' => $status === TaskStatus::Done ? now() : null]);
    }

    public function render()
    {
        $tasks = Task::query()->with('assignee', 'creator', 'taskable')
            ->when($this->scope === 'mine', fn ($q) => $q->where('assigned_to', auth()->id()))
            ->when($this->scope === 'created', fn ($q) => $q->where('created_by', auth()->id()))
            ->when($this->scope === 'all', fn ($q) => $q->where(fn ($w) => $w->where('assigned_to', auth()->id())->orWhere('created_by', auth()->id())))
            ->where(fn ($q) => $q->where('status', '!=', 'done')->orWhere('completed_at', '>=', now()->subDays(14)))
            ->orderByRaw('due_at IS NULL')->orderBy('due_at')->get();

        return view('livewire.tasks.index', [
            'columns' => collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $s) => [$s->value => $tasks->where('status', $s)]),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => OrderPriority::options(),
        ]);
    }
}
