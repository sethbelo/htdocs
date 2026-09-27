<?php

namespace App\Services\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Support\NumberGenerator;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Congés : Employé → Responsable → RH → Validation.
 */
class LeaveService
{
    public function __construct(private NumberGenerator $numbers, private AttendanceService $attendance) {}

    public function balance(Employee $employee, LeaveType $type, ?int $year = null): float
    {
        $year ??= now()->year;
        $taken = (float) $employee->leaveRequests()
            ->where('leave_type_id', $type->id)
            ->whereIn('status', [LeaveStatus::Approved, LeaveStatus::ManagerApproved, LeaveStatus::Pending])
            ->whereYear('starts_on', $year)
            ->sum('days');

        return round((float) $type->annual_quota - $taken, 1);
    }

    public function workingDays(Carbon $from, Carbon $to): float
    {
        return (float) collect(CarbonPeriod::create($from, $to))->reject(fn (Carbon $day): bool => $day->isSunday())->count();
    }

    public function submit(Employee $employee, LeaveType $type, Carbon $from, Carbon $to, ?string $reason = null, ?string $documentPath = null): LeaveRequest
    {
        if ($to->lt($from)) {
            throw new BusinessRuleException('La date de fin doit suivre la date de début.');
        }

        if ($type->requires_document && ! $documentPath) {
            throw new BusinessRuleException("Un justificatif est requis pour « {$type->name} ».");
        }

        $days = $this->workingDays($from, $to);

        if ($type->annual_quota > 0 && $days > $this->balance($employee, $type, $from->year)) {
            throw new BusinessRuleException('Solde de congés insuffisant.');
        }

        return LeaveRequest::query()->create([
            'number' => $this->numbers->next(NumberGenerator::LeaveRequest),
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'starts_on' => $from,
            'ends_on' => $to,
            'days' => $days,
            'reason' => $reason,
            'document_path' => $documentPath,
            'status' => LeaveStatus::Pending,
        ]);
    }

    public function approveAsManager(LeaveRequest $request, User $manager): LeaveRequest
    {
        $this->ensureStatus($request, LeaveStatus::Pending);
        $request->update(['status' => LeaveStatus::ManagerApproved, 'manager_approved_by' => $manager->id, 'manager_approved_at' => now()]);

        return $request;
    }

    public function approveAsHr(LeaveRequest $request, User $hr): LeaveRequest
    {
        $this->ensureStatus($request, LeaveStatus::ManagerApproved);

        return DB::transaction(function () use ($request, $hr): LeaveRequest {
            $request->update(['status' => LeaveStatus::Approved, 'hr_approved_by' => $hr->id, 'hr_approved_at' => now()]);

            foreach (CarbonPeriod::create($request->starts_on, $request->ends_on) as $day) {
                if (! $day->isSunday()) {
                    $this->attendance->markAbsent($request->employee, $day, AttendanceStatus::Leave, "Congé {$request->number}");
                }
            }

            return $request;
        });
    }

    public function reject(LeaveRequest $request, User $user, string $reason): LeaveRequest
    {
        if (blank($reason)) {
            throw new BusinessRuleException('Le motif du refus est obligatoire.');
        }

        if (! in_array($request->status, [LeaveStatus::Pending, LeaveStatus::ManagerApproved], true)) {
            throw new BusinessRuleException('Cette demande est déjà traitée.');
        }

        $request->update(['status' => LeaveStatus::Rejected, 'rejection_reason' => $reason]);

        return $request;
    }

    private function ensureStatus(LeaveRequest $request, LeaveStatus $expected): void
    {
        if ($request->status !== $expected) {
            throw new BusinessRuleException("Étape invalide (statut actuel : {$request->status->label()}).");
        }
    }
}
