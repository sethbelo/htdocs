<?php

namespace App\Exports;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly int $companyId,
        private readonly string $type = 'client',
        private readonly string $search = '',
    ) {}

    public function query(): Builder
    {
        return Contact::query()
            ->where('company_id', $this->companyId)
            ->where('type', $this->type)
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('company_name', 'like', "%{$this->search}%")
                ->orWhere('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('company_name')
            ->orderBy('last_name')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['Référence', 'Type', 'Société', 'Prénom', 'Nom', 'E-mail', 'Téléphone', 'NIF / Tax ID', 'Devise', 'Délai de paiement', 'Statut', 'Créé le'];
    }

    public function map(mixed $contact): array
    {
        return [
            $contact->reference,
            $contact->type->value,
            $contact->company_name,
            $contact->first_name,
            $contact->last_name,
            $contact->email,
            $contact->phone,
            $contact->tax_number,
            $contact->currency_code,
            $contact->payment_terms,
            $contact->is_active ? 'Actif' : 'Inactif',
            $contact->created_at?->format('Y-m-d H:i'),
        ];
    }
}
