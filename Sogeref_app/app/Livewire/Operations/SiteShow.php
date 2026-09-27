<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\PayrollPeriod;
use App\Models\Site;
use App\Models\SiteEquipment;
use App\Models\SiteEvaluation;
use App\Models\SiteReport;
use App\Services\PayrollService;
use App\Services\SequenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Fiche chantier : aperçu, équipe, présences, matériel, rapports, bons,
 * paie des journaliers et évaluation.
 */
class SiteShow extends Component
{
    use InteractsWithUi, WithFileUploads;

    public Site $site;

    #[Url(as: 'onglet')]
    public string $tab = 'apercu';

    // Aperçu
    public int $progress = 0;

    public string $status = 'planned';

    // Équipe
    public ?int $memberId = null;

    public string $memberRole = '';

    public string $memberRate = '';

    // Matériel
    public ?int $equipmentId = null;

    // Rapport journalier
    public bool $showReport = false;

    public string $reportDate = '';

    public string $weather = 'ensoleille';

    public string $workforce = '0';

    public int $reportProgress = 0;

    public string $workDone = '';

    public string $issues = '';

    public string $nextSteps = '';

    public array $photos = [];

    // Évaluation
    public bool $showEvaluation = false;

    public array $scores = [];

    public string $evaluationComment = '';

    // Paie
    public string $payFrom = '';

    public string $payTo = '';

    public function mount(Site $site): void
    {
        abort_unless($site->company_id === company_id(), 404);
        $this->site = $site;
        $this->progress = (int) $site->progress;
        $this->status = $site->status;
        $this->scores = array_fill_keys(array_keys(SiteEvaluation::CRITERIA), 3);
        $this->payFrom = now()->subDays(14)->toDateString();
        $this->payTo = now()->toDateString();
    }

    private function canManage(): bool
    {
        return auth()->user()->canManage('sites');
    }

    // ── Aperçu ──────────────────────────────────────────────────────────────
    public function saveStatus(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->validate(['progress' => ['integer', 'between:0,100'], 'status' => ['in:planned,in_progress,suspended,completed']]);
        $this->site->update([
            'progress' => $this->status === 'completed' ? 100 : $this->progress,
            'status' => $this->status,
            'actual_end_date' => $this->status === 'completed' ? ($this->site->actual_end_date ?? now()) : null,
        ]);
        if ($this->site->project) {
            $this->site->project->update(['progress' => $this->site->progress]);
        }
        $this->toast('Chantier mis à jour.');
    }

    // ── Équipe ──────────────────────────────────────────────────────────────
    public function addMember(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->validate(['memberId' => ['required', 'integer'], 'memberRate' => ['nullable', 'numeric', 'min:0']], [], ['memberId' => 'employé']);
        $employee = Employee::where('company_id', company_id())->findOrFail($this->memberId);
        $this->site->members()->syncWithoutDetaching([$employee->id => ['role' => $this->memberRole ?: $employee->position?->title, 'daily_rate' => $this->memberRate !== '' ? $this->memberRate : null, 'joined_at' => now()->toDateString(), 'left_at' => null]]);
        $this->reset(['memberId', 'memberRole', 'memberRate']);
        $this->toast("{$employee->full_name} affecté(e) au chantier.");
    }

    public function releaseMember(int $employeeId): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->site->members()->updateExistingPivot($employeeId, ['left_at' => now()->toDateString()]);
        $this->toast('Fin d’affectation enregistrée.');
    }

    public function removeMember(int $employeeId): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->site->members()->detach($employeeId);
    }

    // ── Matériel ────────────────────────────────────────────────────────────
    public function assignEquipment(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->validate(['equipmentId' => ['required', 'integer']], [], ['equipmentId' => 'équipement']);
        $equipment = Equipment::where('company_id', company_id())->where('status', 'available')->findOrFail($this->equipmentId);
        DB::transaction(function () use ($equipment) {
            $this->site->equipmentAssignments()->create(['equipment_id' => $equipment->id, 'assigned_at' => now()->toDateString()]);
            $equipment->update(['status' => 'assigned', 'site_id' => $this->site->id]);
        });
        $this->equipmentId = null;
        $this->toast("{$equipment->name} affecté au chantier.");
    }

    public function returnEquipment(int $assignmentId): void
    {
        $this->authorizeModule('sites', 'manage');
        $assignment = SiteEquipment::where('site_id', $this->site->id)->findOrFail($assignmentId);
        DB::transaction(function () use ($assignment) {
            $assignment->update(['returned_at' => now()->toDateString()]);
            $assignment->equipment->update(['status' => 'available', 'site_id' => null]);
        });
        $this->toast('Retour du matériel enregistré.');
    }

    // ── Rapports journaliers ────────────────────────────────────────────────
    public function newReport(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->reportDate = now()->toDateString();
        $this->workforce = (string) $this->site->members()->whereNull('site_members.left_at')->count();
        $this->reportProgress = (int) $this->site->progress;
        $this->reset(['workDone', 'issues', 'nextSteps', 'photos']);
        $this->showReport = true;
    }

    public function saveReport(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->validate([
            'reportDate' => ['required', 'date'], 'workforce' => ['required', 'integer', 'min:0'], 'reportProgress' => ['integer', 'between:0,100'],
            'workDone' => ['required', 'string', 'max:3000'], 'issues' => ['nullable', 'string', 'max:2000'], 'nextSteps' => ['nullable', 'string', 'max:2000'],
            'photos.*' => ['image', 'max:5120'],
        ], [], ['workDone' => 'travaux réalisés', 'photos.*' => 'photo']);
        $paths = collect($this->photos)->map(fn ($photo) => $photo->store("chantiers/{$this->site->id}", 'public'))->all();
        SiteReport::create([
            'site_id' => $this->site->id, 'user_id' => auth()->id(), 'report_date' => $this->reportDate, 'weather' => $this->weather,
            'workforce' => $this->workforce, 'progress' => $this->reportProgress, 'work_done' => $this->workDone,
            'issues' => $this->issues ?: null, 'next_steps' => $this->nextSteps ?: null, 'photos' => $paths,
        ]);
        $this->site->update(['progress' => max((int) $this->site->progress, $this->reportProgress), 'status' => $this->site->status === 'planned' ? 'in_progress' : $this->site->status]);
        $this->progress = (int) $this->site->progress;
        $this->showReport = false;
        $this->toast('Rapport journalier enregistré.');
    }

    // ── Évaluation ──────────────────────────────────────────────────────────
    public function saveEvaluation(): void
    {
        $this->authorizeModule('sites', 'manage');
        $this->validate(['scores.*' => ['required', 'integer', 'between:1,5'], 'evaluationComment' => ['nullable', 'string', 'max:2000']]);
        SiteEvaluation::create(['site_id' => $this->site->id, 'user_id' => auth()->id(), 'evaluated_at' => now(), 'scores' => array_map('intval', $this->scores), 'score' => round(array_sum($this->scores) / count($this->scores), 2), 'comment' => $this->evaluationComment ?: null]);
        $this->showEvaluation = false;
        $this->evaluationComment = '';
        $this->toast('Évaluation enregistrée.');
    }

    // ── Paie des journaliers ────────────────────────────────────────────────
    public function preparePayroll(PayrollService $payroll, SequenceService $sequences)
    {
        $this->authorizeModule('payroll', 'manage');
        $this->validate(['payFrom' => ['required', 'date'], 'payTo' => ['required', 'date', 'after_or_equal:payFrom']]);
        $period = PayrollPeriod::create([
            'company_id' => company_id(), 'reference' => $sequences->next('payroll'), 'pay_type' => 'daily', 'site_id' => $this->site->id,
            'name' => 'Journaliers '.$this->site->name.' — du '.Carbon::parse($this->payFrom)->format('d/m').' au '.Carbon::parse($this->payTo)->format('d/m/Y'),
            'start_date' => $this->payFrom, 'end_date' => $this->payTo, 'exchange_rate' => erp_setting('payroll.exchange_rate', 2847),
        ]);
        $this->attempt(fn () => $payroll->compute($period));
        session()->flash('toast', ['type' => 'success', 'message' => 'Paie calculée à partir des listes de présence validées.']);

        return $this->redirectRoute('payroll.show', $period, navigate: true);
    }

    public function render()
    {
        $this->site->load('contact', 'manager', 'project', 'salesOrder', 'members.position');
        $cid = company_id();
        $sheets = $this->site->attendanceSheets()->with('entries')->limit(60)->get();
        $validated = $sheets->where('status', 'validated');
        $labour = $validated->flatMap->entries->sum(fn ($e) => $e->dayFraction() * (float) $e->daily_rate);
        $vouchers = $this->site->vouchers()->with('requester')->get();
        $spent = (float) $vouchers->where('status', 'executed')->sum('amount');

        return view('livewire.operations.site-show', [
            'canManage' => $this->canManage(),
            'sheets' => $sheets,
            'labour' => $labour,
            'spent' => $spent,
            'vouchers' => $vouchers,
            'assignments' => $this->site->equipmentAssignments()->with('equipment')->get(),
            'reports' => $this->site->reports()->with('author')->limit(30)->get(),
            'evaluations' => $this->site->evaluations()->with('evaluator')->get(),
            'periods' => PayrollPeriod::where('site_id', $this->site->id)->latest('end_date')->get(),
            'availableEmployees' => Employee::where('company_id', $cid)->where('status', 'active')->whereNotIn('id', $this->site->members->pluck('id'))->orderBy('pay_type')->orderBy('last_name')->get(),
            'availableEquipment' => Equipment::where('company_id', $cid)->where('status', 'available')->orderBy('name')->get(),
            'daysWorked' => $validated->flatMap->entries->groupBy('employee_id')->map(fn ($g) => $g->sum(fn ($e) => $e->dayFraction())),
        ])->title($this->site->code.' · Chantiers');
    }
}
