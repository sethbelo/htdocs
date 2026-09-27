<?php

namespace App\Actions\Documents;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Events\DocumentCreated;
use App\Models\Document;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDocument
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly SynchronizeAccounting $accounting,
    ) {}

    public function handle(array $data, array $items): Document
    {
        return DB::transaction(function () use ($data, $items): Document {
            $type = $data['type'] instanceof DocumentType ? $data['type'] : DocumentType::from($data['type']);
            $subtotal = $discount = $tax = $total = 0;

            foreach ($items as &$item) {
                $gross = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $lineDiscount = round($gross * ((float) ($item['discount_rate'] ?? 0) / 100), 2);
                $net = $gross - $lineDiscount;
                $lineTax = round($net * ((float) ($item['tax_rate'] ?? 0) / 100), 2);
                $item += ['unit' => 'unité', 'position' => 0];
                $item['subtotal'] = $net;
                $item['tax_total'] = $lineTax;
                $item['total'] = $net + $lineTax;
                $subtotal += $gross;
                $discount += $lineDiscount;
                $tax += $lineTax;
                $total += $item['total'];
            }

            $document = Document::create($data + [
                'type' => $type,
                'status' => DocumentStatus::Draft,
                'number' => $this->numbers->next($data['company_id'], $type),
                'public_token' => Str::random(48),
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'total' => $total,
                'base_total' => round($total * (float) ($data['exchange_rate'] ?? 1), 2),
            ]);
            $document->items()->createMany($items);
            $this->accounting->invoice($document);
            DB::afterCommit(fn () => DocumentCreated::dispatch($document));

            return $document->load('items', 'contact');
        });
    }
}
