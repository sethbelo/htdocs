<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use DomainException;

class ConvertQuoteToInvoice
{
    public function __construct(private readonly CreateDocument $creator) {}

    public function handle(Document $quote): Document
    {
        if ($quote->type !== DocumentType::Quote) {
            throw new DomainException('Seul un devis peut être converti.');
        }
        if ($quote->status === DocumentStatus::Rejected) {
            throw new DomainException('Un devis refusé ne peut pas être converti.');
        }

        $invoice = $this->creator->handle([
            'company_id' => $quote->company_id, 'branch_id' => $quote->branch_id,
            'contact_id' => $quote->contact_id, 'converted_from_id' => $quote->id,
            'created_by' => auth()->id(), 'type' => DocumentType::Invoice,
            'issue_date' => now()->toDateString(), 'due_date' => now()->addDays($quote->contact->payment_terms)->toDateString(),
            'currency_code' => $quote->currency_code, 'exchange_rate' => $quote->exchange_rate,
            'notes' => $quote->notes, 'terms' => $quote->terms, 'template' => $quote->template ?: 'modern',
        ], $quote->items->map->only(['product_id', 'description', 'quantity', 'unit', 'unit_price', 'discount_rate', 'tax_rate', 'position'])->all());

        $quote->update(['status' => DocumentStatus::Accepted, 'accepted_at' => now()]);

        return $invoice;
    }
}
