<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Models\Document;
use App\Models\ManufacturingOrder;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PosOrder;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\Voucher;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impressions PDF SOGEREF (papier à en-tête, pied de page légal).
 * ?download=1 force le téléchargement, sinon aperçu dans le navigateur.
 */
class PdfController extends Controller
{
    // ── Documents commerciaux (4 modèles au choix) ──────────────────────────
    public function document(Request $request, Document $document): Response
    {
        $this->own($document);
        $module = match ($document->type->value) {
            'quote' => 'quotes', 'purchase_order' => 'purchases', default => 'invoices'
        };
        abort_unless(auth()->user()?->canAccessModule($module), 403);

        $document->load('items.product', 'contact', 'creator');
        $template = $request->query('modele', $document->template);
        $template = array_key_exists($template, config('erp.document_templates')) ? $template : 'moderne';
        $type = $document->type->value;
        $isProforma = $type === 'invoice' && $document->status->value === 'draft';
        $contact = $document->contact;

        return $this->render("pdf.documents.{$template}", [
            'document' => $document,
            'type' => $type,
            'isProforma' => $isProforma,
            'docTitle' => match ($type) {
                'quote' => 'DEVIS', 'invoice' => $isProforma ? 'FACTURE PROFORMA' : 'FACTURE', 'credit_note' => 'AVOIR', 'purchase_order' => 'BON DE COMMANDE', default => 'DOCUMENT'
            },
            'docReference' => 'Réf. : '.$document->number,
            'contact' => $contact,
            'partyLabel' => $type === 'purchase_order' ? 'Fournisseur' : 'Client',
            'order' => $document->relatedOrder()?->load('site'),
            'currencyLabel' => ['USD' => 'Dollars US', 'CDF' => 'Francs congolais', 'EUR' => 'Euros'][$document->currency_code] ?? $document->currency_code,
            'terms' => $document->terms ?: erp_setting($type === 'quote' ? 'quote_terms' : 'invoice_terms'),
            'address' => collect(company()->address ?? [])->filter()->implode(', '),
            'contactAddress' => collect($contact->billing_address ?? [])->filter()->implode(', ') ?: $contact->city,
        ], $document->number, $request);
    }

    // ── Bons ────────────────────────────────────────────────────────────────
    public function voucher(Request $request, Voucher $voucher): Response
    {
        $this->own($voucher);
        $voucher->load('lines.product', 'employee', 'contact', 'site', 'warehouse', 'requester', 'approver', 'executor', 'financialAccount', 'manufacturingOrder', 'salesOrder');

        return $this->render('pdf.voucher', ['voucher' => $voucher, 'docTitle' => mb_strtoupper($voucher->typeLabel()), 'docReference' => 'N° '.$voucher->number], $voucher->number, $request);
    }

    // ── Fiche de fabrication / dossier de construction ─────────────────────
    public function manufacturing(Request $request, ManufacturingOrder $mo): Response
    {
        $this->own($mo);
        $mo->load('materials.product', 'operations.employee', 'salesOrder.contact', 'salesOrder.quote.items', 'product', 'responsible', 'warehouse', 'vouchers');
        $title = $mo->type === 'construction' ? 'DOSSIER DE CONSTRUCTION' : 'FICHE DE FABRICATION';

        return $this->render('pdf.manufacturing', ['mo' => $mo, 'docTitle' => $title, 'docReference' => 'OF '.$mo->number], $mo->number, $request);
    }

    // ── Paie ────────────────────────────────────────────────────────────────
    public function payslip(Request $request, Payslip $payslip): Response
    {
        $this->own($payslip);
        $payslip->load('employee.department', 'employee.position', 'period');

        return $this->render('pdf.payslip', ['slips' => collect([$payslip]), 'docTitle' => 'FICHE DE PAIE', 'docReference' => ucfirst($payslip->period->end_date->translatedFormat('F Y'))], $payslip->reference, $request);
    }

    public function payrollPeriod(Request $request, PayrollPeriod $period): Response
    {
        $this->own($period);
        $period->load('payslips.employee.department', 'payslips.employee.position', 'site');
        $view = $request->query('format') === 'etat' ? 'pdf.payroll-register' : 'pdf.payslip';

        return $this->render($view, ['period' => $period, 'slips' => $period->payslips, 'docTitle' => $view === 'pdf.payslip' ? 'FICHE DE PAIE' : 'ÉTAT DE PAIE', 'docReference' => $period->name], $period->reference, $request, $view === 'pdf.payroll-register' ? 'landscape' : 'portrait');
    }

    // ── Présences & chantiers ──────────────────────────────────────────────
    public function attendance(Request $request, AttendanceSheet $sheet): Response
    {
        $this->own($sheet);
        $sheet->load('entries.employee.position', 'site.manager', 'recorder', 'validator');

        return $this->render('pdf.attendance', ['sheet' => $sheet, 'docTitle' => 'LISTE DE PRÉSENCE', 'docReference' => $sheet->site->code.' · '.$sheet->work_date->format('d/m/Y')], 'presence-'.$sheet->site->code.'-'.$sheet->work_date->format('Ymd'), $request);
    }

    public function siteReport(Request $request, Site $site): Response
    {
        $this->own($site);
        $site->load('contact', 'manager', 'members.position', 'equipmentAssignments.equipment', 'reports.author', 'evaluations.evaluator', 'vouchers', 'salesOrder');
        $attendance = $site->attendanceSheets()->with('entries')->where('status', 'validated')->get();

        return $this->render('pdf.site', ['site' => $site, 'attendance' => $attendance, 'docTitle' => 'RAPPORT DE CHANTIER', 'docReference' => $site->code], 'rapport-'.$site->code, $request);
    }

    // ── Dossier de commande (suivi complet) ────────────────────────────────
    public function order(Request $request, SalesOrder $order): Response
    {
        $this->own($order);
        $order->load('contact', 'quote.items', 'invoice', 'manufacturingOrder', 'project', 'site', 'events.user', 'owner', 'vouchers');

        return $this->render('pdf.order', ['order' => $order, 'docTitle' => 'DOSSIER DE COMMANDE', 'docReference' => $order->number], $order->number, $request);
    }

    // ── Ticket de caisse (80 mm) ───────────────────────────────────────────
    public function ticket(Request $request, PosOrder $posOrder): Response
    {
        $this->own($posOrder);
        $posOrder->load('lines', 'contact', 'session.user');
        $pdf = Pdf::loadView('pdf.ticket', ['order' => $posOrder, 'company' => company()])->setPaper([0, 0, 226.77, 600 + $posOrder->lines->count() * 28]);

        return $request->boolean('download') ? $pdf->download($posOrder->number.'.pdf') : $pdf->stream($posOrder->number.'.pdf');
    }

    // ── Rapports de gestion ────────────────────────────────────────────────
    public function report(Request $request, string $report, ReportService $reports): Response
    {
        $from = $request->date('from') ?? now()->startOfYear();
        $to = $request->date('to') ?? now();
        $data = $reports->build($report, $from, $to);

        return $this->render('pdf.report', ['report' => $data, 'docTitle' => mb_strtoupper($data['title']), 'docReference' => 'Du '.$from->format('d/m/Y').' au '.$to->format('d/m/Y')], 'rapport-'.$report, $request, $data['orientation'] ?? 'portrait');
    }

    // ── Papier à en-tête vierge (modèle de courrier) ───────────────────────
    public function letterhead(Request $request): Response
    {
        return $this->render('pdf.letterhead', ['docTitle' => '', 'body' => $request->query('texte')], 'papier-entete-sogeref', $request);
    }

    // ── Outils ─────────────────────────────────────────────────────────────
    private function render(string $view, array $data, string $filename, Request $request, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data + ['company' => company()])->setPaper('a4', $orientation);
        $filename = str($filename)->slug()->append('.pdf')->toString();

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    private function own($model): void
    {
        abort_unless((int) $model->company_id === company_id(), 404);
    }
}
