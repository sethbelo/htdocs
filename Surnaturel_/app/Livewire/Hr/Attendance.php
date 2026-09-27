<?php

namespace App\Livewire\Hr;

use App\Enums\AttendanceStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Attendance as AttendanceRecord;
use App\Models\Employee;
use App\Services\Hr\AttendanceService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Présence')]
class Attendance extends Component
{
    use InteractsWithToasts, ScopesToSite;

    #[Url(as: 'date')]
    public string $date = '';

    public string $badge = '';

    public function mount(): void
    {
        $this->date = $this->date ?: today()->toDateString();
        $this->siteId ??= auth()->user()->default_site_id;
    }

    public function checkIn(int $employeeId, AttendanceService $attendance): void
    {
        $this->authorize('attendance.create');
        $this->attempt(fn () => $attendance->checkIn($this->employee($employeeId), $this->at()), 'Arrivée enregistrée.');
    }

    public function checkOut(int $employeeId, AttendanceService $attendance): void
    {
        $this->authorize('attendance.create');
        $this->attempt(fn () => $attendance->checkOut($this->employee($employeeId), $this->at()), 'Départ enregistré.');
    }

    public function mark(int $employeeId, string $status, AttendanceService $attendance): void
    {
        $this->authorize('attendance.update');
        $attendance->markAbsent($this->employee($employeeId), Carbon::parse($this->date), AttendanceStatus::from($status));
    }

    /**
     * Pointage par badge (RFID/NFC/QR) : arrivée, puis départ au second passage.
     */
    public function scanBadge(AttendanceService $attendance): void
    {
        $this->authorize('attendance.create');
        $employee = Employee::query()->visibleTo(auth()->user())->where('badge_code', trim($this->badge))->first();
        $this->badge = '';

        if (! $employee) {
            $this->toast('Badge inconnu.', 'warning');

            return;
        }

        $record = AttendanceRecord::query()->where('employee_id', $employee->id)->whereDate('date', today())->first();
        $this->attempt(fn () => $record?->check_in ? $attendance->checkOut($employee) : $attendance->checkIn($employee, source: 'badge'), "Pointage de {$employee->fullName()}.");
    }

    private function employee(int $id): Employee
    {
        return Employee::query()->visibleTo(auth()->user())->findOrFail($id);
    }

    private function at(): Carbon
    {
        return $this->date === today()->toDateString() ? now() : Carbon::parse($this->date.' '.now()->format('H:i:s'));
    }

    public function render()
    {
        $employees = Employee::query()->visibleTo(auth()->user())->forSite($this->siteId)->where('is_active', true)->with('department')->orderBy('last_name')->get();
        $records = AttendanceRecord::query()->whereIn('employee_id', $employees->pluck('id'))->whereDate('date', $this->date)->get()->keyBy('employee_id');

        return view('livewire.hr.attendance', [
            'employees' => $employees,
            'records' => $records,
            'summary' => [
                'present' => $records->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])->count(),
                'late' => $records->where('status', AttendanceStatus::Late)->count(),
                'absent' => $records->where('status', AttendanceStatus::Absent)->count(),
                'missing' => $employees->count() - $records->count(),
                'overtime' => round($records->sum('overtime_minutes') / 60, 1),
            ],
            'statuses' => AttendanceStatus::options(),
        ]);
    }
}
