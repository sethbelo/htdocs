<?php

namespace App\Exports;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly int $companyId, private readonly ?string $type = null) {}

    public function query(): Builder
    {
        return Document::query()->with('contact')->where('company_id', $this->companyId)->when($this->type, fn ($q) => $q->where('type', $this->type))->orderBy('id');
    }

    public function headings(): array
    {
        return ['Numéro', 'Type', 'Client', 'Date', 'Échéance', 'Statut', 'Devise', 'Taux historique', 'HT', 'TVA', 'TTC', 'Payé', 'Solde'];
    }

    public function map(mixed $document): array
    {
        return [$document->number, $document->type->label(), $document->contact->display_name, $document->issue_date->format('Y-m-d'), $document->due_date?->format('Y-m-d'), $document->status->label(), $document->currency_code, $document->exchange_rate, $document->subtotal, $document->tax_total, $document->total, $document->amount_paid, $document->balance_due];
    }
}
