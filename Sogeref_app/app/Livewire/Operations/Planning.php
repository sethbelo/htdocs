<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\EncryptsUrlIds;
use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ManufacturingOrder;
use App\Models\PlanningSlot;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Planification hebdomadaire des équipes : chantiers, atelier, projets.
 */
#[Title('Planification')]
class Planning extends Component
{
    use EncryptsUrlIds, InteractsWithUi;

    #[Url(as: 'semaine')]
    public string $week = '';

    #[Url(as: 'departement')]
    public ?string $departmentKey = null;

    public ?int $departmentId = null;

    public bool $showSlot = false;

    public ?int $editing = null;

    public array $assignment = [];

    protected function encryptedUrlIds(): array
    {
        return ['departmentId' => ['departmentKey', Department::class]];
    }

    public function mount(): void
    {
        $this->decodeUrlIds();
        $this->week = $this->week ?: now()->startOfWeek()->toDateString();
    }

    public function shift(int $weeks): void
    {
        $this->week = Carbon::parse($this->week)->addWeeks($weeks)->startOfWeek()->toDateString();
    }

    public function openSlot(int $employeeId, string $date, ?int $slotId = null): void
    {
        $this->authorizeModule('planning', 'manage');
        $s = $slotId ? PlanningSlot::where('company_id', company_id())->findOrFail($slotId) : null;
        $this->editing = $s?->id;
        $target = $s?->site_id ? 'site:'.$s->site_id : ($s?->manufacturing_order_id ? 'mo:'.$s->manufacturing_order_id : ($s?->project_id ? 'project:'.$s->project_id : ''));
        $this->assignment = ['employee_id' => $employeeId, 'slot_date' => $date, 'shift' => $s?->shift ?? 'journee', 'target' => $target, 'role' => $s?->role ?? '', 'notes' => $s?->notes ?? '', 'repeat' => false];
        $this->showSlot = true;
    }

    public function saveSlot(): void
    {
        $this->authorizeModule('planning', 'manage');
        $this->validate(['assignment.employee_id' => ['required', 'integer'], 'assignment.slot_date' => ['required', 'date'], 'assignment.shift' => ['required', 'in:journee,matin,apres_midi'], 'assignment.target' => ['required', 'string']], [], ['assignment.target' => 'affectation']);
        [$kind, $id] = explode(':', $this->assignment['target']) + [null, null];
        $data = [
            'company_id' => company_id(), 'employee_id' => $this->assignment['employee_id'], 'shift' => $this->assignment['shift'], 'role' => $this->assignment['role'] ?: null, 'notes' => $this->assignment['notes'] ?: null,
            'site_id' => $kind === 'site' ? (int) $id : null, 'manufacturing_order_id' => $kind === 'mo' ? (int) $id : null, 'project_id' => $kind === 'project' ? (int) $id : null,
        ];
        $dates = $this->assignment['repeat'] && ! $this->editing
            ? collect(range(0, 5))->map(fn ($d) => Carbon::parse($this->week)->addDays($d)->toDateString())->filter(fn ($d) => $d >= $this->assignment['slot_date'])
            : collect([$this->assignment['slot_date']]);
        foreach ($dates as $date) {
            PlanningSlot::updateOrCreate(['id' => $this->editing ?? 0], $data + ['slot_date' => $date]);
        }
        $this->showSlot = false;
        $this->toast($dates->count() > 1 ? 'Affectation répétée sur '.$dates->count().' jours.' : 'Affectation enregistrée.');
    }

    public function deleteSlot(): void
    {
        $this->authorizeModule('planning', 'manage');
        PlanningSlot::where('company_id', company_id())->whereKey($this->editing)->delete();
        $this->showSlot = false;
    }

    public function render()
    {
        $cid = company_id();
        $start = Carbon::parse($this->week)->startOfWeek();
        $days = collect(range(0, 5))->map(fn ($d) => $start->copy()->addDays($d));
        $employees = Employee::with('position')->where('company_id', $cid)->where('status', 'active')
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))->orderBy('pay_type')->orderBy('last_name')->get();
        $slots = PlanningSlot::with('site', 'project', 'manufacturingOrder')->where('company_id', $cid)->whereBetween('slot_date', [$start->toDateString(), $start->copy()->addDays(6)->toDateString().' 23:59:59'])->get();

        return view('livewire.operations.planning', [
            'days' => $days,
            'employees' => $employees,
            'assignments' => $slots->groupBy(fn ($s) => $s->employee_id.'|'.$s->slot_date->toDateString()),
            'load' => $days->mapWithKeys(fn ($d) => [$d->toDateString() => $slots->where('slot_date', $d)->unique('employee_id')->count()]),
            'departments' => Department::where('company_id', $cid)->orderBy('name')->get(),
            'targets' => collect()
                ->merge(Site::where('company_id', $cid)->whereIn('status', ['planned', 'in_progress'])->get()->mapWithKeys(fn ($s) => ['site:'.$s->id => 'Chantier · '.$s->name]))
                ->merge(ManufacturingOrder::where('company_id', $cid)->whereIn('status', ['confirmed', 'in_progress', 'quality'])->get()->mapWithKeys(fn ($m) => ['mo:'.$m->id => 'Atelier · '.$m->number.' '.$m->title]))
                ->merge(Project::where('company_id', $cid)->where('status', 'active')->get()->mapWithKeys(fn ($p) => ['project:'.$p->id => 'Projet · '.$p->name])),
        ]);
    }
}
