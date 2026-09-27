<?php

namespace App\Services;

use App\Models\Establishment;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPrinter
{
    /**
     * Generate a PDF receipt for an order
     */
    public function generatePDF(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->load(['items.product', 'payments.paymentMethod', 'payments.currency', 'user', 'table', 'establishment.defaultCurrency']);

        $establishment = $order->establishment;
        $ticketSettings = $this->settingsFor($establishment);
        $codeImage = app(ReceiptCodeGenerator::class)->dataUri($ticketSettings['code_type'], $order->order_number);

        $data = [
            'order' => $order,
            'establishment' => $establishment,
            'items' => $order->items,
            'payments' => $order->payments,
            'generatedAt' => now(),
            'ticketSettings' => $ticketSettings,
            'codeImage' => $codeImage,
        ];

        $pdf = Pdf::loadView('receipts.ticket', $data);
        if ($ticketSettings['paper_size'] === 'a4') {
            $pdf->setPaper('a4', 'portrait');
        } else {
            $width = $ticketSettings['paper_size'] === '58' ? 164.41 : 226.77;
            $pdf->setPaper([0, 0, $width, 720], 'portrait');
        }

        return $pdf;
    }

    public function settingsFor(?Establishment $establishment): array
    {
        return [
            'paper_size' => $establishment?->getSetting('paper_size', '80') ?? '80',
            'font' => $establishment?->getSetting('ticket_font', 'DejaVu Sans') ?? 'DejaVu Sans',
            'font_size' => (int) ($establishment?->getSetting('ticket_font_size', 11) ?? 11),
            'color' => $establishment?->getSetting('ticket_color', '#18181b') ?? '#18181b',
            'show_logo' => (bool) ($establishment?->getSetting('show_logo_on_ticket', true) ?? true),
            'logo_width' => (int) ($establishment?->getSetting('ticket_logo_width', 26) ?? 26),
            'auto_print' => (bool) ($establishment?->getSetting('auto_print_ticket', true) ?? true),
            'template' => $establishment?->getSetting('ticket_template', 'modern') ?? 'modern',
            'code_type' => $establishment?->getSetting('ticket_code_type', 'qrcode') ?? 'qrcode',
            'header_text' => $establishment?->getSetting('ticket_header_text', 'REÇU DE PAIEMENT') ?? 'REÇU DE PAIEMENT',
            'footer_text' => $establishment?->getSetting('ticket_footer_text', $establishment?->document_footer ?: 'Merci de votre visite !') ?? 'Merci de votre visite !',
            'show_fiscal_info' => (bool) ($establishment?->getSetting('ticket_show_fiscal_info', true) ?? true),
            'show_tax_details' => (bool) ($establishment?->getSetting('ticket_show_tax_details', true) ?? true),
            'show_payment_details' => (bool) ($establishment?->getSetting('ticket_show_payment_details', true) ?? true),
        ];
    }

    /**
     * Download receipt PDF
     */
    public function download(Order $order)
    {
        $pdf = $this->generatePDF($order);

        return $pdf->download("ticket-{$order->order_number}.pdf");
    }

    /**
     * Stream receipt PDF (inline display)
     */
    public function stream(Order $order)
    {
        $pdf = $this->generatePDF($order);

        return $pdf->stream("ticket-{$order->order_number}.pdf");
    }
}
