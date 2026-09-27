<?php

namespace App\Services\Approvals;

use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Notifications\ApprovalRequired;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Moteur d'approbation configurable : étapes séquentielles, déclenchées selon des seuils de montant.
 * Exemple : Demandeur → Responsable → Responsable désigné → Directeur Général (au-delà d'un seuil).
 */
class ApprovalService
{
    public function submit(Approvable&Model $approvable, User $requester): ApprovalRequest
    {
        $workflow = ApprovalWorkflow::query()->where('code', $approvable->approvalWorkflowCode())->where('is_active', true)->first()
            ?? throw new BusinessRuleException("Aucun circuit de validation actif pour « {$approvable->approvalWorkflowCode()} ».");

        if ($approvable->approvalRequest()->where('status', ApprovalStatus::Pending)->exists()) {
            throw new BusinessRuleException('Une validation est déjà en cours pour ce document.');
        }

        return DB::transaction(function () use ($approvable, $requester, $workflow): ApprovalRequest {
            $firstStep = $this->applicableSteps($workflow, $approvable->approvalAmount())->first();

            $request = ApprovalRequest::query()->create([
                'approval_workflow_id' => $workflow->id,
                'approvable_type' => $approvable->getMorphClass(),
                'approvable_id' => $approvable->getKey(),
                'amount' => $approvable->approvalAmount(),
                'status' => $firstStep ? ApprovalStatus::Pending : ApprovalStatus::Approved,
                'current_step_id' => $firstStep?->id,
                'requested_by' => $requester->id,
                'completed_at' => $firstStep ? null : now(),
            ]);

            $firstStep ? $this->notifyApprovers($firstStep, $request, $approvable) : $approvable->onApproved();

            return $request;
        });
    }

    public function approve(ApprovalRequest $request, User $user, ?string $comment = null): ApprovalRequest
    {
        $step = $this->authorizeAction($request, $user);

        return DB::transaction(function () use ($request, $user, $comment, $step): ApprovalRequest {
            $request->actions()->create([
                'approval_step_id' => $step->id,
                'user_id' => $user->id,
                'decision' => ApprovalStatus::Approved,
                'comment' => $comment,
            ]);

            $next = $this->applicableSteps($request->workflow, (float) $request->amount)
                ->first(fn (ApprovalStep $candidate): bool => $candidate->sequence > $step->sequence);

            /** @var Approvable&Model $approvable */
            $approvable = $request->approvable;

            if ($next) {
                $request->update(['current_step_id' => $next->id]);
                $this->notifyApprovers($next, $request, $approvable);
            } else {
                $request->update(['status' => ApprovalStatus::Approved, 'current_step_id' => null, 'completed_at' => now()]);
                $approvable->onApproved();
            }

            return $request;
        });
    }

    /**
     * Refus : motif et commentaire obligatoires, utilisateur et date enregistrés.
     */
    public function reject(ApprovalRequest $request, User $user, string $reason, string $comment): ApprovalRequest
    {
        if (blank($reason) || blank($comment)) {
            throw new BusinessRuleException('Le motif et le commentaire sont obligatoires pour un refus.');
        }

        $step = $this->authorizeAction($request, $user);

        return DB::transaction(function () use ($request, $user, $reason, $comment, $step): ApprovalRequest {
            $request->actions()->create([
                'approval_step_id' => $step->id,
                'user_id' => $user->id,
                'decision' => ApprovalStatus::Rejected,
                'reason' => $reason,
                'comment' => $comment,
            ]);

            $request->update(['status' => ApprovalStatus::Rejected, 'completed_at' => now()]);
            $request->approvable->onRejected($reason);

            return $request;
        });
    }

    /**
     * Demandes en attente sur lesquelles l'utilisateur peut agir.
     *
     * @return Collection<int, ApprovalRequest>
     */
    public function pendingFor(User $user): Collection
    {
        return ApprovalRequest::query()
            ->with('currentStep', 'approvable', 'requester', 'workflow')
            ->where('status', ApprovalStatus::Pending)
            ->latest()
            ->get()
            ->filter(fn (ApprovalRequest $request): bool => $request->currentStep?->canBeActedOnBy($user) ?? false)
            ->values();
    }

    /**
     * @return Collection<int, ApprovalStep>
     */
    private function applicableSteps(ApprovalWorkflow $workflow, float $amount): Collection
    {
        return $workflow->steps()->get()->filter(fn (ApprovalStep $step): bool => $amount >= (float) $step->min_amount)->values();
    }

    private function authorizeAction(ApprovalRequest $request, User $user): ApprovalStep
    {
        if ($request->status !== ApprovalStatus::Pending || ! $request->currentStep) {
            throw new BusinessRuleException('Cette demande n’est plus en attente.');
        }

        if (! $request->currentStep->canBeActedOnBy($user)) {
            throw new BusinessRuleException("Cette étape (« {$request->currentStep->name} ») ne vous est pas attribuée.");
        }

        if ($request->requested_by === $user->id && ! $user->hasRole('super-admin')) {
            throw new BusinessRuleException('Vous ne pouvez pas approuver votre propre demande.');
        }

        return $request->currentStep;
    }

    private function notifyApprovers(ApprovalStep $step, ApprovalRequest $request, Approvable $approvable): void
    {
        $recipients = $step->approverUser
            ? collect([$step->approverUser])
            : ($step->approver_role && Role::query()->where('name', $step->approver_role)->exists() ? User::role($step->approver_role)->where('is_active', true)->get() : collect());

        Notification::send($recipients, new ApprovalRequired($request, $approvable->approvalTitle(), $step->name));
    }
}
