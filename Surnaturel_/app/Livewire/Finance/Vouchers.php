<?php

namespace App\Livewire\Finance;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Voucher;
use App\Services\Approvals\ApprovalService;
use App\Services\Support\NumberGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Bons')]
class Vouchers extends Component
{
    use InteractsWithToasts, WithPagination;

    #[Url(as: 'type')]
    public string $type = '';

    /** @var array<string, string> */
    public array $form = ['type' => 'disbursement_voucher', 'beneficiary' => '', 'amount' => '', 'description' => ''];

    /** @var list<array<string, string>> */
    public array $lines = [];

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
                ->when($this->type, fn ($q) => $q->where('type', $this->type))->latest()->paginate(20),
            'types' => VoucherType::options(),
        ]);
    }
}
