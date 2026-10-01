<?php

namespace App\Exports;

use App\Livewire\Crm\Directory;
use App\Services\ContactService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export Excel de l'annuaire (mêmes filtres que l'écran). */
class DirectoryExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private bool $whatsapp;

    public function __construct(private readonly int $companyId, private readonly array $filters = [])
    {
        $this->whatsapp = ContactService::hasColumn('whatsapp');
    }

    public function query(): Builder
    {
        return Directory::directoryQuery($this->companyId, $this->filters);
    }

    public function headings(): array
    {
        return array_values(array_filter(['Nom / Raison sociale', 'Interlocuteur', 'Type', 'Segment', 'Téléphone', $this->whatsapp ? 'WhatsApp' : null, 'E-mail', 'Ville', 'Adresse', 'Référence']));
    }

    public function map(mixed $contact): array
    {
        $row = [
            $contact->display_name,
            $contact->company_name ? trim($contact->first_name.' '.$contact->last_name) : '',
            $contact->type->label(),
            $contact->segment === 'b2b' ? 'Entreprise' : 'Particulier',
            $contact->phone,
        ];
        if ($this->whatsapp) {
            $row[] = $contact->whatsapp;
        }

        return [...$row, $contact->email, $contact->city, data_get($contact->billing_address, 'line1'), $contact->reference];
    }
}
