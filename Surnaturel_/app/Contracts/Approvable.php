<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Document soumis au moteur d'approbation configurable (DA, notes de frais, bons de décaissement…).
 */
interface Approvable
{
    public function approvalWorkflowCode(): string;

    public function approvalAmount(): float;

    public function approvalTitle(): string;

    public function approvalRequest(): MorphOne;

    public function onApproved(): void;

    public function onRejected(string $reason): void;
}
