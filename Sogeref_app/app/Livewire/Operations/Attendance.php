<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\AttendanceEntry;
use App\Models\AttendanceSheet;
use App\Models\Site;
use App\Services\HrService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Appel quotidien par chantier (tablette) : statut, heures, heures sup.
 * Les listes validées alimentent la paie des journaliers.
 */
#[Title('Listes de présence')]
class Attendance extends Component
{
    use InteractsWithUi;

    #[Url(as: 'chantier')]
    public ?int $siteId = null;

    #[Url]
    public string $date = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
        $this->siteId ??= Site::where('company_id', company_id())->where('status', 'in_progress')->value('id');
    }

    private function sheet(): ?AttendanceSheet
    {
        if (! $this->siteId || ! $this->date) {
            return null;
        }
        $site = Site::where('company_id', company_id())->find($this->siteId);
        if (! $site) {
            return null;
        }
        $existing = AttendanceSheet::where('site_id', $site->id)->whereDate('work_date', $this->date)->first();
        if ($existing || ! auth()->user()->canManage('attendance') || Carbon::parse($this->date)->isFuture()) {
            return $existing?->load('entries.employee.position');
        }

        return app(HrService::class)->sheetFor($site, $this->date);
    }

    public function shiftDay(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
    }

    private function entry(int $id): AttendanceEntry
    {
        $entry = AttendanceEntry::with('sheet')->findOrFail($id);
        abort_unless($entry->sheet->company_id === company_id(), 404);
        $this->authorizeModule('attendance', 'manage');
        abort_if($entry->sheet->status === 'validated', 403, 'Liste déjà validée.');

        return $entry;
    }

    public function setStatus(int $entryId, string $status): void
    {
        abort_unless(array_key_exists($status, config('erp.statuses.attendance')), 422);
        $entry = $this->entry($entryId);
        $entry->update([
            'status' => $status,
            'check_in' => in_array($status, ['absent', 'leave'], true) ? null : ($entry->check_in ?? '07:30'),
            'check_out' => in_array($status, ['absent', 'leave'], true) ? null : ($status === 'half' ? '12:00' : ($entry->check_out ?? '16:30')),
        ]);
    }

    public function setField(int $entryId, string $field, $value): void
    {
        abort_unless(in_array($field, ['check_in', 'check_out', 'overtime_hours', 'note'], true), 422);
        $entry = $this->entry($entryId);
        $entry->update([$field => $field === 'overtime_hours' ? max(0, min(8, (float) $value)) : ($value ?: null)]);
    }

    public function markAll(string $status): void
    {
        $this->authorizeModule('attendance', 'manage');
        $sheet = $this->sheet();
        abort_if(! $sheet || $sheet->status === 'validated', 403);
        $sheet->entries()->where('status', '!=', 'leave')->update(['status' => $status, 'check_in' => $status === 'absent' ? null : '07:30', 'check_out' => $status === 'absent' ? null : '16:30']);
        $this->toast($status === 'present' ? 'Tout le monde est marqué présent.' : 'Liste mise à jour.');
    }

    public function validateSheet(HrService $hr): void
    {
        $this->authorizeModule('attendance', 'manage');
        $sheet = $this->sheet();
        if ($sheet) {
            $sheet->update(['notes' => $this->notes ?: $sheet->notes]);
            $this->attempt(fn () => $hr->validateSheet($sheet), 'Liste validée : elle sera prise en compte dans la paie des journaliers.');
        }
    }

    public function reopen(): void
    {
        abort_unless($this->allowed('payroll.validate') || auth()->user()->isMaster(), 403);
        $this->sheet()?->update(['status' => 'draft', 'validated_by' => null, 'validated_at' => null]);
        $this->toast('Liste rouverte pour correction.', 'warning');
    }

    public function render()
    {
        $sheet = $this->sheet();
        $entries = $sheet?->entries->sortBy(fn ($e) => $e->employee->last_name)->values() ?? collect();
        $week = collect(range(6, 0))->map(fn ($d) => Carbon::parse($this->date)->subDays($d));
        $weekSheets = $this->siteId ? AttendanceSheet::with('entries')->where('site_id', $this->siteId)->whereBetween('work_date', [$week->first()->toDateString(), $week->last()->toDateString().' 23:59:59'])->get()->keyBy(fn ($s) => $s->work_date->toDateString()) : collect();

        return view('livewire.operations.attendance', [
            'sites' => Site::where('company_id', company_id())->whereIn('status', ['in_progress', 'planned', 'suspended'])->orderBy('name')->get(),
            'sheet' => $sheet,
            'entries' => $entries,
            'week' => $week,
            'weekSheets' => $weekSheets,
            'canEdit' => $sheet && $sheet->status === 'draft' && auth()->user()->canManage('attendance'),
            'total' => $entries->sum(fn ($e) => $e->dayFraction() * (float) $e->daily_rate),
        ]);
    }
}
