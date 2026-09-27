<?php

namespace App\Services\Hr;

use App\Enums\AttendanceStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Pointage : arrivée/départ, retards, heures travaillées et supplémentaires.
 * Sources prévues : saisie manuelle, QR Code, badge RFID/NFC, biométrie (API).
 */
class AttendanceService
{
    public function checkIn(Employee $employee, ?Carbon $at = null, string $source = 'manual'): Attendance
    {
        $at ??= now();
        $start = Carbon::parse($at->toDateString().' '.Setting::get('hr.work_start', '08:00'));
        $tolerance = (int) Setting::get('hr.late_tolerance_minutes', 10);
        $late = $at->greaterThan($start->copy()->addMinutes($tolerance)) ? (int) $start->diffInMinutes($at) : 0;

        $attendance = Attendance::query()->firstOrNew(['employee_id' => $employee->id, 'date' => $at->toDateString()]);

        if ($attendance->exists && $attendance->check_in) {
            throw new BusinessRuleException('Arrivée déjà enregistrée aujourd’hui.');
        }

        $attendance->fill([
            'site_id' => $employee->site_id,
            'check_in' => $at->format('H:i:s'),
            'status' => $late > 0 ? AttendanceStatus::Late : AttendanceStatus::Present,
            'late_minutes' => $late,
            'source' => $source,
        ])->save();

        return $attendance;
    }

    public function checkOut(Employee $employee, ?Carbon $at = null): Attendance
    {
        $at ??= now();
        $attendance = Attendance::query()->where('employee_id', $employee->id)->whereDate('date', $at->toDateString())->first();

        if (! $attendance?->check_in) {
            throw new BusinessRuleException('Aucune arrivée enregistrée aujourd’hui.');
        }

        $worked = (int) Carbon::parse($at->toDateString().' '.$attendance->check_in)->diffInMinutes($at);
        $worked = max(0, $worked - (int) Setting::get('hr.break_minutes', 60));
        $expected = (int) round((float) Setting::get('hr.daily_hours', 8) * 60);

        $attendance->update([
            'check_out' => $at->format('H:i:s'),
            'worked_minutes' => $worked,
            'overtime_minutes' => max(0, $worked - $expected),
        ]);

        return $attendance;
    }

    public function markAbsent(Employee $employee, Carbon $date, AttendanceStatus $status = AttendanceStatus::Absent, ?string $notes = null): Attendance
    {
        return Attendance::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date->toDateString()],
            ['site_id' => $employee->site_id, 'status' => $status, 'notes' => $notes],
        );
    }
}
