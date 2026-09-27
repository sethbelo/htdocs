<?php

namespace App\Actions\Documents;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Models\Document;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Mise à jour d'un document commercial non encaissé : recalcul des lignes,
 * resynchronisation comptable et du dossier de commande encore au stade devis.
 */
class UpdateDocument
{
    public function __construct(private readonly SynchronizeAccounting $accounting) {}

    public function handle(Document $document, array $data, array $items): Document
    {
        if ((float) $document->amount_paid > 0) {
            throw new DomainException('Un document encaissé ne peut plus être modifié : établissez un avoir.');
        }
        if (in_array($document->status->value, ['accepted', 'paid', 'cancelled'], true) && $document->type->value === 'quote') {
            throw new DomainException('Un devis accepté ne peut plus être modifié.');
        }

        return DB::transaction(function () use ($document, $data, $items) {
            $subtotal = $discount = $tax = $total = 0;
            foreach ($items as $position => &$item) {
                $gross = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $lineDiscount = round($gross * ((float) ($item['discount_rate'] ?? 0) / 100), 2);
                $net = $gross - $lineDiscount;
                $lineTax = round($net * ((float) ($item['tax_rate'] ?? 0) / 100), 2);
                $item = array_merge(['unit' => 'unité'], $item, ['position' => $position, 'subtotal' => $net, 'tax_total' => $lineTax, 'total' => $net + $lineTax]);
                $subtotal += $gross;
                $discount += $lineDiscount;
                $tax += $lineTax;
                $total += $net + $lineTax;
            }
            unset($item);

            $document->update($data + [
                'subtotal' => $subtotal, 'discount_total' => $discount, 'tax_total' => $tax, 'total' => $total,
                'base_total' => round($total * (float) ($data['exchange_rate'] ?? $document->exchange_rate), 2),
            ]);
            $document->items()->delete();
            $document->items()->createMany($items);
            $this->accounting->invoice($document->fresh());

            if (($order = $document->relatedOrder()) && $order->stage === 'devis') {
                $order->update(['total' => $total, 'contact_id' => $document->contact_id]);
            }

            return $document->fresh('items');
        });
    }
}
