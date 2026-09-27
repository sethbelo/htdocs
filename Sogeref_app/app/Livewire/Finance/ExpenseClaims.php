<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\FinancialAccount;
use App\Models\Site;
use App\Services\ExpenseClaimService;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Notes de frais : saisie avec justificatifs, approbation, remboursement.
 */
#[Title('Notes de frais')]
class ExpenseClaims extends Component
{
    use InteractsWithUi, WithFileUploads;

    #[Url(as: 'statut')]
    public string $status = '';

    public bool $showForm = false;

    public ?int $editing = null;

    public string $title = '';

    public ?int $employeeId = null;

    public ?int $siteId = null;

    public array $lines = [];

    public array $receipts = [];

    public ?int $viewing = null;

    public ?int $accountId = null;

    public string $comment = '';

    public function create(): void
    {
        $this->reset(['editing', 'title', 'siteId', 'receipts']);
        $this->employeeId = auth()->user()->employee?->id;
        $this->lines = [['spent_at' => now()->toDateString(), 'category' => 'transport', 'description' => '', 'amount' => '']];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $claim = ExpenseClaim::with('lines')->where('company_id', company_id())->findOrFail($id);
        abort_unless(in_array($claim->status, ['draft', 'rejected'], true), 403);
        $this->editing = $claim->id;
        $this->title = $claim->title;
        $this->employeeId = $claim->employee_id;
        $this->siteId = $claim->site_id;
        $this->lines = $claim->lines->map(fn ($l) => ['spent_at' => $l->spent_at->toDateString(), 'category' => $l->category, 'description' => $l->description, 'amount' => (float) $l->amount, 'receipt_path' => $l->receipt_path])->all();
        $this->receipts = [];
        $this->viewing = null;
        $this->showForm = true;
    }

    public function addLine(): void
    {
        $this->lines[] = ['spent_at' => now()->toDateString(), 'category' => 'transport', 'description' => '', 'amount' => ''];
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i], $this->receipts[$i]);
        $this->lines = array_values($this->lines);
        $this->receipts = array_values($this->receipts);
    }

    public function save(bool $submit = false): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:160'], 'employeeId' => ['nullable', 'integer'], 'siteId' => ['nullable', 'integer'],
            'lines' => ['required', 'array', 'min:1'], 'lines.*.spent_at' => ['required', 'date'], 'lines.*.category' => ['required', 'in:'.implode(',', array_keys(config('erp.expense_categories')))],
            'lines.*.description' => ['required', 'string', 'max:200'], 'lines.*.amount' => ['required', 'numeric', 'gt:0'], 'receipts.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [], ['title' => 'intitulé', 'lines.*.description' => 'description', 'lines.*.amount' => 'montant', 'receipts.*' => 'justificatif']);

        $claim = DB::transaction(function () {
            $claim = $this->editing
                ? tap(ExpenseClaim::where('company_id', company_id())->findOrFail($this->editing))->update(['title' => $this->title, 'employee_id' => $this->employeeId, 'site_id' => $this->siteId, 'status' => 'draft'])
                : ExpenseClaim::create(['company_id' => company_id(), 'number' => app(SequenceService::class)->next('expense_claim'), 'title' => $this->title, 'employee_id' => $this->employeeId, 'user_id' => auth()->id(), 'site_id' => $this->siteId]);
            $claim->lines()->delete();
            foreach ($this->lines as $i => $line) {
                $path = isset($this->receipts[$i]) ? $this->receipts[$i]->store('notes-de-frais', 'public') : ($line['receipt_path'] ?? null);
                $claim->lines()->create(['spent_at' => $line['spent_at'], 'category' => $line['category'], 'description' => $line['description'], 'amount' => $line['amount'], 'receipt_path' => $path]);
            }
            $claim->update(['total' => $claim->lines()->sum('amount')]);

            return $claim;
        });
        if ($submit) {
            $this->attempt(fn () => app(ExpenseClaimService::class)->submit($claim->fresh()));
        }
        $this->showForm = false;
        $this->toast($submit ? "Note {$claim->number} soumise pour approbation." : "Note {$claim->number} enregistrée.");
    }

    public function saveAndSubmit(): void
    {
        $this->save(true);
    }

    public function show(int $id): void
    {
        $this->viewing = $id;
        $this->accountId = FinancialAccount::where('company_id', company_id())->where('type', 'cash')->value('id');
        $this->comment = '';
    }

    public function submit(ExpenseClaimService $service): void
    {
        $this->attempt(fn () => $service->submit($this->current()), 'Note soumise.');
    }

    public function decide(bool $approve): void
    {
        abort_unless($this->allowed('expense-claims.approve'), 403);
        $this->attempt(fn () => app(ExpenseClaimService::class)->decide($this->current(), $approve, $this->comment ?: null), $approve ? 'Note approuvée.' : 'Note refusée.');
    }

    public function reimburse(ExpenseClaimService $service): void
    {
        $this->authorizeModule('expense-claims', 'manage');
        $this->attempt(fn () => $service->reimburse($this->current(), (int) $this->accountId), 'Remboursement effectué et comptabilisé.');
    }

    private function current(): ExpenseClaim
    {
        return ExpenseClaim::with('lines', 'employee', 'user', 'site', 'approver')->where('company_id', company_id())->findOrFail($this->viewing);
    }

    public function render()
    {
        $cid = company_id();
        $user = auth()->user();
        $seeAll = $user->canManage('expense-claims') || $user->isMaster() || $user->can('expense-claims.approve');
        $base = ExpenseClaim::where('company_id', $cid)->when(! $seeAll, fn ($q) => $q->where('user_id', $user->id));

        return view('livewire.finance.expense-claims', [
            'claims' => (clone $base)->with('employee', 'user', 'site')->when($this->status, fn ($q) => $q->where('status', $this->status))->latest('id')->get(),
            'totals' => (clone $base)->selectRaw('status, count(*) as c, sum(total) as t')->groupBy('status')->get()->keyBy('status'),
            'viewingClaim' => $this->viewing ? $this->current() : null,
            'employees' => Employee::where('company_id', $cid)->where('status', 'active')->where('pay_type', 'monthly')->orderBy('last_name')->get(),
            'sites' => Site::where('company_id', $cid)->orderBy('name')->get(),
            'accounts' => FinancialAccount::where('company_id', $cid)->where('is_active', true)->get(),
        ]);
    }
}
