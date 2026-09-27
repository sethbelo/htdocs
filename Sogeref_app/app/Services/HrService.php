<?php

namespace App\Services;

use App\Models\AttendanceSheet;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Site;
use Carbon\CarbonPeriod;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * RH : congés (jours ouvrables, soldes) et listes de présence de chantier.
 */
class HrService
{
    public function __construct(private readonly SequenceService $sequences) {}

    /** Jours ouvrables (lundi → samedi, dimanches exclus). */
    public function workingDays(Carbon $from, Carbon $to): float
    {
        return (float) collect(CarbonPeriod::create($from, $to))->reject(fn ($d) => $d->isSunday())->count();
    }

    public function requestLeave(Employee $employee, LeaveType $type, string $from, string $to, ?string $reason = null): LeaveRequest
    {
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        if ($end->lt($start)) {
            throw new DomainException('La date de fin précède la date de début.');
        }
        $days = $this->workingDays($start, $end);
        $overlap = LeaveRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists();
        if ($overlap) {
            throw new DomainException('Une demande existe déjà sur cette période.');
        }

        return LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'reference' => $this->sequences->next('leave', $employee->company_id),
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'reason' => $reason,
            'requested_by' => auth()->id(),
        ]);
    }

    public function decideLeave(LeaveRequest $leave, bool $approve, ?string $comment = null): void
    {
        if ($leave->status !== 'pending') {
            throw new DomainException('Cette demande a déjà été traitée.');
        }

        DB::transaction(function () use ($leave, $approve, $comment) {
            if ($approve && $leave->type->deducts_balance) {
                if ((float) $leave->employee->leave_balance + 1e-6 < (float) $leave->days) {
                    throw new DomainException('Solde de congés insuffisant ('.qty($leave->employee->leave_balance).' j disponibles).');
                }
                $leave->employee->decrement('leave_balance', $leave->days);
            }
            $leave->update(['status' => $approve ? 'approved' : 'rejected', 'approved_by' => auth()->id(), 'decided_at' => now(), 'decision_comment' => $comment]);
        });
    }

    /** Ouvre (ou reprend) la liste de présence d'un chantier pour une date. */
    public function sheetFor(Site $site, string $date): AttendanceSheet
    {
        return DB::transaction(function () use ($site, $date) {
            $sheet = AttendanceSheet::firstOrCreate(
                ['site_id' => $site->id, 'work_date' => $date],
                ['company_id' => $site->company_id, 'recorded_by' => auth()->id()]
            );

            if ($sheet->status === 'draft') {
                $onLeave = LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->pluck('employee_id')->all();
                $members = $site->members()->where('employees.status', 'active')
                    ->where(fn ($q) => $q->whereNull('site_members.left_at')->orWhereDate('site_members.left_at', '>=', $date))->get();
                foreach ($members as $employee) {
                    $sheet->entries()->firstOrCreate(['employee_id' => $employee->id], [
                        'status' => in_array($employee->id, $onLeave, true) ? 'leave' : 'present',
                        'check_in' => '07:30', 'check_out' => '16:30',
                        'daily_rate' => $employee->pivot->daily_rate ?: $employee->daily_rate,
                    ]);
                }
            }

            return $sheet->load('entries.employee');
        });
    }

    public function validateSheet(AttendanceSheet $sheet): void
    {
        if ($sheet->status === 'validated') {
            throw new DomainException('Liste déjà validée.');
        }
        $sheet->update(['status' => 'validated', 'validated_by' => auth()->id(), 'validated_at' => now()]);
    }
}
