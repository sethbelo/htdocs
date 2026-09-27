<?php

namespace App\Services\Design;

use App\Enums\DesignJobStatus;
use App\Enums\OrderStatus;
use App\Enums\ProofStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\DesignJob;
use App\Models\DesignVersion;
use App\Models\Proof;
use App\Models\User;
use App\Notifications\OrderNeedsAttention;
use App\Services\Sales\OrderWorkflow;
use App\Services\Support\NumberGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Atelier graphique : À concevoir → En conception → Proposition → Correction → BAT → Validée → Impression.
 */
class DesignService
{
    public function __construct(private OrderWorkflow $workflow, private NumberGenerator $numbers) {}

    public function assign(DesignJob $job, User $designer, User $assignedBy): DesignJob
    {
        if (! $designer->can('design.work')) {
            throw new BusinessRuleException("{$designer->name} n’est pas graphiste.");
        }

        $job->update([
            'designer_id' => $designer->id,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'status' => $job->status === DesignJobStatus::Blocked ? DesignJobStatus::ToDesign : $job->status,
        ]);

        if ($designer->isNot($assignedBy)) {
            $designer->notify(new OrderNeedsAttention($job->order, "Commande {$job->order->number} assignée à vous par {$assignedBy->name}."));
        }

        return $job;
    }

    public function start(DesignJob $job, User $designer): DesignJob
    {
        $this->ensureStatus($job, [DesignJobStatus::ToDesign, DesignJobStatus::Correction]);

        $job->update([
            'designer_id' => $job->designer_id ?? $designer->id,
            'assigned_at' => $job->assigned_at ?? now(),
            'started_at' => $job->started_at ?? now(),
            'status' => DesignJobStatus::InProgress,
        ]);

        return $job;
    }

    /**
     * Dépôt d'une proposition : chaque dépôt crée une nouvelle version, l'historique est conservé.
     */
    public function uploadVersion(DesignJob $job, UploadedFile $file, User $designer, ?string $comment = null): DesignVersion
    {
        $this->ensureStatus($job, [DesignJobStatus::InProgress, DesignJobStatus::Correction, DesignJobStatus::Proposal]);

        return DB::transaction(function () use ($job, $file, $designer, $comment): DesignVersion {
            $version = (int) $job->versions()->max('version') + 1;
            $path = $file->storeAs("orders/{$job->order_id}/design", "v{$version}-".$file->hashName(), 'local');

            $designVersion = $job->versions()->create([
                'version' => $version,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'comment' => $comment,
                'uploaded_by' => $designer->id,
            ]);

            $job->update(['status' => DesignJobStatus::Proposal]);

            return $designVersion;
        });
    }

    public function requestCorrection(DesignJob $job, User $user, string $comment): DesignJob
    {
        $this->ensureStatus($job, [DesignJobStatus::Proposal, DesignJobStatus::Proof]);

        $job->update(['status' => DesignJobStatus::Correction]);
        $job->order->comments()->create(['user_id' => $user->id, 'body' => "Demande de correction : {$comment}"]);

        return $job;
    }

    /**
     * Génère le BAT à partir d'une version : la commande passe au statut « BAT ».
     */
    public function generateProof(DesignJob $job, DesignVersion $version, User $user): Proof
    {
        $this->ensureStatus($job, [DesignJobStatus::Proposal]);

        if ($version->design_job_id !== $job->id) {
            throw new BusinessRuleException('Cette version n’appartient pas à ce travail.');
        }

        return DB::transaction(function () use ($job, $version, $user): Proof {
            $job->proofs()->where('status', ProofStatus::Pending)->update(['status' => ProofStatus::Rejected, 'comment' => 'Remplacé par un nouveau BAT']);

            $proof = $job->proofs()->create([
                'number' => $this->numbers->next(NumberGenerator::Proof),
                'order_id' => $job->order_id,
                'design_version_id' => $version->id,
                'status' => ProofStatus::Pending,
                'created_by' => $user->id,
            ]);

            $job->update(['status' => DesignJobStatus::Proof]);

            if ($job->order->status === OrderStatus::InDesign) {
                $this->workflow->transition($job->order, OrderStatus::ProofPending, $user, "BAT {$proof->number} (v{$version->version}) soumis à validation");
            }

            return $proof;
        });
    }

    /**
     * Le graphiste signale une impossibilité (motif + rapport obligatoires).
     */
    public function block(DesignJob $job, User $designer, string $reason, string $report): DesignJob
    {
        $this->ensureReasonAndReport($reason, $report);

        $job->update(['status' => DesignJobStatus::Blocked, 'blocked_reason' => $reason, 'report' => $report]);
        $job->order->comments()->create(['user_id' => $designer->id, 'body' => "Impossibilité signalée : {$reason}\n{$report}"]);

        return $job;
    }

    /**
     * Le graphiste annule son intervention : le travail retourne dans la file « À concevoir ».
     */
    public function withdraw(DesignJob $job, User $designer, string $reason, string $report): DesignJob
    {
        $this->ensureReasonAndReport($reason, $report);

        $job->update([
            'status' => DesignJobStatus::ToDesign,
            'designer_id' => null,
            'assigned_at' => null,
            'blocked_reason' => $reason,
            'report' => $report,
        ]);
        $job->order->comments()->create(['user_id' => $designer->id, 'body' => "Intervention annulée : {$reason}\n{$report}"]);

        return $job;
    }

    /**
     * @param  list<DesignJobStatus>  $allowed
     */
    private function ensureStatus(DesignJob $job, array $allowed): void
    {
        if (! in_array($job->status, $allowed, true)) {
            throw new BusinessRuleException("Action impossible au statut « {$job->status->label()} ».");
        }
    }

    private function ensureReasonAndReport(string $reason, string $report): void
    {
        if (blank($reason) || blank($report)) {
            throw new BusinessRuleException('Le motif et le rapport sont obligatoires.');
        }
    }
}
