<?php

namespace App\Http\Controllers;

use App\Livewire\Reception\Mail;
use App\Models\Department;
use App\Models\MailRecord;
use App\Models\Visitor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réception : scan joint au courrier (téléchargement contrôlé), impression
 * du registre du courrier et badge visiteur aux couleurs SOGEREF.
 */
class ReceptionController extends Controller
{
    /** Scan du courrier : disque privé, société courante uniquement. */
    public function mailFile(Request $request, MailRecord $mailRecord): Response
    {
        abort_unless((int) $mailRecord->company_id === company_id() && $mailRecord->file_path, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($mailRecord->file_path), 404);
        $name = str($mailRecord->reference)->slug().'.'.pathinfo($mailRecord->file_path, PATHINFO_EXTENSION);
        $mime = $disk->mimeType($mailRecord->file_path) ?: 'application/octet-stream';
        $inline = ! $request->boolean('download') && in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);

        return $inline
            ? response()->file($disk->path($mailRecord->file_path), ['Content-Type' => $mime, 'Content-Disposition' => 'inline; filename="'.$name.'"', 'X-Content-Type-Options' => 'nosniff'])
            : $disk->download($mailRecord->file_path, $name);
    }

    /** Registre du courrier (filtres de l'écran Courrier), papier à en-tête. */
    public function mailRegister(Request $request): Response
    {
        $filters = $request->only('sens', 'statut', 'du', 'au', 'service', 'q');
        $records = Mail::registerQuery(company_id(), array_map('strval', array_filter($filters, 'is_scalar')))->limit(1000)->get();
        $parts = array_filter([
            ['in' => 'Courrier entrant', 'out' => 'Courrier sortant'][$filters['sens'] ?? ''] ?? null,
            ! empty($filters['du']) ? 'du '.$this->date($filters['du']) : null,
            ! empty($filters['au']) ? 'au '.$this->date($filters['au']) : null,
            ! empty($filters['service']) ? Department::where('company_id', company_id())->whereKey((int) $filters['service'])->value('name') : null,
        ]);

        $pdf = Pdf::loadView('pdf.mail-register', [
            'records' => $records, 'company' => company(), 'docTitle' => 'REGISTRE DU COURRIER',
            'docReference' => 'Édité le '.now()->format('d/m/Y H:i'), 'docSubtitle' => e($parts ? ucfirst(implode(' · ', $parts)) : 'Tout le registre'),
        ])->setPaper('a4', 'landscape');

        return $request->boolean('download') ? $pdf->download('registre-courrier.pdf') : $pdf->stream('registre-courrier.pdf');
    }

    /** Badge visiteur (format carte 86 × 54 mm). */
    public function visitorBadge(Request $request, Visitor $visitor): Response
    {
        abort_unless((int) $visitor->company_id === company_id(), 404);
        $visitor->load('host');
        $pdf = Pdf::loadView('pdf.visitor-badge', ['visitor' => $visitor, 'company' => company()])->setPaper([0, 0, 243.78, 153.07]);
        $name = 'badge-'.str($visitor->badge_number ?: 'visiteur')->slug().'.pdf';

        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);
    }

    private function date(string $value): string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) ? "{$m[3]}/{$m[2]}/{$m[1]}" : $value;
    }
}
