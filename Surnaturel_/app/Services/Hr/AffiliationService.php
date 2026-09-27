<?php

namespace App\Services\Hr;

use App\Enums\AffiliationStatus;
use App\Enums\InsuranceType;
use App\Exceptions\BusinessRuleException;
use App\Models\Affiliation;
use App\Models\CareVoucher;
use App\Models\Employee;
use App\Models\EmployeeDependent;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Carbon;

/**
 * Affiliation des agents et de leurs ayants droit aux polices santé et assurance vie,
 * et émission des bons de prise en charge médicale.
 */
class AffiliationService
{
    public function __construct(private NumberGenerator $numbers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function affiliate(InsurancePolicy $policy, Employee $employee, ?EmployeeDependent $dependent, User $user, array $data = []): Affiliation
    {
        if (! $policy->is_active) {
            throw new BusinessRuleException("La police {$policy->name} n’est pas active.");
        }

        if ($dependent && $dependent->employee_id !== $employee->id) {
            throw new BusinessRuleException('Cet ayant droit n’est pas rattaché à cet agent.');
        }

        if ($policy->type === InsuranceType::Life && $dependent) {
            throw new BusinessRuleException('L’assurance vie couvre l’agent : désignez son ayant droit comme bénéficiaire.');
        }

        $exists = Affiliation::query()->where('insurance_policy_id', $policy->id)->where('employee_id', $employee->id)
            ->where('employee_dependent_id', $dependent?->id)->where('status', '!=', AffiliationStatus::Terminated)->exists();

        if ($exists) {
            throw new BusinessRuleException('Ce membre est déjà affilié à cette police.');
        }

        return Affiliation::query()->create([
            'member_number' => $this->numbers->next(NumberGenerator::Affiliation),
            'insurance_policy_id' => $policy->id,
            'employee_id' => $employee->id,
            'employee_dependent_id' => $dependent?->id,
            'status' => AffiliationStatus::Active,
            'starts_on' => $data['starts_on'] ?? today(),
            'ends_on' => $data['ends_on'] ?? null,
            'beneficiary_name' => $data['beneficiary_name'] ?? null,
            'beneficiary_relationship' => $data['beneficiary_relationship'] ?? null,
            'beneficiary_share' => $data['beneficiary_share'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
        ]);
    }

    public function setStatus(Affiliation $affiliation, AffiliationStatus $status): Affiliation
    {
        $affiliation->update(['status' => $status, 'ends_on' => $status === AffiliationStatus::Terminated ? ($affiliation->ends_on ?? today()) : $affiliation->ends_on]);

        return $affiliation;
    }

    /**
     * @param  array{care_provider: string, care_type: string, description?: string|null, estimated_amount?: float|string|null, validity_days?: int|null}  $data
     */
    public function issueCareVoucher(Affiliation $affiliation, User $user, array $data): CareVoucher
    {
        if ($affiliation->policy->type !== InsuranceType::Medical) {
            throw new BusinessRuleException('Un bon de prise en charge ne s’émet que sur une police santé.');
        }

        if (! $affiliation->isActive()) {
            throw new BusinessRuleException('L’affiliation n’est pas active.');
        }

        return $affiliation->careVouchers()->create([
            'number' => $this->numbers->next(NumberGenerator::CareVoucher),
            'care_provider' => $data['care_provider'],
            'care_type' => $data['care_type'],
            'description' => $data['description'] ?? null,
            'estimated_amount' => (float) ($data['estimated_amount'] ?? 0),
            'status' => 'issued',
            'issued_on' => today(),
            'valid_until' => Carbon::today()->addDays((int) ($data['validity_days'] ?? 15)),
            'issued_by' => $user->id,
        ]);
    }
}
