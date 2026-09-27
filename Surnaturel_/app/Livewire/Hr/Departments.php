<?php

namespace App\Livewire\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\LeaveRequest;
use App\Models\Task;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Départements')]
class Departments extends Component
{
    use InteractsWithToasts;

    public ?int $selectedId = null;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(): void
    {
        $this->selectedId = Department::query()->visibleTo(auth()->user())->orderBy('name')->value('id');
    }

    public function edit(?int $id = null): void
    {
        $this->authorize($id ? 'departments.update' : 'departments.create');
        $this->editingId = $id;
        $department = $id ? Department::query()->findOrFail($id) : null;
        $this->form = $department?->only(['code', 'name', 'site_id', 'manager_id', 'mentor_id', 'objectives', 'activities']) ?? ['code' => '', 'name' => '', 'site_id' => auth()->user()->default_site_id, 'manager_id' => '', 'mentor_id' => '', 'objectives' => '', 'activities' => ''];
        $this->modal('department')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'departments.update' : 'departments.create');
        $data = $this->validate([
            'form.code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.site_id' => ['nullable', Rule::in(auth()->user()->accessibleSiteIds())],
            'form.manager_id' => ['nullable', 'exists:employees,id'],
            'form.mentor_id' => ['nullable', 'exists:employees,id'],
            'form.objectives' => ['nullable', 'string'],
            'form.activities' => ['nullable', 'string'],
        ])['form'];

        $department = Department::query()->updateOrCreate(['id' => $this->editingId], array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->selectedId = $department->id;
        $this->modal('department')->close();
        $this->toast('Département enregistré.');
    }

    public function render()
    {
        $departments = Department::query()->visibleTo(auth()->user())->withCount('employees')->with('manager', 'mentor')->orderBy('name')->get();
        $selected = $departments->firstWhere('id', $this->selectedId);
        $stats = null;

        if ($selected) {
            $employeeIds = $selected->employees()->where('is_active', true)->pluck('id');
            $today = Attendance::query()->whereIn('employee_id', $employeeIds)->whereDate('date', today())->get();
            $userIds = Employee::query()->whereIn('id', $employeeIds)->whereNotNull('user_id')->pluck('user_id');

            $stats = [
                'headcount' => $employeeIds->count(),
                'present' => $today->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Mission])->count(),
                'absent' => $today->where('status', AttendanceStatus::Absent)->count() + max(0, $employeeIds->count() - $today->count()),
                'on_leave' => LeaveRequest::query()->whereIn('employee_id', $employeeIds)->where('status', LeaveStatus::Approved)->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->count(),
                'hours_month' => round(Attendance::query()->whereIn('employee_id', $employeeIds)->where('date', '>=', today()->startOfMonth())->sum('worked_minutes') / 60, 1),
                'contracts_ending' => EmployeeContract::query()->whereIn('employee_id', $employeeIds)->whereBetween('ends_on', [today(), today()->addDays(60)])->count(),
                'tasks_open' => Task::query()->whereIn('assigned_to', $userIds)->where('status', '!=', 'done')->count(),
                'tasks_done_month' => Task::query()->whereIn('assigned_to', $userIds)->where('status', 'done')->where('completed_at', '>=', today()->startOfMonth())->count(),
                'by_status' => $selected->employees()->where('is_active', true)->get()->groupBy(fn ($e) => $e->employment_status->label())->map->count(),
            ];
        }

        return view('livewire.hr.departments', [
            'departments' => $departments,
            'selected' => $selected,
            'stats' => $stats,
            'members' => $selected ? $selected->employees()->where('is_active', true)->orderBy('last_name')->get() : collect(),
            'employees' => Employee::query()->where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
