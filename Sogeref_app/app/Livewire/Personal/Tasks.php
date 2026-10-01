<?php

namespace App\Livewire\Personal;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\PersonalTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * « Mes tâches du jour » : to-do personnelle par date (priorité, créneau),
 * réordonnancement, report des tâches non terminées et impression.
 */
#[Title('Mes tâches du jour')]
class Tasks extends Component
{
    use InteractsWithUi;

    #[Url(as: 'jour')]
    public string $date = '';

    public string $title = '';

    public string $priority = 'normale';

    public string $startTime = '';

    public string $endTime = '';

    public ?int $editingId = null;

    public string $editTitle = '';

    public string $editNotes = '';

    public function mount(): void
    {
        $this->date = $this->normalize($this->date);
    }

    private function normalize(?string $date): string
    {
        try {
            return $date ? Carbon::createFromFormat('Y-m-d', $date)->toDateString() : today()->toDateString();
        } catch (\Throwable) {
            return today()->toDateString();
        }
    }

    public function updatedDate(): void
    {
        $this->date = $this->normalize($this->date);
    }

    public function shiftDay(int $delta): void
    {
        $this->date = Carbon::parse($this->date)->addDays($delta)->toDateString();
    }

    public function goToday(): void
    {
        $this->date = today()->toDateString();
    }

    public function add(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:'.implode(',', PersonalTask::PRIORITIES)],
            'startTime' => ['nullable', 'date_format:H:i'],
            'endTime' => ['nullable', 'date_format:H:i', 'after:startTime'],
        ], [], ['title' => 'tâche', 'startTime' => 'heure de début', 'endTime' => 'heure de fin']);

        PersonalTask::create([
            'company_id' => company_id(), 'user_id' => auth()->id(), 'task_date' => $this->date, 'title' => trim($this->title),
            'priority' => $this->priority, 'start_time' => $this->startTime ?: null, 'end_time' => $this->startTime && $this->endTime ? $this->endTime : null,
            'position' => (int) PersonalTask::mine()->whereDate('task_date', $this->date)->max('position') + 1,
        ]);
        $this->reset('title', 'startTime', 'endTime');
        $this->priority = 'normale';
    }

    public function toggle(int $id): void
    {
        $task = $this->find($id);
        $task->update(['is_done' => ! $task->is_done, 'done_at' => $task->is_done ? null : now()]);
    }

    public function setPriority(int $id, string $priority): void
    {
        abort_unless(in_array($priority, PersonalTask::PRIORITIES, true), 422);
        $this->find($id)->update(['priority' => $priority]);
    }

    public function edit(int $id): void
    {
        $task = $this->find($id);
        $this->editingId = $task->id;
        $this->editTitle = $task->title;
        $this->editNotes = (string) $task->notes;
    }

    public function saveEdit(): void
    {
        $this->validate(['editTitle' => ['required', 'string', 'max:255'], 'editNotes' => ['nullable', 'string', 'max:2000']], [], ['editTitle' => 'tâche']);
        $this->find($this->editingId)->update(['title' => trim($this->editTitle), 'notes' => trim($this->editNotes) ?: null]);
        $this->editingId = null;
    }

    public function delete(int $id): void
    {
        $task = $this->find($id);
        $this->authorize('delete', $task);
        $task->delete();
    }

    /** Ordre reçu du glisser-déposer (identifiants dans l'ordre affiché). */
    public function reorder(array $ids): void
    {
        $ids = array_values(array_map('intval', $ids));
        $owned = PersonalTask::mine()->whereIn('id', $ids)->pluck('id')->all();
        DB::transaction(function () use ($ids, $owned) {
            foreach ($ids as $position => $id) {
                if (in_array($id, $owned, true)) {
                    PersonalTask::whereKey($id)->update(['position' => $position + 1]);
                }
            }
        });
    }

    /** Reporte les tâches non terminées des jours précédents à aujourd'hui. */
    public function carryOver(): void
    {
        $tasks = PersonalTask::mine()->whereDate('task_date', '<', today())->where('is_done', false)->orderBy('task_date')->orderBy('position')->get();
        if ($tasks->isEmpty()) {
            $this->toast('Aucune tâche en retard à reporter.', 'warning');

            return;
        }
        $position = (int) PersonalTask::mine()->whereDate('task_date', today())->max('position');
        DB::transaction(function () use ($tasks, &$position) {
            foreach ($tasks as $task) {
                $task->update(['carried_from' => $task->carried_from ?? $task->task_date, 'task_date' => today(), 'position' => ++$position]);
            }
        });
        $this->date = today()->toDateString();
        $this->toast($tasks->count().' tâche(s) reportée(s) à aujourd’hui.');
    }

    public function clearDone(): void
    {
        PersonalTask::mine()->whereDate('task_date', $this->date)->where('is_done', true)->delete();
        $this->toast('Tâches terminées retirées.');
    }

    private function find(int $id): PersonalTask
    {
        $task = PersonalTask::mine()->findOrFail($id);
        $this->authorize('update', $task);

        return $task;
    }

    public function render()
    {
        $day = Carbon::parse($this->date);
        $tasks = PersonalTask::mine()->whereDate('task_date', $day)->ordered()->get();
        $weekStart = $day->copy()->startOfWeek();
        $weekCounts = PersonalTask::mine()->whereDate('task_date', '>=', $weekStart->toDateString())->whereDate('task_date', '<=', $weekStart->copy()->addDays(6)->toDateString())
            ->selectRaw('task_date, count(*) as total, sum(case when is_done then 1 else 0 end) as done')->groupBy('task_date')->get()
            ->keyBy(fn ($r) => Carbon::parse($r->task_date)->toDateString());

        return view('livewire.personal.tasks', [
            'day' => $day,
            'tasks' => $tasks,
            'done' => $tasks->where('is_done', true)->count(),
            'overdue' => PersonalTask::mine()->whereDate('task_date', '<', today())->where('is_done', false)->count(),
            'week' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)),
            'weekCounts' => $weekCounts,
        ]);
    }
}
