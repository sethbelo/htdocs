<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use DomainException;
use Illuminate\Support\Facades\DB;

class PostJournalEntry
{
    public function handle(array $entry, array $lines): JournalEntry
    {
        $debit = round(array_sum(array_column($lines, 'debit')), 2);
        $credit = round(array_sum(array_column($lines, 'credit')), 2);
        if ($debit <= 0 || $debit !== $credit) {
            throw new DomainException('Une écriture doit être équilibrée au centime près.');
        }

        return DB::transaction(function () use ($entry, $lines): JournalEntry {
            // ZONE DE SYNCHRONISATION : une source métier met à jour sa propre
            // écriture au lieu de créer des doublons à chaque modification.
            $model = isset($entry['source_type'], $entry['source_id'])
                ? JournalEntry::query()
                    ->where('source_type', $entry['source_type'])
                    ->where('source_id', $entry['source_id'])
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($model) {
                $model->update($entry + ['status' => 'posted', 'posted_at' => now()]);
                $model->lines()->delete();
            } else {
                $model = JournalEntry::create($entry + ['status' => 'posted', 'posted_at' => now()]);
            }

            $model->lines()->createMany($lines);

            return $model->load('lines');
        });
    }
}
