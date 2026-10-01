<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSheet;
use App\Models\Document;
use App\Models\ManufacturingOrder;
use App\Models\Payment;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PosOrder;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\Voucher;
use App\Services\ReportService;
use App\Support\AmountInWords;
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
        $type = $document->type->value;
        // Pro forma : facture brouillon, devis intitulé « Pro forma », ou devis imprimé au format pro forma (?format=proforma).
        $headingProforma = $type === 'quote' && data_get($document->meta, 'heading') === 'proforma';
        $isProforma = ($type === 'invoice' && $document->status->value === 'draft')
            || $headingProforma
            || ($type === 'quote' && $request->query('format') === 'proforma');
        // Un devis imprimé ponctuellement en pro forma prend le modèle pro forma par défaut du Master.
        $default = $type === 'quote' && $isProforma && ! $headingProforma ? erp_setting('templates.proforma', $document->template) : $document->template;
        $template = $request->query('modele', $default);
        $template = array_key_exists((string) $template, config('erp.document_templates')) ? $template : 'moderne';
        $contact = $document->contact;

        return $this->render("pdf.documents.{$template}", [
            'document' => $document,
            'type' => $type,
            'isProforma' => $isProforma,
            'docTitle' => match ($type) {
                'quote' => $isProforma ? 'FACTURE PRO FORMA' : 'DEVIS', 'invoice' => $isProforma ? 'FACTURE PRO FORMA' : 'FACTURE', 'credit_note' => 'AVOIR', 'purchase_order' => 'BON DE COMMANDE', default => 'DOCUMENT'
            },
            'docReference' => 'Réf. : '.$document->number,
            'contact' => $contact,
            'partyLabel' => $type === 'purchase_order' ? 'Fournisseur' : 'Client',
            'order' => $document->relatedOrder()?->load('site'),
            'currencyLabel' => ['USD' => 'Dollars US', 'CDF' => 'Francs congolais', 'EUR' => 'Euros'][$document->currency_code] ?? $document->currency_code,
            'terms' => $document->terms ?: erp_setting($type === 'quote' ? 'quote_terms' : 'invoice_terms'),
            'address' => collect(company()->address ?? [])->filter()->implode(', '),
            'contactAddress' => collect($contact->billing_address ?? [])->filter()->implode(', ') ?: $contact->city,
        ], ($isProforma && $type === 'quote' && ! $headingProforma ? 'proforma-' : '').$document->number, $request);
    }

    // ── Reçu de paiement (5 modèles au choix du Master) ─────────────────────
    public function receipt(Request $request, Payment $payment): Response
    {
        $this->own($payment);
        abort_unless(auth()->user()?->canAccessModule('cash') || auth()->user()?->canAccessModule('invoices'), 403);

        return $this->renderReceipt($payment, (string) $request->query('modele', erp_setting('templates.receipt', 'moderne')), $request);
    }

    private function renderReceipt(Payment $payment, string $template, Request $request): Response
    {
        $template = array_key_exists($template, config('erp.print_templates.receipt')) ? $template : 'moderne';
        $payment->loadMissing('allocations.document', 'contact', 'creator');
        $company = company();
        $seal = $company->seal_path ? storage_path('app/public/'.$company->seal_path) : null;
        $data = [
            'payment' => $payment,
            'payerName' => $payment->contact?->display_name ?? 'Client comptant',
            'amountWords' => AmountInWords::make((float) $payment->amount, $payment->currency_code),
            'methodLabel' => $payment->method?->label() ?? '—',
            'cashier' => $payment->creator?->name ?? '—',
            'purpose' => $payment->allocations->isNotEmpty() ? 'Règlement '.$payment->allocations->map(fn ($a) => $a->document?->number)->filter()->implode(', ') : ($payment->notes ?: 'Acompte / règlement client'),
            'sealPath' => $seal && file_exists($seal) ? $seal : null,
            'company' => $company,
        ];
        $filename = 'recu-'.str($payment->reference)->slug().'.pdf';
        $pdf = Pdf::loadView("pdf.receipts.{$template}", $data);
        $template === 'ticket' ? $pdf->setPaper([0, 0, 226.77, 430 + $payment->allocations->count() * 14]) : $pdf->setPaper('a4');

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    // ── Aperçu d'un modèle d'impression (Master système) ───────────────────
    public function preview(Request $request, string $kind, string $template): Response
    {
        $user = auth()->user();
        $cid = company_id();
        switch ($kind) {
            case 'receipt':
                abort_unless($user->canAccessModule('system') || $user->canAccessModule('cash'), 403);
                $payment = Payment::where('company_id', $cid)->where('direction', 'in')->has('allocations')->latest('paid_at')->first()
                    ?? Payment::where('company_id', $cid)->latest('paid_at')->first();
                abort_unless($payment, 404, 'Aucun paiement enregistré pour l’aperçu.');

                return $this->renderReceipt($payment, $template, $request);
            case 'exit_voucher':
                abort_unless($user->canAccessModule('system') || $user->canAccessModule('vouchers'), 403);
                $voucher = Voucher::where('company_id', $cid)->where('type', 'sortie')->has('lines')->latest('id')->first();
                abort_unless($voucher, 404, 'Aucun bon de sortie enregistré pour l’aperçu.');

                return $this->renderVoucher($voucher, $template, $request);
            default:
                abort_unless(in_array($kind, ['quote', 'proforma', 'invoice', 'purchase_order'], true), 404);
                abort_unless($user->canAccessModule('system') || $user->canAccessModule($kind === 'purchase_order' ? 'purchases' : ($kind === 'invoice' ? 'invoices' : 'quotes')), 403);
                $document = Document::where('company_id', $cid)->where('type', $kind === 'proforma' ? 'quote' : $kind)->has('items')->latest('id')->first();
                abort_unless($document, 404, 'Aucun document enregistré pour l’aperçu.');
                $request->query->set('modele', $template);
                if ($kind === 'proforma') {
                    $request->query->set('format', 'proforma');
                }

                return $this->document($request, $document);
        }
    }

    // ── Bons ────────────────────────────────────────────────────────────────
    public function voucher(Request $request, Voucher $voucher): Response
    {
        $this->own($voucher);

        return $this->renderVoucher($voucher, (string) $request->query('modele', $voucher->type === 'sortie' ? erp_setting('templates.exit_voucher', 'standard') : 'standard'), $request);
    }

    /** Bon : les bons de sortie disposent de 5 modèles ; les autres bons du modèle standard. */
    private function renderVoucher(Voucher $voucher, string $template, Request $request): Response
    {
        $voucher->load('lines.product', 'employee', 'contact', 'site', 'warehouse', 'requester', 'approver', 'executor', 'financialAccount', 'manufacturingOrder', 'salesOrder');
        $template = $voucher->type === 'sortie' && array_key_exists($template, config('erp.print_templates.exit_voucher')) ? $template : 'standard';
        $view = $template === 'standard' ? 'pdf.voucher' : "pdf.exit-vouchers.{$template}";

        return $this->render($view, ['voucher' => $voucher, 'docTitle' => mb_strtoupper($voucher->typeLabel()), 'docReference' => 'N° '.$voucher->number], $voucher->number, $request);
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
