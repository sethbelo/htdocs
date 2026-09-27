<?php

namespace App\Services\Design;

use App\Enums\DesignJobStatus;
use App\Enums\OrderStatus;
use App\Enums\ProofStatus;
use App\Enums\ProofValidatorType;
use App\Exceptions\BusinessRuleException;
use App\Models\Proof;
use App\Models\User;
use App\Services\Sales\OrderWorkflow;
use App\Services\Sales\ProductionRouter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Bon À Tirer : validation tracée (qui, quand, version, commentaire) et verrouillage du fichier d'impression.
 */
class ProofService
{
    public function __construct(private OrderWorkflow $workflow, private ProductionRouter $router) {}

    public function approve(Proof $proof, ProofValidatorType $validatorType, User $recordedBy, ?string $validatorName = null, ?string $comment = null): Proof
    {
        $this->ensurePending($proof);

        if ($validatorType === ProofValidatorType::Manager && ! $recordedBy->can('proofs.approve')) {
            throw new BusinessRuleException('Validation réservée aux responsables autorisés.');
        }

        if ($validatorType === ProofValidatorType::SalesRep && ! $recordedBy->can('proofs.validate')) {
            throw new BusinessRuleException('Vous n’êtes pas autorisé à valider un BAT.');
        }

        if ($validatorType === ProofValidatorType::Client && blank($validatorName)) {
            throw new BusinessRuleException('Indiquez le nom du client ayant validé le BAT.');
        }

        return DB::transaction(function () use ($proof, $validatorType, $recordedBy, $validatorName, $comment): Proof {
            $version = $proof->version;
            $lockedPath = "proofs/locked/{$proof->number}/v{$version->version}-".basename($version->path);
            $disk = Storage::disk($version->disk);

            if ($disk->exists($version->path)) {
                $disk->copy($version->path, $lockedPath);
            }

            $proof->update([
                'status' => ProofStatus::Approved,
                'validator_type' => $validatorType,
                'validated_by' => $recordedBy->id,
                'validator_name' => $validatorName ?? $recordedBy->name,
                'validated_at' => now(),
                'comment' => $comment,
                'locked_path' => $lockedPath,
                'locked_checksum' => $disk->exists($lockedPath) ? hash('sha256', (string) $disk->get($lockedPath)) : null,
                'is_locked' => true,
            ]);

            $proof->designJob->update(['status' => DesignJobStatus::SentToPrint, 'completed_at' => now()]);

            $order = $proof->order;
            $this->workflow->advanceThrough($order, [OrderStatus::Validated, OrderStatus::PrintQueue], $recordedBy, "BAT {$proof->number} validé par {$validatorType->label()}");
            $this->router->createPrintJobs($order->load('items'), $proof->id);

            return $proof;
        });
    }

    public function reject(Proof $proof, User $user, string $comment): Proof
    {
        $this->ensurePending($proof);

        if (blank($comment)) {
            throw new BusinessRuleException('Le motif du refus est obligatoire.');
        }

        return DB::transaction(function () use ($proof, $user, $comment): Proof {
            $proof->update(['status' => ProofStatus::Rejected, 'comment' => $comment, 'validated_by' => $user->id, 'validated_at' => now()]);
            $proof->designJob->update(['status' => DesignJobStatus::Correction]);

            if ($proof->order->status === OrderStatus::ProofPending) {
                $this->workflow->transition($proof->order, OrderStatus::InDesign, $user, "BAT {$proof->number} refusé : {$comment}");
            }

            return $proof;
        });
    }

    private function ensurePending(Proof $proof): void
    {
        if ($proof->status !== ProofStatus::Pending || $proof->is_locked) {
            throw new BusinessRuleException('Ce BAT a déjà été traité.');
        }
    }
}
