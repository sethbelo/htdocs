<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use App\Models\CareVoucher;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DesignJob;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payslip;
use App\Models\Proof;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\StockOperation;
use App\Models\StockTransfer;
use App\Models\Voucher;
use App\Services\Cash\CashSessionReport;
use App\Services\Crm\LoyaltyService;
use App\Services\Design\DesignNotebookService;
use App\Services\Printing\PdfRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PrintController extends Controller
{
    public function __construct(private PdfRenderer $pdf) {}

    /**
     * Ticket thermique 80 mm remis au client après la prise de commande (à présenter en caisse).
     */
    public function orderTicket(Order $order): Response
    {
        Gate::authorize('print', $order);
        $order->load('client', 'salesRep', 'items', 'site');

        return $this->pdf->render('pdf.order-ticket', ['order' => $order, 'lines' => $order->items->count() * 2], "{$order->number}-ticket.pdf", 'ticket');
    }

    public function quote(Quote $quote): Response
    {
        abort_unless(auth()->user()->can('quotes.print') || auth()->user()->can('quotes.view'), 403);
        abort_unless(auth()->user()->canAccessSite($quote->site_id), 403);
        $quote->load('items', 'client', 'salesRep');

        return $this->pdf->render('pdf.quote', ['quote' => $quote], "{$quote->number}.pdf");
    }

    public function invoice(Invoice $invoice, string $format = 'a4'): Response
    {
        abort_unless(auth()->user()->can('invoices.print') || auth()->user()->can('cash.collect'), 403);
        abort_unless(auth()->user()->canAccessSite($invoice->site_id), 403);
        $invoice->load('items', 'client', 'order', 'sale', 'payments', 'site', 'creator');

        return $this->pdf->render($format === 'ticket' ? 'pdf.invoice-ticket' : 'pdf.invoice', ['invoice' => $invoice, 'lines' => $invoice->items->count() * 2 + $invoice->payments->count()], "{$invoice->number}.pdf", $format);
    }

    public function saleReceipt(Sale $sale): Response
    {
        abort_unless(auth()->user()->can('pos.print') || auth()->user()->can('invoices.print'), 403);
        abort_unless(auth()->user()->canAccessSite($sale->site_id), 403);
        $sale->load('items', 'payments', 'client.loyaltyAccount', 'cashier', 'register', 'site', 'invoice');

        return $this->pdf->render('pdf.sale-receipt', ['sale' => $sale, 'height' => 380 + ($sale->items->count() * 2 + $sale->payments->count() + ($sale->client ? 2 : 0)) * 16], "{$sale->number}.pdf", 'ticket');
    }

    public function loyaltyCard(Client $client, LoyaltyService $loyalty): Response
    {
        Gate::authorize('view', $client);
        abort_unless(auth()->user()->can('loyalty.print') || auth()->user()->can('loyalty.view'), 403);
        $account = $loyalty->accountFor($client)->load('tier');

        return $this->pdf->render('pdf.loyalty-card', ['client' => $client, 'account' => $account], "carte-{$account->card_number}.pdf", 'card');
    }

    public function proof(Proof $proof): Response
    {
        abort_unless(auth()->user()->can('proofs.view'), 403);
        $proof->load('order.client', 'order.items', 'version', 'validator', 'designJob.designer');

        return $this->pdf->render('pdf.proof', ['proof' => $proof], "{$proof->number}.pdf");
    }

    public function deliveryNote(Delivery $delivery): Response
    {
        abort_unless(auth()->user()->can('deliveries.view'), 403);
        $delivery->load('order.client', 'order.items', 'driver');

        return $this->pdf->render('pdf.delivery-note', ['delivery' => $delivery], "{$delivery->number}.pdf");
    }

    public function cashSession(CashSession $cashSession): Response
    {
        Gate::authorize('view', $cashSession);
        $report = app(CashSessionReport::class)->build($cashSession);
        $lines = $report['by_method']->count() + $report['top_products']->count() + $report['disbursements']->count() + count($cashSession->denominations ?? []);

        return $this->pdf->render('pdf.cash-session', ['r' => $report, 'session' => $cashSession, 'height' => 760 + $lines * 16], "{$cashSession->number}.pdf", 'ticket');
    }

    public function payslip(Payslip $payslip): Response
    {
        $payslip->load('employee.department', 'period');
        abort_unless(auth()->user()->can('payroll.print') || $payslip->employee->user_id === auth()->id(), 403);

        return $this->pdf->render('pdf.payslip', ['payslip' => $payslip], "{$payslip->number}.pdf");
    }

    public function stockOperation(StockOperation $stockOperation): Response
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);
        $stockOperation->load('warehouse.site', 'recorder', 'supplier', 'employee', 'department', 'movements.product.unit');
        abort_unless(auth()->user()->canAccessSite($stockOperation->warehouse->site_id), 403);

        return $this->pdf->render('pdf.stock-operation', ['operation' => $stockOperation], "{$stockOperation->number}.pdf");
    }

    public function stockTransfer(StockTransfer $stockTransfer): Response
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);
        $stockTransfer->load('from', 'to', 'items.product.unit', 'requester', 'shipper', 'receiver');
        abort_unless(auth()->user()->canAccessSite($stockTransfer->from->site_id) || auth()->user()->canAccessSite($stockTransfer->to->site_id), 403);

        return $this->pdf->render('pdf.stock-transfer', ['transfer' => $stockTransfer], "{$stockTransfer->number}.pdf");
    }

    public function voucher(Voucher $voucher): Response
    {
        abort_unless(auth()->user()->can('vouchers.print') || auth()->user()->can('vouchers.view'), 403);
        $voucher->load('creator', 'approver', 'approvalRequest.actions.user');

        return $this->pdf->render('pdf.voucher', ['voucher' => $voucher], "{$voucher->number}.pdf");
    }

    /**
     * Fiche technique d'impression (bon à imprimer) issue du cahier de conception.
     */
    public function designBrief(DesignJob $designJob, DesignNotebookService $notebook): Response
    {
        abort_unless(auth()->user()->can('design.view') || auth()->user()->can('production.view'), 403);
        abort_unless($designJob->order()->visibleTo(auth()->user())->exists(), 404);
        $brief = $notebook->briefFor($designJob);
        $designJob->load('order.client', 'order.items', 'order.salesRep', 'designer', 'latestVersion', 'notes.user');

        return $this->pdf->render('pdf.design-brief', ['job' => $designJob, 'brief' => $brief->load('validator')], "fiche-impression-{$designJob->order->number}.pdf");
    }

    public function affiliation(Affiliation $affiliation): Response
    {
        $affiliation->load('employee.department', 'dependent', 'policy', 'creator');
        $this->authorizeAffiliation($affiliation);

        return $this->pdf->render('pdf.affiliation', ['affiliation' => $affiliation], "attestation-{$affiliation->member_number}.pdf");
    }

    public function memberCard(Affiliation $affiliation): Response
    {
        $affiliation->load('employee', 'dependent', 'policy');
        $this->authorizeAffiliation($affiliation);

        return $this->pdf->render('pdf.member-card', ['affiliation' => $affiliation], "carte-{$affiliation->member_number}.pdf", 'card');
    }

    public function careVoucher(CareVoucher $careVoucher): Response
    {
        $careVoucher->load('affiliation.employee.department', 'affiliation.dependent', 'affiliation.policy', 'issuer');
        $this->authorizeAffiliation($careVoucher->affiliation);

        return $this->pdf->render('pdf.care-voucher', ['voucher' => $careVoucher], "{$careVoucher->number}.pdf");
    }

    /**
     * Page publique de vérification d'une carte de service (lien signé contenu dans le QR code).
     */
    public function verifyEmployee(Employee $employee): View
    {
        return view('verify-employee', [
            'employee' => $employee->load('department'),
            'valid' => $employee->is_active && $employee->card_expires_at?->isFuture(),
            'company' => $this->pdf->company(),
        ]);
    }

    private function authorizeAffiliation(Affiliation $affiliation): void
    {
        $user = auth()->user();
        abort_unless($user->can('affiliations.view') || $affiliation->employee->user_id === $user->id, 403);
    }
}
