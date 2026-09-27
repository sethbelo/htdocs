<?php

namespace App\Livewire\Cash;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Cash\Concerns\ManagesCashSession;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Order;
use App\Services\Cash\OrderCheckoutService;
use App\Services\Cash\PaymentData;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Caisse commandes')]
class OrderCheckout extends Component
{
    use InteractsWithToasts, ManagesCashSession;

    #[Url(as: 'commande')]
    public string $reference = '';

    public ?int $orderId = null;

    /** @var list<array{method: string, amount: string, reference: string}> */
    public array $payments = [];

    /** @var array{invoice_key: string, change: float, order_number: string}|null */
    public ?array $lastCheckout = null;

    public function mount(): void
    {
        if ($this->reference) {
            $this->lookup();
        }
    }

    public function lookup(?string $scanned = null): void
    {
        $term = trim($scanned ?? $this->reference);
        $this->reference = $term;
        $this->lastCheckout = null;

        $order = Order::query()->visibleTo(auth()->user())->where('number', $term)->first()
            ?? Order::query()->visibleTo(auth()->user())->where('number', 'like', "%{$term}")->latest()->first();

        if (! $order) {
            $this->orderId = null;
            $this->toast("Aucune commande « {$term} ».", 'warning');

            return;
        }

        $this->orderId = $order->id;
        $amount = $order->status === OrderStatus::AwaitingPayment ? max((float) $order->deposit_required - (float) $order->amount_paid, 0) : $order->balanceDue();
        $this->payments = [['method' => PaymentMethod::Cash->value, 'amount' => (string) ($amount ?: $order->balanceDue()), 'reference' => '']];
    }

    public function selectOrder(int $id): void
    {
        $this->orderId = $id;
        $this->reference = (string) Order::query()->whereKey($id)->value('number');
        $this->lookup();
    }

    public function addPayment(): void
    {
        $remaining = max(0, ($this->order?->balanceDue() ?? 0) - $this->tendered);
        $this->payments[] = ['method' => PaymentMethod::MobileMoney->value, 'amount' => (string) round($remaining, 2), 'reference' => ''];
    }

    public function removePayment(int $index): void
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    }

    public function payFull(): void
    {
        $this->payments = [['method' => $this->payments[0]['method'] ?? 'cash', 'amount' => (string) $this->order->balanceDue(), 'reference' => $this->payments[0]['reference'] ?? '']];
    }

    #[Computed]
    public function order(): ?Order
    {
        return $this->orderId ? Order::query()->with('client', 'items', 'salesRep', 'payments')->find($this->orderId) : null;
    }

    #[Computed]
    public function tendered(): float
    {
        return round(collect($this->payments)->sum(fn (array $p): float => (float) $p['amount']), 2);
    }

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function awaiting(): Collection
    {
        return Order::query()->visibleTo(auth()->user())->with('client')
            ->where('status', OrderStatus::AwaitingPayment)
            ->when($this->session, fn ($q) => $q->where('site_id', $this->session->register->site_id))
            ->latest('ordered_at')->limit(12)->get();
    }

    public function checkout(OrderCheckoutService $checkout): void
    {
        $this->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->attempt(fn () => $checkout->checkout($this->order, PaymentData::collection($this->payments), $this->session, auth()->user()));

        if ($result) {
            $this->lastCheckout = ['invoice_key' => $result['invoice']->getRouteKey(), 'change' => $result['change'], 'order_number' => $result['order']->number];
            $this->toast("Paiement enregistré — facture {$result['invoice']->number}. Statut : {$result['order']->status->label()}.");
            $this->js('window.open('.json_encode(route('print.invoice', [$result['invoice'], 'ticket'])).', "_blank")');
            $this->orderId = null;
            $this->reference = '';
            $this->payments = [];
            unset($this->order, $this->awaiting, $this->session);
        }
    }

    public function render()
    {
        return view('livewire.cash.order-checkout', ['methods' => PaymentMethod::options()]);
    }
}
