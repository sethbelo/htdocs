<?php

namespace App\Livewire\Hr;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Hr\EmployeeCards;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Atelier RH des cartes : badges, cartes de service recto / verso et cartes de visite,
 * à l'unité (imprimante à cartes) ou en planche A4 imposée (presse de l'imprimerie).
 */
#[Title('Badges & cartes')]
class Cards extends Component
{
    use InteractsWithToasts;

    public string $kind = 'service';

    public string $layout = 'single';

    public bool $withBack = true;

    public string $search = '';

    public ?int $departmentId = null;

    /** @var list<int> */
    public array $selected = [];

    public ?int $previewId = null;

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::query()->visibleTo(auth()->user())->with('department', 'site')->where('is_active', true)
            ->when($this->departmentId, fn (Builder $q) => $q->where('department_id', $this->departmentId))
            ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('matricule', 'like', "%{$this->search}%")->orWhere('job_title', 'like', "%{$this->search}%")))
            ->orderBy('last_name')->get();
    }

    #[Computed]
    public function preview(): ?Employee
    {
        $id = $this->previewId ?? ($this->selected[0] ?? null) ?? $this->employees->first()?->id;

        return $id ? $this->employees->firstWhere('id', $id) ?? Employee::query()->visibleTo(auth()->user())->find($id) : null;
    }

    public function toggleAll(): void
    {
        $ids = $this->employees->pluck('id')->all();
        $this->selected = count(array_diff($ids, $this->selected)) === 0 ? [] : $ids;
    }

    public function generate(EmployeeCards $cards): ?StreamedResponse
    {
        $this->authorize('cards.print');
        $this->validate([
            'kind' => ['required', 'in:'.implode(',', array_keys(EmployeeCards::KINDS))],
            'layout' => ['required', 'in:single,sheet'],
            'selected' => ['required', 'array', 'min:1', 'max:200'],
        ], ['selected.required' => 'Sélectionnez au moins un agent.']);

        $employees = Employee::query()->visibleTo(auth()->user())->whereIn('id', $this->selected)->orderBy('last_name')->get();
        $pdf = $cards->render($employees, $this->kind, $this->layout, $this->withBack);
        $this->toast($employees->count().' carte(s) générée(s).');
        unset($this->employees);

        return response()->streamDownload(fn () => print ($pdf->output()), "{$this->kind}-".now()->format('Ymd-His').'.pdf');
    }

    public function render()
    {
        return view('livewire.hr.cards', [
            'kinds' => EmployeeCards::KINDS,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
