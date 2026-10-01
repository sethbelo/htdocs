<?php

namespace App\Http\Controllers;

use App\Exports\PersonalSheetExport;
use App\Models\PersonalNote;
use App\Models\PersonalSheet;
use App\Models\PersonalTask;
use App\Models\Voucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impressions et exports de l'Espace personnel (propriétaire uniquement) :
 * notes et tableurs (impression navigateur, PDF à en-tête SOGEREF, Excel),
 * bons émis par l'employé et liste des tâches du jour.
 */
class PersonalExportController extends Controller
{
    // ── Notes ─────────────────────────────────────────────────────────────
    public function notePrint(PersonalNote $note)
    {
        $this->owner($note);

        return view('personal.print.note', ['note' => $note]);
    }

    public function notePdf(Request $request, PersonalNote $note): Response
    {
        $this->owner($note);
        $html = preg_replace(
            ['#<li data-checked="true">#', '#<li data-checked="false">#'],
            ['<li class="check done"><span class="chk">&#9745;</span> ', '<li class="check"><span class="chk">&#9744;</span> '],
            (string) $note->content,
        );

        return $this->pdf('pdf.personal-note', ['note' => $note, 'html' => $html, 'docTitle' => 'NOTE', 'docReference' => str($note->title)->limit(60)->toString(), 'docSubtitle' => 'Mise à jour le '.$note->updated_at->format('d/m/Y H:i')], $note->title, $request);
    }

    // ── Tableurs ──────────────────────────────────────────────────────────
    public function sheetPrint(PersonalSheet $sheet)
    {
        $this->owner($sheet);

        return view('personal.print.sheet', ['sheet' => $sheet, 'grid' => $sheet->matrix()]);
    }

    public function sheetPdf(Request $request, PersonalSheet $sheet): Response
    {
        $this->owner($sheet);
        $grid = $sheet->matrix();

        return $this->pdf('pdf.personal-sheet', ['sheet' => $sheet, 'grid' => $grid, 'docTitle' => 'TABLEUR', 'docReference' => str($sheet->title)->limit(60)->toString(), 'docSubtitle' => 'Mis à jour le '.$sheet->updated_at->format('d/m/Y H:i')], $sheet->title, $request, count($grid[0] ?? []) > 6 ? 'landscape' : 'portrait');
    }

    public function sheetExcel(PersonalSheet $sheet)
    {
        $this->owner($sheet);

        return Excel::download(new PersonalSheetExport($sheet), str($sheet->title)->slug()->whenEmpty(fn () => str('classeur'))->append('.xlsx')->toString());
    }

    // ── Bons émis par l'employé ───────────────────────────────────────────
    public function voucherPdf(Request $request, Voucher $voucher): Response
    {
        abort_unless((int) $voucher->company_id === company_id() && (int) $voucher->requested_by === (int) auth()->id(), 404);
        $voucher->load('lines.product', 'employee', 'contact', 'site', 'warehouse', 'requester', 'approver', 'executor', 'financialAccount', 'manufacturingOrder', 'salesOrder');

        // Bon de sortie : modèle choisi par le Master (Master système › Documents & impressions).
        $template = $voucher->type === 'sortie' ? (string) erp_setting('templates.exit_voucher', 'standard') : 'standard';
        $view = $template !== 'standard' && array_key_exists($template, config('erp.print_templates.exit_voucher')) ? "pdf.exit-vouchers.{$template}" : 'pdf.voucher';

        return $this->pdf($view, ['voucher' => $voucher, 'docTitle' => mb_strtoupper($voucher->typeLabel()), 'docReference' => 'N° '.$voucher->number], $voucher->number, $request);
    }

    // ── Tâches du jour ────────────────────────────────────────────────────
    public function tasksPrint(Request $request)
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', (string) $request->query('jour', today()->toDateString()))->startOfDay();
        } catch (\Throwable) {
            $day = today();
        }

        return view('personal.print.tasks', [
            'day' => $day,
            'tasks' => PersonalTask::mine()->whereDate('task_date', $day)->ordered()->get(),
        ]);
    }

    // ── Outils ────────────────────────────────────────────────────────────
    private function owner($item): void
    {
        abort_unless((int) $item->company_id === company_id() && (int) $item->user_id === (int) auth()->id(), 404);
    }

    private function pdf(string $view, array $data, string $filename, Request $request, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data + ['company' => company()])->setPaper('a4', $orientation);
        $filename = str($filename)->slug()->whenEmpty(fn () => str('document'))->append('.pdf')->toString();

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
