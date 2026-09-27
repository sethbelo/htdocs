<?php

namespace App\Livewire\Finance;

use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rapports de gestion avec aperçu et impression sur papier à en-tête.
 */
#[Title('Rapports')]
class Reports extends Component
{
    #[Url(as: 'rapport')]
    public string $report = 'ventes';

    #[Url(as: 'du')]
    public string $from = '';

    #[Url(as: 'au')]
    public string $to = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfYear()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
        if (! array_key_exists($this->report, ReportService::REPORTS)) {
            $this->report = 'ventes';
        }
    }

    public function preset(string $period): void
    {
        [$this->from, $this->to] = match ($period) {
            'mois' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'mois-dernier' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'trimestre' => [now()->startOfQuarter()->toDateString(), now()->toDateString()],
            default => [now()->startOfYear()->toDateString(), now()->toDateString()],
        };
    }

    public function render(ReportService $reports)
    {
        return view('livewire.finance.reports', [
            'catalog' => ReportService::REPORTS,
            'data' => $reports->build($this->report, Carbon::parse($this->from), Carbon::parse($this->to)),
        ]);
    }
}
