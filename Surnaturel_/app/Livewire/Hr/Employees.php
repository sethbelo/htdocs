<?php

namespace App\Livewire\Hr;

use App\Enums\EmploymentStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Employés')]
class Employees extends Component
{
    use InteractsWithToasts, ScopesToSite, WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'statut')]
    public string $status = '';

    public ?int $departmentId = null;

    #[Url(as: 'fonction')]
    public string $jobTitle = '';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public $photo = null;

    public ?int $viewingId = null;

    public function create(): void
    {
        $this->authorize('employees.create');
        $this->editingId = null;
        $this->photo = null;
        $this->form = ['matricule' => 'EMP-'.str_pad((string) (Employee::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT), 'first_name' => '', 'last_name' => '', 'gender' => '', 'birth_date' => '', 'birth_place' => '', 'nationality' => 'Congolaise', 'phone' => '', 'email' => '', 'address' => '', 'job_title' => '', 'department_id' => '', 'site_id' => auth()->user()->default_site_id, 'manager_id' => '', 'user_id' => '', 'employment_status' => 'permanent', 'hired_at' => today()->toDateString(), 'salary_type' => 'monthly', 'emergency_contact' => '', 'blood_group' => '', 'badge_code' => '', 'national_id' => '', 'base_salary' => '', 'bank_name' => '', 'bank_account' => '', 'mobile_money_number' => ''];
        $this->modal('employee')->show();
    }

    public function edit(int $id): void
    {
        $employee = Employee::query()->visibleTo(auth()->user())->findOrFail($id);
        $this->authorize('update', $employee);
        $this->editingId = $id;
        $this->photo = null;
        $this->form = $employee->only(['matricule', 'first_name', 'last_name', 'gender', 'birth_place', 'nationality', 'phone', 'email', 'address', 'job_title', 'department_id', 'site_id', 'manager_id', 'user_id', 'salary_type', 'emergency_contact', 'blood_group', 'badge_code'])
            + ['employment_status' => $employee->employment_status->value, 'birth_date' => $employee->birth_date?->toDateString(), 'hired_at' => $employee->hired_at?->toDateString()];

        if (auth()->user()->can('viewSensitive', $employee)) {
            $this->form += array_map(fn ($v) => (string) $v, $employee->only(Employee::SENSITIVE_ATTRIBUTES));
        }

        $this->modal('employee')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'employees.update' : 'employees.create');
        $canSensitive = auth()->user()->can('employees.view_sensitive');

        $rules = [
            'form.matricule' => ['required', 'string', 'max:30', Rule::unique('employees', 'matricule')->ignore($this->editingId)],
            'form.first_name' => ['required', 'string', 'max:255'],
            'form.last_name' => ['required', 'string', 'max:255'],
            'form.gender' => ['nullable', 'in:M,F'],
            'form.birth_date' => ['nullable', 'date'],
            'form.birth_place' => ['nullable', 'string', 'max:255'],
            'form.nationality' => ['nullable', 'string', 'max:100'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.job_title' => ['nullable', 'string', 'max:255'],
            'form.department_id' => ['nullable', 'exists:departments,id'],
            'form.site_id' => ['required', Rule::in(auth()->user()->accessibleSiteIds())],
            'form.manager_id' => ['nullable', 'exists:employees,id'],
            'form.user_id' => ['nullable', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($this->editingId)],
            'form.employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'form.hired_at' => ['nullable', 'date'],
            'form.salary_type' => ['required', 'in:monthly,daily,hourly'],
            'form.emergency_contact' => ['nullable', 'string', 'max:255'],
            'form.blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'form.badge_code' => ['nullable', 'string', 'max:64', Rule::unique('employees', 'badge_code')->ignore($this->editingId)],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];

        if ($canSensitive) {
            $rules += [
                'form.national_id' => ['nullable', 'string', 'max:100'],
                'form.base_salary' => ['nullable', 'numeric', 'min:0'],
                'form.bank_name' => ['nullable', 'string', 'max:255'],
                'form.bank_account' => ['nullable', 'string', 'max:100'],
                'form.mobile_money_number' => ['nullable', 'string', 'max:30'],
            ];
        }

        $data = array_map(fn ($v) => $v === '' ? null : $v, $this->validate($rules, [], ['form.first_name' => 'prénom', 'form.last_name' => 'nom'])['form']);

        if (! $canSensitive) {
            $data = array_diff_key($data, array_flip(Employee::SENSITIVE_ATTRIBUTES));
        }

        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('employees', 'local');
        }

        $employee = Employee::query()->find($this->editingId);
        $previous = $employee?->only(['site_id', 'department_id', 'job_title']);
        $employee = Employee::query()->updateOrCreate(['id' => $this->editingId], $data);

        if (! $previous || $previous !== $employee->only(['site_id', 'department_id', 'job_title'])) {
            $employee->assignments()->whereNull('ends_on')->update(['ends_on' => today()]);
            $employee->assignments()->create(['site_id' => $employee->site_id, 'department_id' => $employee->department_id, 'job_title' => $employee->job_title, 'starts_on' => today()]);
        }

        $this->modal('employee')->close();
        $this->toast('Fiche employé enregistrée.');
    }

    public function show(int $id): void
    {
        $this->viewingId = $id;
        $this->modal('profile')->show();
    }

    public function render()
    {
        $month = [today()->startOfMonth()->toDateString(), today()->toDateString()];

        return view('livewire.hr.employees', [
            'employees' => Employee::query()->visibleTo(auth()->user())->forSite($this->siteId)->with('department', 'site', 'manager')
                ->withSum(['attendances as minutes_month' => fn (Builder $q) => $q->whereDate('date', '>=', $month[0])->whereDate('date', '<=', $month[1])], 'worked_minutes')
                ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('matricule', 'like', "%{$this->search}%")))
                ->when($this->status, fn (Builder $q) => $q->where('employment_status', $this->status))
                ->when($this->departmentId, fn (Builder $q) => $q->where('department_id', $this->departmentId))
                ->when($this->jobTitle, fn (Builder $q) => $q->where('job_title', $this->jobTitle))
                ->orderBy('last_name')->paginate(25),
            'viewing' => $this->viewingId ? Employee::query()->visibleTo(auth()->user())->with('contracts', 'assignments.site', 'assignments.department', 'leaveRequests.leaveType', 'payslips.period', 'user')->find($this->viewingId) : null,
            'statuses' => EmploymentStatus::options(),
            'departments' => Department::query()->orderBy('name')->get(),
            'jobTitles' => Employee::query()->whereNotNull('job_title')->distinct()->orderBy('job_title')->pluck('job_title'),
            'managers' => Employee::query()->where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'username']),
            'canSensitive' => auth()->user()->can('employees.view_sensitive'),
        ]);
    }
}
