<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Document;

class DocumentNumberService
{
    public function next(int $companyId, DocumentType $type): string
    {
        $prefix = match ($type) {
            DocumentType::Quote => 'DEV', DocumentType::Invoice => 'FAC',
            DocumentType::CreditNote => 'AVR', DocumentType::PurchaseOrder => 'ACH',
        };
        $year = now()->format('Y');
        $last = Document::withTrashed()->where('company_id', $companyId)->where('type', $type)->where('number', 'like', "{$prefix}-{$year}-%")->lockForUpdate()->latest('id')->value('number');
        $sequence = $last ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }
}
