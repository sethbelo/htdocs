<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\LoyaltyCard;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $loyaltyFilter = '';

    public string $statusFilter = '';

    public string $viewMode = 'list';

    public bool $showDeleteModal = false;

    public ?string $deleteId = null;

    public string $deleteName = '';

    public bool $showCardPanel = false;

    public string $cardPanelMode = 'issue';

    public string $card_customer_id = '';

    public string $card_customer_name = '';

    public string $card_number = '';

    public string $card_type = 'simple';

    public string $card_token = '';

    public string $card_nfc_uid = '';

    public string $card_reader_type = '';

    public string $card_status = 'active';

    public string $nfc_message = '';

    public string $detected_customer_name = '';

    protected $queryString = ['search', 'loyaltyFilter', 'statusFilter', 'viewMode'];

    public function mount(): void
    {
        if (! in_array($this->viewMode, ['list', 'grid'], true)) {
            $this->viewMode = 'list';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLoyaltyFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['list', 'grid'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function openCardPanel(?string $customerId = null): void
    {
        abort_unless(
            $customerId ? auth()->user()->can('loyalty.manage') : auth()->user()->can('loyalty.view'),
            403
        );
        $this->resetValidation();
        $this->resetCardState();

        if (! $customerId) {
            $this->cardPanelMode = 'read';
            $this->nfc_message = 'Présentez une carte au lecteur pour identifier le client.';
            $this->showCardPanel = true;

            return;
        }

        $customer = Customer::with('loyaltyCard')->findOrFail($customerId);
        $this->card_customer_id = $customer->id;
        $this->card_customer_name = $customer->full_name;
        $card = $customer->loyaltyCard;
        $this->card_number = $card?->card_number ?? $this->generateCardNumber();
        $this->card_type = $card?->type ?? 'simple';
        $this->card_token = $card?->token ?? Str::random(48);
        $this->card_nfc_uid = $card?->nfc_uid ?? '';
        $this->card_reader_type = $card?->reader_type ?? '';
        $this->card_status = $card?->status ?? 'active';
        $this->nfc_message = $card?->isNfc()
            ? 'Carte NFC existante. Vous pouvez la réécrire ou enregistrer ses paramètres.'
            : 'Choisissez une carte simple ou NFC.';
        $this->showCardPanel = true;
    }

    public function nfcCardWritten(string $uid, string $readerType = 'acs'): void
    {
        $this->card_nfc_uid = trim($uid);
        $this->card_reader_type = $readerType;
        $this->card_type = 'nfc';
        $this->nfc_message = 'Carte détectée et écrite avec succès. Enregistrez maintenant son attribution.';
    }

    public function nfcCardRead(string $uid = '', string $token = ''): void
    {
        abort_unless(auth()->user()->can('loyalty.view'), 403);

        $card = LoyaltyCard::with('customer')
            ->where(function ($query) use ($uid, $token) {
                if ($uid !== '') {
                    $query->where('nfc_uid', $uid);
                }
                if ($token !== '') {
                    $uid !== '' ? $query->orWhere('token', $token) : $query->where('token', $token);
                }
            })->first();

        if (! $card) {
            $this->nfc_message = 'Carte lue, mais aucune attribution n’a été trouvée dans cet établissement.';
            $this->detected_customer_name = '';

            return;
        }

        $card->update(['last_read_at' => now()]);
        $this->detected_customer_name = $card->customer->full_name;
        $this->card_customer_id = $card->customer_id;
        $this->card_customer_name = $card->customer->full_name;
        $this->card_number = $card->card_number;
        $this->card_type = $card->type;
        $this->card_token = $card->token;
        $this->card_nfc_uid = $card->nfc_uid ?? $uid;
        $this->card_reader_type = $card->reader_type ?? '';
        $this->card_status = $card->status;
        $this->nfc_message = "Carte reconnue : {$card->customer->full_name}, {$card->customer->loyalty_points} point(s).";
    }

    public function nfcFailed(string $message): void
    {
        $this->nfc_message = $message;
        $this->addError('nfc', $message);
    }

    public function saveLoyaltyCard(): void
    {
        abort_unless(auth()->user()->can('loyalty.manage'), 403);
        $establishmentId = auth()->user()->establishment_id;
        $existing = LoyaltyCard::where('customer_id', $this->card_customer_id)->first();

        $validated = $this->validate([
            'card_customer_id' => ['required', Rule::exists('customers', 'id')->where('establishment_id', $establishmentId)],
            'card_number' => ['required', 'string', 'max:32', Rule::unique('loyalty_cards', 'card_number')->where('establishment_id', $establishmentId)->ignore($existing?->id)],
            'card_type' => ['required', 'in:simple,nfc'],
            'card_nfc_uid' => ['nullable', 'required_if:card_type,nfc', 'string', 'max:100'],
            'card_status' => ['required', 'in:active,blocked'],
        ], [], [
            'card_customer_id' => 'client', 'card_number' => 'numéro de carte',
            'card_nfc_uid' => 'identifiant NFC', 'card_status' => 'statut',
        ]);

        LoyaltyCard::updateOrCreate(
            ['customer_id' => $validated['card_customer_id']],
            [
                'establishment_id' => $establishmentId,
                'card_number' => $validated['card_number'],
                'type' => $validated['card_type'],
                'nfc_uid' => $validated['card_type'] === 'nfc' ? $validated['card_nfc_uid'] : null,
                'token' => $this->card_token ?: Str::random(48),
                'reader_type' => $validated['card_type'] === 'nfc' ? ($this->card_reader_type ?: 'acs') : null,
                'status' => $validated['card_status'],
                'issued_at' => $existing?->issued_at ?? now(),
                'created_by' => $existing?->created_by ?? auth()->id(),
            ]
        );

        $this->showCardPanel = false;
        session()->flash('success', 'Carte de fidélité enregistrée pour '.$this->card_customer_name.'.');
    }

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('customers.delete'), 403);
        $customer = Customer::findOrFail($id);
        $this->deleteId = $customer->id;
        $this->deleteName = $customer->full_name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('customers.delete'), 403);
        Customer::findOrFail($this->deleteId)->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Client supprimé.');
    }

    public function toggleStatus(string $id): void
    {
        abort_unless(auth()->user()->can('customers.edit'), 403);
        $customer = Customer::findOrFail($id);
        $customer->update(['is_active' => ! $customer->is_active]);
    }

    private function resetCardState(): void
    {
        $this->reset([
            'card_customer_id', 'card_customer_name', 'card_number', 'card_nfc_uid',
            'card_reader_type', 'nfc_message', 'detected_customer_name',
        ]);
        $this->cardPanelMode = 'issue';
        $this->card_type = 'simple';
        $this->card_token = Str::random(48);
        $this->card_status = 'active';
    }

    private function generateCardNumber(): string
    {
        do {
            $number = 'YBX-'.now()->format('y').'-'.random_int(10000000, 99999999);
        } while (LoyaltyCard::where('card_number', $number)->exists());

        return $number;
    }

    public function render()
    {
        $customers = Customer::query()
            ->forEstablishment()
            ->with('loyaltyCard')
            ->search($this->search)
            ->when($this->loyaltyFilter, fn ($query) => $query->where('loyalty_level', $this->loyaltyFilter))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($this->viewMode === 'grid' ? 12 : 15);

        $cardCount = LoyaltyCard::where('status', 'active')->count();

        return view('livewire.admin.customers.index', compact('customers', 'cardCount'))
            ->layout('layouts.admin', ['title' => 'Clients', 'breadcrumb' => 'Clients']);
    }
}
