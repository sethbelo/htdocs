<?php

namespace App\Livewire\Hr;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\PayrollPeriod;
use App\Models\Site;
use App\Services\PayrollService;
use App\Services\SequenceService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Paie : périodes mensuelles (salariés) et périodes des journaliers
 * (par chantier, calculées depuis les listes de présence validées).
 */
#[Title('Paie')]
class Payroll extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;

    public string $payType = 'monthly';

    public string $month = '';

    public ?int $siteId = null;

    public string $from = '';

    public string $to = '';

    public string $exchangeRate = '';

    public function create(string $type = 'monthly'): void
    {
        $this->authorizeModule('payroll', 'manage');
        $this->payType = $type;
        $this->month = now()->format('Y-m');
        $this->siteId = Site::where('company_id', company_id())->where('status', 'in_progress')->value('id');
        $this->from = now()->subDays(14)->toDateString();
        $this->to = now()->toDateString();
        $this->exchangeRate = (string) erp_setting('payroll.exchange_rate', 2847);
        $this->showForm = true;
    }

    public function save(PayrollService $payroll, SequenceService $sequences)
    {
        $this->authorizeModule('payroll', 'manage');
        $this->validate([
            'payType' => ['required', 'in:monthly,daily'], 'exchangeRate' => ['required', 'numeric', 'gt:0'],
            'month' => ['required_if:payType,monthly', 'nullable', 'date_format:Y-m'],
            'from' => ['required_if:payType,daily', 'nullable', 'date'], 'to' => ['required_if:payType,daily', 'nullable', 'date', 'after_or_equal:from'],
        ], [], ['exchangeRate' => 'taux CDF/USD']);

        if ($this->payType === 'monthly') {
            $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $name = 'Salaires '.$start->translatedFormat('F Y');
            if (PayrollPeriod::where('company_id', company_id())->where('pay_type', 'monthly')->whereDate('start_date', $start)->exists()) {
                $this->addError('month', 'Une paie mensuelle existe déjà pour ce mois.');

                return null;
            }
        } else {
            $start = Carbon::parse($this->from);
            $end = Carbon::parse($this->to);
            $site = $this->siteId ? Site::find($this->siteId) : null;
            $name = 'Journaliers '.($site ? $site->name.' ' : '').'— du '.$start->format('d/m').' au '.$end->format('d/m/Y');
        }

        $period = PayrollPeriod::create([
            'company_id' => company_id(), 'reference' => $sequences->next('payroll'), 'name' => $name, 'pay_type' => $this->payType,
            'site_id' => $this->payType === 'daily' ? ($this->siteId ?: null) : null, 'start_date' => $start, 'end_date' => $end, 'exchange_rate' => $this->exchangeRate,
        ]);
        $this->attempt(fn () => $payroll->compute($period));
        session()->flash('toast', ['type' => 'success', 'message' => "Période {$period->reference} calculée."]);

        return $this->redirectRoute('payroll.show', $period, navigate: true);
    }

    public function render()
    {
        $cid = company_id();
        $periods = PayrollPeriod::with('site')->withCount('payslips')->where('company_id', $cid)->latest('end_date')->latest('id')->get();
        $paidThisYear = PayrollPeriod::where('company_id', $cid)->where('status', 'paid')->whereYear('end_date', now()->year);

        return view('livewire.hr.payroll', [
            'periods' => $periods,
            'stats' => [
                'net' => (float) (clone $paidThisYear)->sum('total_net'),
                'employer' => (float) (clone $paidThisYear)->sum('total_employer'),
                'pending' => $periods->whereIn('status', ['draft', 'computed'])->count(),
                'toPay' => (float) $periods->where('status', 'validated')->sum('total_net'),
            ],
            'sites' => Site::where('company_id', $cid)->orderBy('name')->get(),
        ]);
    }
}
