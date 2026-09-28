<?php

namespace App\Livewire\Finance;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\WithListFilters;
use App\Models\Account;
use App\Models\TreasuryAccount;
use App\Models\Voucher;
use App\Services\Approvals\ApprovalService;
use App\Services\Finance\ReceivablesService;
use App\Services\Support\NumberGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Bons')]
class Vouchers extends Component
{
    use InteractsWithToasts, WithListFilters, WithPagination;

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'type')]
    public string $type = '';

    /** @var array<string, string> */
    public array $form = ['type' => 'disbursement_voucher', 'beneficiary' => '', 'amount' => '', 'description' => ''];

    /** @var list<array<string, string>> */
    public array $lines = [];

    public ?int $executeId = null;

    /** @var array<string, string> */
    public array $execution = ['treasury_account_id' => '', 'expense_account' => '605'];

    /**
     * Préfixes de numérotation des bons administratifs.
     *
     * @var array<string, string>
     */
    private const PREFIXES = [
        'purchase_order' => 'BCD', 'purchase_voucher' => 'BA', 'delivery_note' => 'BLV', 'receipt_note' => 'BRC', 'exit_voucher' => 'BS',
        'transfer_voucher' => 'BT', 'disbursement_voucher' => 'BD', 'cash_voucher' => 'BCA', 'production_voucher' => 'BPR',
    ];

    public function create(): void
    {
        $this->authorize('vouchers.create');
        $this->form = ['type' => 'disbursement_voucher', 'beneficiary' => '', 'amount' => '', 'description' => ''];
        $this->lines = [];
        $this->modal('voucher')->show();
    }

    public function addLine(): void
    {
        $this->lines[] = ['label' => '', 'quantity' => '', 'amount' => ''];
    }

    public function save(NumberGenerator $numbers, ApprovalService $approvals): void
    {
        $this->authorize('vouchers.create');
        $data = $this->validate([
            'form.type' => ['required', Rule::enum(VoucherType::class)],
            'form.beneficiary' => ['nullable', 'string', 'max:255'],
            'form.amount' => ['nullable', 'numeric', 'min:0'],
            'form.description' => ['required', 'string', 'max:2000'],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric'],
            'lines.*.amount' => ['nullable', 'numeric'],
        ], [], ['form.description' => 'objet'])['form'];

        $voucher = Voucher::query()->create([
            'number' => $numbers->next(self::PREFIXES[$data['type']]),
            'type' => $data['type'],
            'status' => VoucherStatus::PendingApproval,
            'site_id' => auth()->user()->default_site_id,
            'amount' => (float) ($data['amount'] ?: collect($this->lines)->sum(fn ($l) => (float) $l['amount'])),
            'beneficiary' => $data['beneficiary'] ?: null,
            'description' => $data['description'],
            'lines' => $this->lines ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->attempt(fn () => $approvals->submit($voucher, auth()->user()));
        $this->modal('voucher')->close();
        $this->toast("{$voucher->type->label()} {$voucher->number} soumis à validation.");
    }

    public function render()
    {
        return view('livewire.finance.vouchers', [
            'vouchers' => Voucher::query()->with('creator', 'approvalRequest.currentStep')->visibleTo(auth()->user())
                ->when($this->type, fn ($q) => $q->where('type', $this->type))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->tap(fn ($q) => $this->applySearch($q, ['number', 'beneficiary', 'description']))
                ->tap(fn ($q) => $this->applyPeriod($q, 'created_at'))
                ->latest()->paginate(20),
            'types' => VoucherType::options(),
            'executing' => $this->executeId ? Voucher::query()->find($this->executeId) : null,
            'treasuryAccounts' => TreasuryAccount::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('site_id')->orWhereIn('site_id', auth()->user()->accessibleSiteIds()))->orderBy('name')->get(),
            'expenseAccounts' => Account::query()->where('class', 6)->where('is_postable', true)->orderBy('code')->get(['code', 'name']),
        ]);
    }

    protected function filterProperties(): array
    {
        return ['status', 'type'];
    }

    public function openExecute(int $id): void
    {
        $this->authorize('vouchers.disburse');
        $this->executeId = Voucher::query()->visibleTo(auth()->user())->findOrFail($id)->id;
        $this->execution = ['treasury_account_id' => '', 'expense_account' => '605'];
        $this->modal('execute-voucher')->show();
    }

    public function execute(ReceivablesService $finance): void
    {
        $this->authorize('vouchers.disburse');
        $voucher = Voucher::query()->visibleTo(auth()->user())->findOrFail($this->executeId);
        $pays = in_array($voucher->type, [VoucherType::DisbursementVoucher, VoucherType::CashVoucher], true);

        if ($pays) {
            $this->validate([
                'execution.treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
                'execution.expense_account' => ['required', 'exists:accounts,code'],
            ], [], ['execution.treasury_account_id' => 'compte de trésorerie', 'execution.expense_account' => 'compte de charge']);
        }

        $done = $this->attempt(fn () => $finance->executeVoucher($voucher, auth()->user(), $pays ? TreasuryAccount::query()->find($this->execution['treasury_account_id']) : null, $pays ? $this->execution['expense_account'] : null), $pays ? 'Bon payé et comptabilisé.' : 'Bon marqué comme exécuté.');

        if ($done) {
            $this->modal('execute-voucher')->close();
            $this->reset('executeId');
        }
    }
}
