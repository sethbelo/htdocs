<?php

namespace App\Livewire\Finance;

use App\Enums\TreasuryAccountType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Account;
use App\Models\Payment;
use App\Models\TreasuryAccount;
use App\Models\TreasuryForecast;
use App\Models\TreasuryTransfer;
use App\Services\Cash\TreasuryService;
use App\Services\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Trésorerie')]
class Treasury extends Component
{
    use InteractsWithToasts;

    /** @var array<string, string> */
    public array $transfer = ['from' => '', 'to' => '', 'amount' => '', 'reference' => '', 'description' => ''];

    /** @var array<string, string> */
    public array $forecast = ['label' => '', 'direction' => 'in', 'amount' => '', 'expected_on' => '', 'treasury_account_id' => ''];

    /** @var array<string, string> */
    public array $account = ['code' => '', 'name' => '', 'type' => 'bank', 'bank_name' => '', 'account_number' => '', 'account_id' => '', 'site_id' => ''];

    public function saveTransfer(TreasuryService $treasury): void
    {
        $this->authorize('treasury.disburse');
        $data = $this->validate([
            'transfer.from' => ['required', 'exists:treasury_accounts,id'],
            'transfer.to' => ['required', 'different:transfer.from', 'exists:treasury_accounts,id'],
            'transfer.amount' => ['required', 'numeric', 'gt:0'],
            'transfer.reference' => ['nullable', 'string', 'max:100'],
            'transfer.description' => ['nullable', 'string', 'max:255'],
        ], [], ['transfer.from' => 'compte source', 'transfer.to' => 'compte destination'])['transfer'];

        if ($this->attempt(fn () => $treasury->transfer(TreasuryAccount::query()->findOrFail($data['from']), TreasuryAccount::query()->findOrFail($data['to']), (float) $data['amount'], auth()->user(), $data['reference'] ?: null, $data['description'] ?: null), 'Transfert enregistré et comptabilisé.')) {
            $this->reset('transfer');
            $this->modal('transfer')->close();
        }
    }

    public function saveForecast(): void
    {
        $this->authorize('treasury.create');
        $data = $this->validate([
            'forecast.label' => ['required', 'string', 'max:255'],
            'forecast.direction' => ['required', 'in:in,out'],
            'forecast.amount' => ['required', 'numeric', 'gt:0'],
            'forecast.expected_on' => ['required', 'date'],
            'forecast.treasury_account_id' => ['nullable', 'exists:treasury_accounts,id'],
        ])['forecast'];

        TreasuryForecast::query()->create(array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->reset('forecast');
        $this->toast('Prévision ajoutée.');
    }

    public function saveAccount(): void
    {
        $this->authorize('treasury.create');
        $data = $this->validate([
            'account.code' => ['required', 'string', 'max:20', 'unique:treasury_accounts,code'],
            'account.name' => ['required', 'string', 'max:255'],
            'account.type' => ['required', Rule::enum(TreasuryAccountType::class)],
            'account.bank_name' => ['nullable', 'string', 'max:255'],
            'account.account_number' => ['nullable', 'string', 'max:255'],
            'account.account_id' => ['nullable', 'exists:accounts,id'],
            'account.site_id' => ['nullable', 'exists:sites,id'],
        ])['account'];

        TreasuryAccount::query()->create(array_map(fn ($v) => $v === '' ? null : $v, $data) + ['currency' => Money::currency()]);
        $this->reset('account');
        $this->modal('account')->close();
        $this->toast('Compte de trésorerie créé.');
    }

    public function render()
    {
        $sites = auth()->user()->accessibleSiteIds();
        $accounts = TreasuryAccount::query()->with('site')->where(fn (Builder $q) => $q->whereNull('site_id')->orWhereIn('site_id', $sites))->orderBy('type')->get();

        $flows = Payment::query()->whereIn('treasury_account_id', $accounts->pluck('id'))->where('paid_at', '>=', today()->subDays(29))
            ->selectRaw("DATE(paid_at) as day, SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as inflow, SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as outflow")
            ->groupBy('day')->orderBy('day')->get();

        $forecasts = TreasuryForecast::query()->where('is_realized', false)->where('expected_on', '>=', today())->orderBy('expected_on')->limit(20)->get();
        $projected = (float) $accounts->sum('balance') + (float) $forecasts->where('direction', 'in')->sum('amount') - (float) $forecasts->where('direction', 'out')->sum('amount');

        return view('livewire.finance.treasury', [
            'accounts' => $accounts,
            'transfers' => TreasuryTransfer::query()->with('from', 'to', 'user')->latest('transferred_at')->limit(15)->get(),
            'forecasts' => $forecasts,
            'projected' => $projected,
            'ledgerAccounts' => Account::query()->where('class', 5)->orderBy('code')->get(),
            'types' => TreasuryAccountType::options(),
            'flowChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $flows->pluck('day')->map(fn ($d) => Carbon::parse($d)->format('d/m'))->all(),
                    'datasets' => [
                        ['label' => 'Entrées', 'data' => $flows->pluck('inflow')->map(fn ($v) => (float) $v)->all(), 'backgroundColor' => '#10b981', 'borderRadius' => 4],
                        ['label' => 'Sorties', 'data' => $flows->pluck('outflow')->map(fn ($v) => -(float) $v)->all(), 'backgroundColor' => '#ef4444', 'borderRadius' => 4],
                    ],
                ],
                'options' => ['maintainAspectRatio' => false, 'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]]],
            ],
        ]);
    }
}
