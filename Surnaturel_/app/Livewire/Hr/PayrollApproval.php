<?php

namespace App\Livewire\Hr;

use App\Enums\PayrollPeriodStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\Hr\PayrollService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Outil de la Direction générale : visa (ou rejet motivé) des fiches de paie de tous les agents
 * avant la validation comptable et le paiement.
 */
#[Title('Visa des fiches de paie')]
class PayrollApproval extends Component
{
    use InteractsWithToasts;

    public ?int $periodId = null;

    public string $filter = 'pending';

    /** @var list<int> */
    public array $selected = [];

    public ?int $detailId = null;

    public ?int $rejectId = null;

    public string $comment = '';

    public function mount(): void
    {
        $this->periodId = PayrollPeriod::query()->whereIn('status', [PayrollPeriodStatus::Computed, PayrollPeriodStatus::Validated])->latest('starts_on')->value('id')
            ?? PayrollPeriod::query()->latest('starts_on')->value('id');
    }

    public function updatedPeriodId(): void
    {
        $this->reset('selected', 'detailId');
    }

    #[Computed]
    public function period(): ?PayrollPeriod
    {
        return $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;
    }

    /**
     * @return Collection<int, Payslip>
     */
    #[Computed]
    public function payslips(): Collection
    {
        if (! $this->period) {
            return collect();
        }

        return $this->period->payslips()->with('employee.department', 'dgApprover')->get()
            ->when($this->filter !== 'all', fn (Collection $slips) => $slips->where('dg_status', $this->filter))
            ->sortBy(fn (Payslip $p) => $p->employee->last_name)->values();
    }

    /**
     * @return array<string, float|int>
     */
    #[Computed]
    public function stats(): array
    {
        $all = $this->period?->payslips()->get() ?? collect();

        return [
            'total' => $all->count(),
            'pending' => $all->where('dg_status', 'pending')->count(),
            'approved' => $all->where('dg_status', 'approved')->count(),
            'rejected' => $all->where('dg_status', 'rejected')->count(),
            'net' => (float) $all->sum('net_pay'),
            'gross' => (float) $all->sum('gross_earnings'),
        ];
    }

    public function toggleAll(): void
    {
        $ids = $this->payslips->where('dg_status', '!=', 'approved')->pluck('id')->all();
        $this->selected = count(array_diff($ids, $this->selected)) === 0 ? [] : $ids;
    }

    public function approveSelected(PayrollService $payroll): void
    {
        $this->authorize('direction.payroll_sign');
        $slips = $this->period->payslips()->with('period')->whereIn('id', $this->selected)->get();

        $count = $this->attempt(fn () => $payroll->approveByDirection($slips, auth()->user()));
        if ($count) {
            $this->toast("{$count} fiche(s) de paie visée(s).");
        }
        $this->reset('selected');
        unset($this->payslips, $this->stats);
    }

    public function approve(int $id, PayrollService $payroll): void
    {
        $this->authorize('direction.payroll_sign');
        $slip = $this->period->payslips()->with('period')->findOrFail($id);
        $this->attempt(fn () => $payroll->approveByDirection([$slip], auth()->user()), "Fiche de {$slip->employee->fullName()} visée.");
        unset($this->payslips, $this->stats);
    }

    public function openReject(int $id): void
    {
        $this->rejectId = $id;
        $this->comment = '';
        $this->modal('reject-payslip')->show();
    }

    public function reject(PayrollService $payroll): void
    {
        $this->authorize('direction.payroll_sign');
        $this->validate(['comment' => ['required', 'string', 'max:1000']], [], ['comment' => 'motif']);
        $slip = $this->period->payslips()->with('period', 'employee')->findOrFail($this->rejectId);

        $this->attempt(fn () => $payroll->rejectByDirection($slip, auth()->user(), $this->comment), 'Fiche renvoyée aux RH pour correction.');
        $this->modal('reject-payslip')->close();
        $this->reset('rejectId', 'comment');
        unset($this->payslips, $this->stats);
    }

    public function render()
    {
        return view('livewire.hr.payroll-approval', [
            'periods' => PayrollPeriod::query()->latest('starts_on')->get(),
            'detail' => $this->detailId ? $this->period?->payslips()->with('employee.department')->find($this->detailId) : null,
        ]);
    }
}
