<?php

namespace App\Livewire\Hr;

use App\Enums\LeaveStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Hr\LeaveService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Congés')]
class Leaves extends Component
{
    use InteractsWithToasts, WithFileUploads;

    #[Url(as: 'statut')]
    public string $status = '';

    /** @var array<string, string> */
    public array $form = ['employee_id' => '', 'leave_type_id' => '', 'starts_on' => '', 'ends_on' => '', 'reason' => ''];

    public $document = null;

    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    public function mount(): void
    {
        $this->form['employee_id'] = (string) auth()->user()->employee?->id;
    }

    public function submit(LeaveService $leaves): void
    {
        $data = $this->validate([
            'form.employee_id' => ['required', 'exists:employees,id'],
            'form.leave_type_id' => ['required', 'exists:leave_types,id'],
            'form.starts_on' => ['required', 'date'],
            'form.ends_on' => ['required', 'date', 'after_or_equal:form.starts_on'],
            'form.reason' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'max:10240'],
        ], [], ['form.employee_id' => 'employé', 'form.leave_type_id' => 'type de congé'])['form'];

        $employee = Employee::query()->findOrFail($data['employee_id']);
        abort_unless($employee->user_id === auth()->id() || auth()->user()->can('leaves.create'), 403);

        $path = $this->document?->store("leaves/{$employee->id}", 'local');

        if ($this->attempt(fn () => $leaves->submit($employee, LeaveType::query()->findOrFail($data['leave_type_id']), Carbon::parse($data['starts_on']), Carbon::parse($data['ends_on']), $data['reason'] ?: null, $path), 'Demande de congé envoyée au responsable.')) {
            $this->reset('document');
            $this->form = ['employee_id' => $data['employee_id'], 'leave_type_id' => '', 'starts_on' => '', 'ends_on' => '', 'reason' => ''];
            $this->modal('leave')->close();
        }
    }

    public function approve(int $id, LeaveService $leaves): void
    {
        $request = LeaveRequest::query()->with('employee.manager')->findOrFail($id);

        if ($request->status === LeaveStatus::Pending) {
            $isManager = $request->employee->manager?->user_id === auth()->id();
            abort_unless($isManager || auth()->user()->can('leaves.validate'), 403);
            $this->attempt(fn () => $leaves->approveAsManager($request, auth()->user()), 'Validation responsable enregistrée : transmis aux RH.');
        } else {
            $this->authorize('leaves.approve');
            $this->attempt(fn () => $leaves->approveAsHr($request, auth()->user()), 'Congé validé par les RH.');
        }
    }

    public function openReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->modal('reject')->show();
    }

    public function reject(LeaveService $leaves): void
    {
        $this->validate(['rejectionReason' => ['required', 'string', 'max:255']], [], ['rejectionReason' => 'motif']);
        abort_unless(auth()->user()->can('leaves.validate') || auth()->user()->can('leaves.approve'), 403);

        if ($this->attempt(fn () => $leaves->reject(LeaveRequest::query()->findOrFail($this->rejectingId), auth()->user(), $this->rejectionReason), 'Demande refusée.')) {
            $this->reset('rejectionReason', 'rejectingId');
            $this->modal('reject')->close();
        }
    }

    public function render(LeaveService $leaves)
    {
        $user = auth()->user();
        $canManage = $user->can('leaves.view') && ($user->can('leaves.validate') || $user->can('leaves.approve'));
        $myEmployee = $user->employee;

        return view('livewire.hr.leaves', [
            'requests' => LeaveRequest::query()->with('employee.department', 'leaveType')
                ->when(! $canManage, fn ($q) => $q->where(fn ($w) => $w->where('employee_id', $myEmployee?->id)->orWhereHas('employee.manager', fn ($m) => $m->where('user_id', $user->id))))
                ->when($canManage, fn ($q) => $q->whereHas('employee', fn ($e) => $e->visibleTo($user)))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest()->limit(100)->get(),
            'calendar' => LeaveRequest::query()->with('employee', 'leaveType')->where('status', LeaveStatus::Approved)->whereDate('ends_on', '>=', today())->orderBy('starts_on')->limit(30)->get(),
            'types' => LeaveType::query()->orderBy('name')->get(),
            'balances' => $myEmployee ? LeaveType::query()->where('annual_quota', '>', 0)->get()->map(fn (LeaveType $type): array => ['type' => $type, 'balance' => $leaves->balance($myEmployee, $type)]) : collect(),
            'employees' => $user->can('leaves.create') ? Employee::query()->visibleTo($user)->where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']) : collect([$myEmployee])->filter(),
            'statuses' => LeaveStatus::options(),
        ]);
    }
}
