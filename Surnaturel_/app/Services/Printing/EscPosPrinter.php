<?php

namespace App\Services\Printing;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\Support\Money;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Throwable;

/**
 * Impression directe sur imprimante thermique ESC/POS réseau (port 9100), en complément des tickets PDF 80 mm.
 * L'adresse est paramétrée par caisse ou par siège : réglage « pos.printer.<CODE> » = « 192.168.1.50:9100 ».
 */
class EscPosPrinter
{
    public function printOrderTicket(Order $order, string $printerKey): void
    {
        $order->loadMissing('client', 'salesRep', 'items');

        $this->print($printerKey, function (Printer $printer) use ($order): void {
            $this->header($printer);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("BON DE COMMANDE\n{$order->number}\n");
            $printer->setEmphasis(false);
            $printer->text($order->ordered_at->format('d/m/Y H:i')."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_repeat('-', 42)."\n");
            $printer->text("Client : {$order->client->displayName()}\n");
            $printer->text('Commercial : '.($order->salesRep?->name ?? '-')."\n");
            $printer->text(str_repeat('-', 42)."\n");

            foreach ($order->items as $item) {
                $printer->text($this->ascii($item->description)."\n");
                $printer->text($this->line((float) $item->quantity.' x '.Money::format($item->unit_price), Money::format($item->total)));
            }

            $printer->text(str_repeat('-', 42)."\n");
            $printer->setEmphasis(true);
            $printer->text($this->line('A ENCAISSER', Money::format($order->balanceDue())));
            $printer->setEmphasis(false);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->qrCode($order->number, Printer::QR_ECLEVEL_M, 6);
            $printer->setBarcodeHeight(60);
            $printer->barcode('{B'.$order->number, Printer::BARCODE_CODE128);
            $printer->text("\nPresentez ce ticket a la caisse.\n");
        });
    }

    public function printSaleReceipt(Sale $sale, string $printerKey): void
    {
        $sale->loadMissing('items', 'payments', 'cashier');

        $this->print($printerKey, function (Printer $printer) use ($sale): void {
            $this->header($printer);
            $printer->text("{$sale->number} - ".$sale->sold_at->format('d/m/Y H:i')."\n".str_repeat('-', 42)."\n");

            foreach ($sale->items as $item) {
                $printer->text($this->ascii($item->description)."\n");
                $printer->text($this->line((float) $item->quantity.' x '.Money::format($item->unit_price), Money::format($item->total)));
            }

            $printer->text(str_repeat('-', 42)."\n");
            $printer->setEmphasis(true);
            $printer->text($this->line('TOTAL', Money::format($sale->total)));
            $printer->setEmphasis(false);

            foreach ($sale->payments as $payment) {
                $printer->text($this->line($payment->method->label(), Money::format($payment->amount)));
            }

            if ((float) $sale->change_due > 0) {
                $printer->text($this->line('Monnaie', Money::format($sale->change_due)));
            }

            $printer->pulse();
        });
    }

    private function print(string $printerKey, callable $body): void
    {
        $address = Setting::get("pos.printer.{$printerKey}");

        if (! $address) {
            throw new BusinessRuleException("Aucune imprimante thermique configurée pour « {$printerKey} ».");
        }

        [$host, $port] = array_pad(explode(':', (string) $address, 2), 2, 9100);

        try {
            $printer = new Printer(new NetworkPrintConnector($host, (int) $port, 5));
            $body($printer);
            $printer->feed(3);
            $printer->cut();
            $printer->close();
        } catch (Throwable $exception) {
            throw new BusinessRuleException("Imprimante injoignable ({$address}) : {$exception->getMessage()}");
        }
    }

    private function header(Printer $printer): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text($this->ascii((string) Setting::get('company.name'))."\n");
        $printer->selectPrintMode();
        $printer->text($this->ascii((string) Setting::get('company.address'))."\n".Setting::get('company.phone')."\n");
        $printer->text('RCCM '.Setting::get('company.rccm').' NIF '.Setting::get('company.nif')."\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
    }

    private function line(string $left, string $right, int $width = 42): string
    {
        $left = $this->ascii($left);
        $right = $this->ascii($right);

        return str_pad(mb_substr($left, 0, $width - strlen($right) - 1), $width - strlen($right)).$right."\n";
    }

    private function ascii(string $text): string
    {
        return (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    }
}
