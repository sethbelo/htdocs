<?php

namespace App\Livewire\Orders;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\ProofValidatorType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Order;
use App\Models\Proof;
use App\Models\User;
use App\Services\Collaboration\CommentService;
use App\Services\Delivery\DeliveryService;
use App\Services\Design\ProofService;
use App\Services\Sales\OrderWorkflow;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Commande')]
class Show extends Component
{
    use InteractsWithToasts, WithFileUploads;

    public Order $order;

    public string $comment = '';

    public string $transitionComment = '';

    public string $targetStatus = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    public ?int $proofId = null;

    public string $proofValidator = 'client';

    public string $proofValidatorName = '';

    public string $proofComment = '';

    /** @var array<string, string> */
    public array $delivery = ['mode' => 'pickup', 'driver_id' => '', 'scheduled_at' => '', 'address' => '', 'phone' => '', 'fee' => '0'];

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
        $this->delivery['address'] = (string) $order->client->address;
        $this->delivery['phone'] = (string) $order->client->phone;
    }

    public function changeStatus(OrderWorkflow $workflow): void
    {
        $target = OrderStatus::tryFrom($this->targetStatus);
        abort_unless($target, 422);

        $permission = match ($target) {
            OrderStatus::Cancelled => 'cancel',
            default => 'update',
        };
        $this->authorize($permission, $this->order);

        if ($this->attempt(fn () => $workflow->transition($this->order, $target, auth()->user(), $this->transitionComment ?: null), "Commande passée au statut « {$target->label()} ».")) {
            $this->reset('transitionComment', 'targetStatus');
            $this->modal('transition')->close();
        }
    }

    public function resume(OrderWorkflow $workflow): void
    {
        $this->authorize('update', $this->order);
        $this->attempt(fn () => $workflow->resume($this->order, auth()->user()), 'Commande reprise.');
    }

    public function openTransition(string $status): void
    {
        $this->targetStatus = $status;
        $this->modal('transition')->show();
    }

    public function postComment(CommentService $comments): void
    {
        $this->validate(['comment' => ['required', 'string', 'max:3000']]);
        $comments->post($this->order, auth()->user(), $this->comment);
        $this->reset('comment');
    }

    public function uploadFiles(): void
    {
        $this->authorize('update', $this->order);
        $this->validate(['uploads' => ['required', 'array'], 'uploads.*' => ['file', 'max:204800']], [], ['uploads.*' => 'fichier']);

        foreach ($this->uploads as $file) {
            $this->order->files()->create([
                'disk' => 'local',
                'path' => $file->store("orders/{$this->order->id}/client", 'local'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->reset('uploads');
        $this->toast('Fichiers ajoutés.');
    }

    public function openProof(int $proofId): void
    {
        $this->proofId = $proofId;
        $this->proofValidatorName = $this->order->client->name;
        $this->modal('proof')->show();
    }

    public function approveProof(ProofService $proofs): void
    {
        $this->validate(['proofValidator' => ['required', Rule::enum(ProofValidatorType::class)], 'proofComment' => ['nullable', 'string', 'max:2000']]);
        $proof = Proof::query()->where('order_id', $this->order->id)->findOrFail($this->proofId);

        if ($this->attempt(fn () => $proofs->approve($proof, ProofValidatorType::from($this->proofValidator), auth()->user(), $this->proofValidatorName ?: null, $this->proofComment ?: null), 'BAT validé : version verrouillée et transmise à l’impression.')) {
            $this->modal('proof')->close();
            $this->reset('proofId', 'proofComment');
        }
    }

    public function rejectProof(ProofService $proofs): void
    {
        $this->validate(['proofComment' => ['required', 'string', 'max:2000']], [], ['proofComment' => 'motif']);
        $proof = Proof::query()->where('order_id', $this->order->id)->findOrFail($this->proofId);

        if ($this->attempt(fn () => $proofs->reject($proof, auth()->user(), $this->proofComment), 'BAT refusé : retour en correction.')) {
            $this->modal('proof')->close();
            $this->reset('proofId', 'proofComment');
        }
    }

    public function scheduleDelivery(DeliveryService $deliveries): void
    {
        $this->authorize('deliveries.create');

        $data = $this->validate([
            'delivery.mode' => ['required', Rule::enum(DeliveryMode::class)],
            'delivery.driver_id' => ['nullable', 'exists:users,id'],
            'delivery.scheduled_at' => ['nullable', 'date'],
            'delivery.address' => ['nullable', 'string', 'max:255'],
            'delivery.phone' => ['nullable', 'string', 'max:30'],
            'delivery.fee' => ['nullable', 'numeric', 'min:0'],
        ])['delivery'];

        $mode = DeliveryMode::from($data['mode']);
        $payload = array_filter($data, fn ($value, $key) => $key !== 'mode' && $value !== '' && $value !== null, ARRAY_FILTER_USE_BOTH);

        if ($this->attempt(fn () => $deliveries->schedule($this->order, $mode, $payload, auth()->user()), 'Livraison planifiée.')) {
            $this->modal('delivery')->close();
        }
    }

    public function render()
    {
        $this->order->load([
            'client', 'salesRep', 'site', 'items', 'files.uploader', 'payments.receiver', 'invoices',
            'designJob.designer', 'designJob.versions.uploader', 'proofs.version', 'proofs.validator',
            'printJobs.machine', 'printJobs.operator', 'deliveries.driver', 'statusHistories.user', 'comments.user',
        ]);

        $possibleTransitions = collect($this->order->status->allowedTransitions())
            ->reject(fn (OrderStatus $status): bool => in_array($status, [OrderStatus::Paid, OrderStatus::InDesign, OrderStatus::ProofPending, OrderStatus::Validated, OrderStatus::PrintQueue, OrderStatus::Printing, OrderStatus::QualityCheck, OrderStatus::Ready, OrderStatus::OutForDelivery, OrderStatus::Delivered], true));

        return view('livewire.orders.show', [
            'flow' => OrderStatus::mainFlow(),
            'possibleTransitions' => $possibleTransitions,
            'drivers' => User::permission('deliveries.update')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'mentionables' => User::query()->where('is_active', true)->orderBy('username')->pluck('username'),
        ])->title("Commande {$this->order->number}");
    }
}
