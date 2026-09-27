<?php

namespace App\Services\Design;

use App\Enums\DesignJobStatus;
use App\Enums\DesignNoteType;
use App\Exceptions\BusinessRuleException;
use App\Models\DesignBrief;
use App\Models\DesignJob;
use App\Models\DesignNote;
use App\Models\User;
use App\Notifications\OrderNeedsAttention;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Cahier de conception : brief créatif, notes de travail et feu vert donné à la salle d'impression
 * (format, dimensions et spécifications finales du fichier à imprimer).
 */
class DesignNotebookService
{
    /**
     * Brief du travail, pré-rempli depuis les spécifications de la commande à la première ouverture.
     */
    public function briefFor(DesignJob $job): DesignBrief
    {
        return $job->brief ?? DB::transaction(function () use ($job): DesignBrief {
            $item = $job->order->items()->first();
            $toMm = fn ($value) => $value === null ? null : match ($item?->dimension_unit) {
                'cm' => (float) $value * 10,
                'm' => (float) $value * 1000,
                default => (float) $value,
            };

            return $job->brief()->create([
                'title' => $item?->description ?? "Commande {$job->order->number}",
                'final_format' => $item?->format,
                'width_mm' => $toMm($item?->width),
                'bleed_mm' => 3,
                'orientation' => 'portrait',
                'height_mm' => $toMm($item?->height),
                'colors' => $item?->colors,
                'paper' => $item?->paper,
                'grammage' => $item?->grammage,
                'finishing' => collect([$item?->finishing, $item?->lamination ? 'Plastification' : null, $item?->cutting ? "Découpe {$item->cutting}" : null])->filter()->implode(' · ') ?: null,
                'quantity' => $item ? (int) ceil((float) $item->quantity) : null,
                'pages' => $item?->pages,
                'checklist' => [],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveBrief(DesignJob $job, array $data): DesignBrief
    {
        $brief = $this->briefFor($job);

        if ($brief->isValidatedForPrint()) {
            throw new BusinessRuleException('La fiche a été transmise à l’impression : elle est verrouillée.');
        }

        $brief->update($data);

        return $brief;
    }

    public function addNote(DesignJob $job, User $user, DesignNoteType $type, string $body): DesignNote
    {
        if (blank($body)) {
            throw new BusinessRuleException('La note est vide.');
        }

        return $job->notes()->create(['user_id' => $user->id, 'type' => $type, 'body' => trim($body)]);
    }

    public function toggleTodo(DesignNote $note): DesignNote
    {
        $note->update(['is_done' => ! $note->is_done]);

        return $note;
    }

    /**
     * Conception terminée : la fiche technique est verrouillée et la salle d'impression est prévenue
     * que le fichier peut être imprimé, avec le format et les dimensions à respecter.
     */
    public function validateForPrint(DesignJob $job, User $user): DesignBrief
    {
        $brief = $this->briefFor($job);

        if ($brief->isValidatedForPrint()) {
            throw new BusinessRuleException('Conception déjà transmise à l’impression.');
        }

        if (! in_array($job->status, [DesignJobStatus::Validated, DesignJobStatus::SentToPrint], true)) {
            throw new BusinessRuleException('Le BAT doit être validé par le client avant de transmettre à l’impression.');
        }

        if (! $job->latestVersion) {
            throw new BusinessRuleException('Déposez le fichier final avant de valider la conception.');
        }

        $missing = collect(['width_mm' => 'largeur', 'height_mm' => 'hauteur', 'quantity' => 'quantité', 'colors' => 'couleurs'])
            ->filter(fn (string $label, string $field): bool => blank($brief->{$field}))
            ->values();

        if ($missing->isNotEmpty()) {
            throw new BusinessRuleException('Complétez la fiche technique : '.$missing->implode(', ').'.');
        }

        $unchecked = collect(DesignBrief::CHECKLIST)->reject(fn (string $label, string $key): bool => (bool) ($brief->checklist[$key] ?? false));

        if ($unchecked->isNotEmpty()) {
            throw new BusinessRuleException('Contrôles pré-presse non cochés : '.$unchecked->implode(', ').'.');
        }

        return DB::transaction(function () use ($job, $brief, $user): DesignBrief {
            $brief->update([
                'validated_for_print_by' => $user->id,
                'validated_for_print_at' => now(),
                'final_file_name' => $brief->final_file_name ?: $job->latestVersion->original_name,
            ]);

            $order = $job->order;
            $message = "Conception terminée par {$user->name} — prête à imprimer : {$brief->printSummary()}";
            $order->comments()->create(['user_id' => $user->id, 'body' => $message]);

            $printRoom = User::permission('production.view')->where('is_active', true)->get()
                ->filter(fn (User $member): bool => $member->canAccessSite($order->site_id) && $member->isNot($user));
            Notification::send($printRoom, new OrderNeedsAttention($order, $message));

            return $brief;
        });
    }
}
