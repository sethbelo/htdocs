<?php

namespace App\Livewire\Cash\Concerns;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Services\Cash\CashSessionReport;
use App\Services\Cash\CashSessionService;
use App\Services\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Ouverture, décaissement et clôture de la session de caisse du caissier connecté.
 */
trait ManagesCashSession
{
    public ?int $registerId = null;

    public string $openingBalance = '0';

    public string $pin = '';

    public string $countedBalance = '';

    public string $closingNotes = '';

    public bool $closingOpen = false;

    /** @var array<string, string> */
    public array $denominations = [];

    public ?int $closingSupervisorId = null;

    public string $closingSupervisorPin = '';

    public string $depositAmount = '';

    public ?int $depositAccountId = null;

    public string $disbursementAmount = '';

    public string $disbursementReason = '';

    public ?int $supervisorId = null;

    public string $supervisorPin = '';

    public function mountManagesCashSession(): void
    {
        $this->registerId ??= $this->registers()->first(fn (CashRegister $register): bool => ! $register->openSession
            && ($register->site_id === auth()->user()->default_site_id))?->id
            ?? $this->registers()->first(fn (CashRegister $register): bool => ! $register->openSession)?->id;
    }

    #[Computed]
    public function session(): ?CashSession
    {
        return app(CashSessionService::class)->currentFor(auth()->user());
    }

    /**
     * @return Collection<int, CashRegister>
     */
    #[Computed]
    public function registers(): Collection
    {
        return CashRegister::query()->with('site', 'openSession.cashier')->where('is_active', true)
            ->whereIn('site_id', auth()->user()->accessibleSiteIds())->orderBy('code')->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function supervisors(): Collection
    {
        return User::permission('cash.approve')->where('is_active', true)->where('id', '!=', auth()->id())->orderBy('name')->get(['id', 'name']);
    }

    public function openSession(CashSessionService $cash): void
    {
        $this->validate(['registerId' => ['required', 'exists:cash_registers,id'], 'openingBalance' => ['required', 'numeric', 'min:0']], [], ['registerId' => 'caisse']);
        $register = CashRegister::query()->findOrFail($this->registerId);

        if ($this->attempt(fn () => $cash->open($register, auth()->user(), (float) $this->openingBalance, $this->pin ?: null), 'Caisse ouverte.')) {
            $this->reset('pin', 'openingBalance');
            unset($this->session);
            $this->afterSessionOpened();
        }
    }

    public function disburse(CashSessionService $cash): void
    {
        $this->validate([
            'disbursementAmount' => ['required', 'numeric', 'gt:0'],
            'disbursementReason' => ['required', 'string', 'max:255'],
        ], [], ['disbursementAmount' => 'montant', 'disbursementReason' => 'motif']);

        $supervisor = $this->supervisorId ? User::query()->find($this->supervisorId) : null;

        if ($this->attempt(fn () => $cash->disburse($this->session, (float) $this->disbursementAmount, $this->disbursementReason, auth()->user(), '605', $supervisor, $this->supervisorPin ?: null), 'Décaissement enregistré.')) {
            $this->reset('disbursementAmount', 'disbursementReason', 'supervisorId', 'supervisorPin');
            $this->modal('disburse')->close();
            unset($this->session);
        }
    }

    /**
     * Ouvre le modal de clôture et prépare le comptage par coupures.
     */
    public function openClosing(): void
    {
        abort_unless($this->session, 404);
        $this->denominations = collect($this->denominationValues())->mapWithKeys(fn ($value): array => [(string) $value => ''])->all();
        $this->countedBalance = '';
        $this->closingOpen = true;
        $this->modal('close-session')->show();
    }

    /**
     * @return list<float|int>
     */
    public function denominationValues(): array
    {
        return config('erp.cash.denominations.'.Money::currency(), config('erp.cash.denominations.USD'));
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function closingReport(): ?array
    {
        return $this->closingOpen && $this->session ? app(CashSessionReport::class)->build($this->session) : null;
    }

    /**
     * @return Collection<int, TreasuryAccount>
     */
    #[Computed]
    public function depositAccounts(): Collection
    {
        return TreasuryAccount::query()->where('is_active', true)
            ->where('id', '!=', $this->session?->register->treasury_account_id)
            ->where(fn ($q) => $q->whereNull('site_id')->orWhereIn('site_id', auth()->user()->accessibleSiteIds()))
            ->orderBy('type')->get();
    }

    public function closeSession(CashSessionService $cash): void
    {
        $this->validate([
            'countedBalance' => ['required', 'numeric', 'min:0'],
            'denominations.*' => ['nullable', 'integer', 'min:0'],
            'depositAmount' => ['nullable', 'numeric', 'min:0'],
            'depositAccountId' => ['nullable', 'required_with_all:depositAmount', 'exists:treasury_accounts,id'],
            'closingNotes' => ['nullable', 'string', 'max:2000'],
        ], [], ['countedBalance' => 'solde compté', 'depositAccountId' => 'compte de versement']);

        $session = $this->session;
        $supervisor = $this->closingSupervisorId ? User::query()->find($this->closingSupervisorId) : null;
        $deposit = (float) ($this->depositAmount ?: 0);

        $closed = $this->attempt(fn () => $cash->close(
            $session,
            (float) $this->countedBalance,
            auth()->user(),
            $this->closingNotes ?: null,
            $this->denominations,
            $supervisor,
            $this->closingSupervisorPin ?: null,
            $deposit,
            $deposit > 0 && $this->depositAccountId ? TreasuryAccount::query()->find($this->depositAccountId) : null,
        ), 'Caisse clôturée et synchronisée avec la comptabilité.');

        if ($closed) {
            $this->reset('countedBalance', 'closingNotes', 'denominations', 'closingSupervisorId', 'closingSupervisorPin', 'depositAmount', 'depositAccountId', 'closingOpen');
            $this->modal('close-session')->close();
            unset($this->session);
            $this->js('window.open('.json_encode(route('print.cash-session', $session)).', "_blank")');
            $this->afterSessionClosed();
        }
    }

    protected function afterSessionOpened(): void
    {
        //
    }

    protected function afterSessionClosed(): void
    {
        //
    }
}
