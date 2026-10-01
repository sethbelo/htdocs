<?php

namespace App\Http\Controllers;

use App\Exports\DirectoryExport;
use App\Livewire\Crm\Directory;
use App\Models\Contact;
use App\Services\ContactService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Annuaire : carte de visite vCard (.vcf) par contact, export Excel et
 * impression PDF (papier à en-tête SOGEREF) avec les filtres de l'écran.
 */
class DirectoryController extends Controller
{
    public function vcard(Contact $contact): Response
    {
        abort_unless((int) $contact->company_id === company_id(), 404);
        $e = fn (?string $v) => str_replace(['\\', "\r\n", "\n", ',', ';'], ['\\\\', '\\n', '\\n', '\\,', '\;'], (string) $v);
        $address = $contact->billing_address ?? [];
        $whatsapp = ContactService::hasColumn('whatsapp') ? $contact->whatsapp : null;

        $lines = array_filter([
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:'.$e($contact->last_name ?: ($contact->first_name ? '' : $contact->company_name)).';'.$e($contact->first_name).';;;',
            'FN:'.$e(trim($contact->first_name.' '.$contact->last_name) ?: $contact->display_name),
            $contact->company_name ? 'ORG:'.$e($contact->company_name) : null,
            $contact->phone ? 'TEL;TYPE=CELL,VOICE:'.$e($contact->phone) : null,
            $whatsapp && $whatsapp !== $contact->phone ? 'TEL;TYPE=CELL:'.$e($whatsapp) : null,
            $contact->email ? 'EMAIL;TYPE=INTERNET:'.$e($contact->email) : null,
            ($address['line1'] ?? null) || $contact->city ? 'ADR;TYPE=WORK:;;'.$e($address['line1'] ?? '').';'.$e($contact->city).';;;'.$e($address['country'] ?? 'RDC') : null,
            'NOTE:'.$e($contact->type->label().' SOGEREF · '.$contact->reference),
            'REV:'.($contact->updated_at ?? now())->utc()->format('Ymd\THis\Z'),
            'END:VCARD',
        ]);

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.(str($contact->display_name)->slug()->whenEmpty(fn () => str('contact'))).'.vcf"',
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(new DirectoryExport(company_id(), $this->filters($request)), 'annuaire-sogeref-'.now()->format('Ymd').'.xlsx');
    }

    public function print(Request $request): Response
    {
        $filters = $this->filters($request);
        $contacts = Directory::directoryQuery(company_id(), $filters)->limit(2000)->get();
        $pdf = Pdf::loadView('pdf.directory', [
            'contacts' => $contacts, 'company' => company(), 'docTitle' => 'ANNUAIRE', 'docReference' => $contacts->count().' contact(s)', 'docSubtitle' => 'Édité le '.now()->format('d/m/Y'),
            'hasWhatsapp' => ContactService::hasColumn('whatsapp'),
        ])->setPaper('a4', 'landscape');

        return $request->boolean('download') ? $pdf->download('annuaire-sogeref.pdf') : $pdf->stream('annuaire-sogeref.pdf');
    }

    private function filters(Request $request): array
    {
        return array_map('strval', array_filter($request->only('lettre', 'q', 'type', 'segment', 'ville'), 'is_scalar'));
    }
}
