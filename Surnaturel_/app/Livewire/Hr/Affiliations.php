<?php

namespace App\Livewire\Hr;

use App\Enums\AffiliationStatus;
use App\Enums\CareType;
use App\Enums\DependentRelationship;
use App\Enums\InsuranceType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Affiliation;
use App\Models\CareVoucher;
use App\Models\Employee;
use App\Models\EmployeeDependent;
use App\Models\InsurancePolicy;
use App\Services\Hr\AffiliationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Gestion et affiliation des agents et de leurs familles aux soins médicaux et à l'assurance vie.
 */
#[Title('Santé & assurance')]
class Affiliations extends Component
{
    use InteractsWithToasts;

    #[Url(as: 'vue')]
    public string $tab = 'members';

    public string $search = '';

    public ?int $employeeId = null;

    /** @var array<string, mixed> */
    public array $dependent = [];

    /** @var array<string, mixed> */
    public array $affiliation = [];

    /** @var array<string, mixed> */
    public array $policy = [];

    public ?int $editingPolicyId = null;

    /** @var array<string, mixed> */
    public array $care = [];

    public function mount(): void
    {
        $this->resetForms();
    }

    private function resetForms(): void
    {
        $this->dependent = ['first_name' => '', 'last_name' => '', 'relationship' => 'child', 'gender' => '', 'birth_date' => '', 'phone' => '', 'is_student' => false];
        $this->affiliation = ['insurance_policy_id' => '', 'member' => 'employee', 'starts_on' => today()->toDateString(), 'beneficiary_name' => '', 'beneficiary_relationship' => '', 'beneficiary_share' => '100'];
        $this->policy = ['name' => '', 'type' => 'medical', 'provider' => '', 'policy_number' => '', 'coverage' => '', 'coverage_limit' => '', 'employer_share' => '100', 'monthly_premium' => '0', 'contact_phone' => '', 'starts_on' => '', 'ends_on' => '', 'is_active' => true];
        $this->care = ['affiliation_id' => '', 'care_provider' => '', 'care_type' => 'consultation', 'description' => '', 'estimated_amount' => '', 'validity_days' => 15];
    }

    /**
     * @return array<string, float|int>
     */
    #[Computed]
    public function stats(): array
    {
        $active = Affiliation::query()->with('policy')->where('status', AffiliationStatus::Active)->whereHas('employee', fn (Builder $q) => $q->visibleTo(auth()->user()))->get();

        return [
            'employees' => $active->whereNull('employee_dependent_id')->pluck('employee_id')->unique()->count(),
            'dependents' => $active->whereNotNull('employee_dependent_id')->pluck('employee_dependent_id')->unique()->count(),
            'policies' => InsurancePolicy::query()->where('is_active', true)->count(),
            'premium' => (float) $active->sum(fn (Affiliation $a) => (float) $a->policy->monthly_premium),
            'vouchers' => CareVoucher::query()->whereMonth('issued_on', now()->month)->whereYear('issued_on', now()->year)->count(),
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::query()->visibleTo(auth()->user())->where('is_active', true)
            ->withCount(['dependents', 'affiliations' => fn (Builder $q) => $q->where('status', AffiliationStatus::Active)])
            ->with('department')
            ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('matricule', 'like', "%{$this->search}%")))
            ->orderBy('last_name')->get();
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return $this->employeeId
            ? Employee::query()->visibleTo(auth()->user())->with(['dependents.affiliations.policy', 'affiliations' => fn ($q) => $q->with('policy', 'dependent', 'careVouchers')->latest()])->find($this->employeeId)
            : null;
    }

    public function selectEmployee(int $id): void
    {
        $this->employeeId = $id;
        $this->resetForms();
        unset($this->employee);
    }

    public function saveDependent(): void
    {
        $this->authorize('affiliations.create');
        $data = $this->validate([
            'dependent.first_name' => ['required', 'string', 'max:100'],
            'dependent.last_name' => ['required', 'string', 'max:100'],
            'dependent.relationship' => ['required', Rule::enum(DependentRelationship::class)],
            'dependent.gender' => ['nullable', 'in:M,F'],
            'dependent.birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'dependent.phone' => ['nullable', 'string', 'max:30'],
            'dependent.is_student' => ['boolean'],
        ], [], ['dependent.first_name' => 'prénom', 'dependent.last_name' => 'nom', 'dependent.birth_date' => 'date de naissance'])['dependent'];

        $this->employee->dependents()->create(array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->modal('dependent')->close();
        $this->resetForms();
        $this->toast('Ayant droit ajouté.');
        unset($this->employee, $this->employees);
    }

    public function removeDependent(int $id): void
    {
        $this->authorize('affiliations.delete');
        $dependent = $this->employee->dependents()->findOrFail($id);
        $dependent->affiliations()->update(['status' => AffiliationStatus::Terminated, 'ends_on' => today()]);
        $dependent->delete();
        $this->toast('Ayant droit retiré ; ses affiliations sont résiliées.');
        unset($this->employee, $this->stats);
    }

    public function openAffiliation(string $member = 'employee'): void
    {
        $this->resetForms();
        $this->affiliation['member'] = $member;
        $this->modal('affiliate')->show();
    }

    public function affiliate(AffiliationService $service): void
    {
        $this->authorize('affiliations.create');
        $this->validate([
            'affiliation.insurance_policy_id' => ['required', 'exists:insurance_policies,id'],
            'affiliation.member' => ['required', 'string'],
            'affiliation.starts_on' => ['required', 'date'],
            'affiliation.beneficiary_name' => ['nullable', 'string', 'max:255'],
            'affiliation.beneficiary_relationship' => ['nullable', 'string', 'max:100'],
            'affiliation.beneficiary_share' => ['nullable', 'numeric', 'between:0,100'],
        ], [], ['affiliation.insurance_policy_id' => 'police']);

        $policy = InsurancePolicy::query()->findOrFail($this->affiliation['insurance_policy_id']);
        $dependent = $this->affiliation['member'] === 'employee' ? null : EmployeeDependent::query()->where('employee_id', $this->employee->id)->findOrFail((int) $this->affiliation['member']);

        $result = $this->attempt(fn () => $service->affiliate($policy, $this->employee, $dependent, auth()->user(), [
            'starts_on' => $this->affiliation['starts_on'],
            'beneficiary_name' => $policy->type === InsuranceType::Life ? ($this->affiliation['beneficiary_name'] ?: null) : null,
            'beneficiary_relationship' => $policy->type === InsuranceType::Life ? ($this->affiliation['beneficiary_relationship'] ?: null) : null,
            'beneficiary_share' => $policy->type === InsuranceType::Life && $this->affiliation['beneficiary_name'] ? $this->affiliation['beneficiary_share'] : null,
        ]), 'Affiliation enregistrée.');

        if ($result) {
            $this->modal('affiliate')->close();
            $this->resetForms();
            unset($this->employee, $this->stats, $this->employees);
        }
    }

    public function setStatus(int $affiliationId, string $status, AffiliationService $service): void
    {
        $this->authorize('affiliations.update');
        $affiliation = $this->employee->affiliations()->findOrFail($affiliationId);
        $service->setStatus($affiliation, AffiliationStatus::from($status));
        $this->toast('Statut de l’affiliation mis à jour.');
        unset($this->employee, $this->stats);
    }

    public function openCare(int $affiliationId): void
    {
        $this->resetForms();
        $this->care['affiliation_id'] = $affiliationId;
        $this->modal('care')->show();
    }

    public function issueCare(AffiliationService $service): void
    {
        $this->authorize('affiliations.create');
        $data = $this->validate([
            'care.affiliation_id' => ['required', 'integer'],
            'care.care_provider' => ['required', 'string', 'max:255'],
            'care.care_type' => ['required', Rule::enum(CareType::class)],
            'care.description' => ['nullable', 'string', 'max:2000'],
            'care.estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'care.validity_days' => ['required', 'integer', 'between:1,90'],
        ], [], ['care.care_provider' => 'établissement de soins'])['care'];

        $affiliation = $this->employee->affiliations()->with('policy')->findOrFail($data['affiliation_id']);
        $voucher = $this->attempt(fn () => $service->issueCareVoucher($affiliation, auth()->user(), $data), 'Bon de prise en charge émis.');

        if ($voucher) {
            $this->modal('care')->close();
            $this->js('window.open('.json_encode(route('print.care-voucher', $voucher)).', "_blank")');
            unset($this->employee, $this->stats);
        }
    }

    public function editPolicy(?int $id = null): void
    {
        $this->resetForms();
        $this->editingPolicyId = $id;
        if ($id) {
            $policy = InsurancePolicy::query()->findOrFail($id);
            $this->policy = array_map(fn ($v) => $v ?? '', $policy->only(array_keys($this->policy)));
            $this->policy['type'] = $policy->type->value;
            $this->policy['starts_on'] = $policy->starts_on?->toDateString() ?? '';
            $this->policy['ends_on'] = $policy->ends_on?->toDateString() ?? '';
            $this->policy['is_active'] = $policy->is_active;
        }
        $this->modal('policy')->show();
    }

    public function savePolicy(): void
    {
        $this->authorize($this->editingPolicyId ? 'affiliations.update' : 'affiliations.create');
        $data = $this->validate([
            'policy.name' => ['required', 'string', 'max:255'],
            'policy.type' => ['required', Rule::enum(InsuranceType::class)],
            'policy.provider' => ['required', 'string', 'max:255'],
            'policy.policy_number' => ['nullable', 'string', 'max:100'],
            'policy.coverage' => ['nullable', 'string', 'max:5000'],
            'policy.coverage_limit' => ['nullable', 'numeric', 'min:0'],
            'policy.employer_share' => ['required', 'numeric', 'between:0,100'],
            'policy.monthly_premium' => ['required', 'numeric', 'min:0'],
            'policy.contact_phone' => ['nullable', 'string', 'max:30'],
            'policy.starts_on' => ['nullable', 'date'],
            'policy.ends_on' => ['nullable', 'date', 'after_or_equal:policy.starts_on'],
            'policy.is_active' => ['boolean'],
        ], [], ['policy.name' => 'nom', 'policy.provider' => 'assureur / prestataire'])['policy'];

        InsurancePolicy::query()->updateOrCreate(['id' => $this->editingPolicyId], array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->modal('policy')->close();
        $this->toast('Police enregistrée.');
        $this->reset('editingPolicyId');
        unset($this->stats);
    }

    public function render()
    {
        return view('livewire.hr.affiliations', [
            'policies' => InsurancePolicy::query()->withCount(['affiliations' => fn ($q) => $q->where('status', AffiliationStatus::Active)])->orderBy('type')->orderBy('name')->get(),
            'vouchers' => $this->tab === 'care' ? CareVoucher::query()->with('affiliation.employee', 'affiliation.dependent', 'affiliation.policy', 'issuer')->whereHas('affiliation.employee', fn (Builder $q) => $q->visibleTo(auth()->user()))->latest('issued_on')->latest('id')->limit(100)->get() : collect(),
            'relationships' => DependentRelationship::cases(),
            'careTypes' => CareType::cases(),
            'insuranceTypes' => InsuranceType::cases(),
        ]);
    }
}
