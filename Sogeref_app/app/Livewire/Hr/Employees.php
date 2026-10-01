<?php

namespace App\Livewire\Hr;

use App\Livewire\Concerns\EncryptsUrlIds;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosition;
use App\Services\SequenceService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Registre du personnel : permanents, contractuels, journaliers, stagiaires.
 */
#[Title('Employés')]
class Employees extends Component
{
    use EncryptsUrlIds, InteractsWithUi, WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'categorie')]
    public string $category = '';

    #[Url(as: 'departement')]
    public ?string $departmentKey = null;

    public ?int $departmentId = null;

    public string $status = 'active';

    public bool $showForm = false;

    public ?int $editing = null;

    public array $form = [];

    public $photo = null;

    // Départements & postes
    public bool $showStructure = false;

    public string $newDepartment = '';

    public string $newDepartmentCode = '';

    public ?int $positionDepartmentId = null;

    public string $newPosition = '';

    protected function encryptedUrlIds(): array
    {
        return ['departmentId' => ['departmentKey', Department::class]];
    }

    public function mount(): void
    {
        $this->decodeUrlIds();
        if ($id = Employee::decodeRouteKey(request()->query('modifier'))) {
            $this->edit($id);
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'category', 'departmentId', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorizeModule('employees', 'manage');
        $this->editing = null;
        $this->photo = null;
        $this->form = [
            'first_name' => '', 'last_name' => '', 'gender' => 'M', 'birth_date' => '', 'phone' => '', 'email' => '', 'address' => '', 'national_id' => '', 'cnss_number' => '',
            'dependents' => 0, 'department_id' => null, 'job_position_id' => null, 'category' => 'permanent', 'pay_type' => 'monthly', 'base_salary' => 0, 'daily_rate' => 0,
            'transport_allowance' => 0, 'housing_allowance' => 0, 'payment_method' => 'cash', 'bank_account' => '', 'hire_date' => now()->toDateString(), 'leave_balance' => 0,
            'status' => 'active', 'notes' => '',
        ];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeModule('employees', 'manage');
        $e = Employee::where('company_id', company_id())->findOrFail($id);
        $this->editing = $e->id;
        $this->photo = null;
        $this->form = collect($e->only(['first_name', 'last_name', 'gender', 'phone', 'email', 'address', 'national_id', 'cnss_number', 'dependents', 'department_id', 'job_position_id', 'category', 'pay_type', 'payment_method', 'bank_account', 'status', 'notes']))
            ->merge(['birth_date' => $e->birth_date?->toDateString() ?? '', 'hire_date' => $e->hire_date?->toDateString() ?? '', 'base_salary' => (float) $e->base_salary, 'daily_rate' => (float) $e->daily_rate, 'transport_allowance' => (float) $e->transport_allowance, 'housing_allowance' => (float) $e->housing_allowance, 'leave_balance' => (float) $e->leave_balance])
            ->map(fn ($v) => $v ?? '')->all();
        $this->showForm = true;
    }

    public function updatedFormCategory(string $value): void
    {
        $this->form['pay_type'] = $value === 'journalier' ? 'daily' : 'monthly';
    }

    public function save(SequenceService $sequences): void
    {
        $this->authorizeModule('employees', 'manage');
        $data = $this->validate([
            'form.first_name' => ['required', 'string', 'max:80'], 'form.last_name' => ['required', 'string', 'max:80'], 'form.gender' => ['nullable', 'in:M,F'],
            'form.birth_date' => ['nullable', 'date'], 'form.phone' => ['nullable', 'string', 'max:40'], 'form.email' => ['nullable', 'email'], 'form.address' => ['nullable', 'string', 'max:255'],
            'form.national_id' => ['nullable', 'string', 'max:60'], 'form.cnss_number' => ['nullable', 'string', 'max:60'], 'form.dependents' => ['integer', 'between:0,20'],
            'form.department_id' => ['nullable', 'integer'], 'form.job_position_id' => ['nullable', 'integer'],
            'form.category' => ['required', Rule::in(array_keys(Employee::CATEGORIES))], 'form.pay_type' => ['required', 'in:monthly,daily'],
            'form.base_salary' => ['numeric', 'min:0'], 'form.daily_rate' => ['numeric', 'min:0'], 'form.transport_allowance' => ['numeric', 'min:0'], 'form.housing_allowance' => ['numeric', 'min:0'],
            'form.payment_method' => ['required', 'in:cash,bank,mobile_money'], 'form.bank_account' => ['nullable', 'string', 'max:80'],
            'form.hire_date' => ['nullable', 'date'], 'form.leave_balance' => ['numeric', 'min:0'], 'form.status' => ['required', 'in:active,suspended,left'], 'form.notes' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [], ['form.first_name' => 'prénom', 'form.last_name' => 'nom'])['form'];
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('employees', 'public');
        }

        if ($this->editing) {
            Employee::where('company_id', company_id())->findOrFail($this->editing)->update($data);
        } else {
            $prefix = $data['category'] === 'journalier' ? 'JRN' : 'EMP';
            $last = Employee::withTrashed()->where('company_id', company_id())->where('matricule', 'like', "{$prefix}-%")->orderByDesc('matricule')->value('matricule');
            Employee::create($data + ['company_id' => company_id(), 'matricule' => sprintf('%s-%03d', $prefix, ((int) substr((string) $last, 4)) + 1)]);
        }
        $this->showForm = false;
        $this->toast('Fiche employé enregistrée.');
    }

    public function addDepartment(): void
    {
        $this->authorizeModule('employees', 'manage');
        $this->validate(['newDepartment' => ['required', 'string', 'max:80'], 'newDepartmentCode' => ['required', 'string', 'max:10', Rule::unique('departments', 'code')->where('company_id', company_id())]]);
        Department::create(['company_id' => company_id(), 'name' => $this->newDepartment, 'code' => strtoupper($this->newDepartmentCode)]);
        $this->reset(['newDepartment', 'newDepartmentCode']);
    }

    public function addPosition(): void
    {
        $this->authorizeModule('employees', 'manage');
        $this->validate(['newPosition' => ['required', 'string', 'max:120'], 'positionDepartmentId' => ['nullable', 'integer']]);
        JobPosition::create(['company_id' => company_id(), 'department_id' => $this->positionDepartmentId, 'title' => $this->newPosition]);
        $this->newPosition = '';
    }

    public function render()
    {
        $cid = company_id();
        $base = Employee::where('company_id', $cid);

        return view('livewire.hr.employees', [
            'employees' => (clone $base)->with('department', 'position')
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->category, fn ($q) => $q->where('category', $this->category))
                ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('matricule', 'like', "%{$this->search}%")))
                ->orderBy('last_name')->paginate(20),
            'stats' => [
                'total' => (clone $base)->where('status', 'active')->count(),
                'permanent' => (clone $base)->where('status', 'active')->where('pay_type', 'monthly')->count(),
                'daily' => (clone $base)->where('status', 'active')->where('pay_type', 'daily')->count(),
                'payroll' => (float) (clone $base)->where('status', 'active')->where('pay_type', 'monthly')->sum('base_salary'),
            ],
            'departments' => Department::withCount('employees')->where('company_id', $cid)->orderBy('name')->get(),
            'positions' => JobPosition::with('department')->where('company_id', $cid)->orderBy('title')->get(),
        ]);
    }
}
