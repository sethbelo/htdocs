<?php

namespace App\Services\Imports;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactImportService
{
    public function import(int $companyId, iterable $rows, array $mapping): array
    {
        $processed = $failed = 0;
        $errors = [];
        DB::transaction(function () use ($companyId, $rows, $mapping, &$processed, &$failed, &$errors): void {
            foreach ($rows as $index => $row) {
                try {
                    $value = fn (string $field, $default = null) => isset($mapping[$field]) && $mapping[$field] !== '' ? ($row[$mapping[$field]] ?? $default) : $default;
                    $name = trim((string) $value('company_name'));
                    if ($name === '') {
                        throw new \InvalidArgumentException('La raison sociale est obligatoire.');
                    }
                    Contact::updateOrCreate(['company_id' => $companyId, 'email' => $value('email')], ['type' => $value('type', 'client'), 'reference' => $value('reference', 'IMP-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT)), 'company_name' => $name, 'phone' => $value('phone'), 'currency_code' => $value('currency_code', 'USD'), 'payment_terms' => (int) $value('payment_terms', 30)]);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = ['row' => $index + 2, 'message' => $e->getMessage()];
                }
            }
        });

        return compact('processed', 'failed', 'errors');
    }
}
