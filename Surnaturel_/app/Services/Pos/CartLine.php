<?php

namespace App\Services\Pos;

final readonly class CartLine
{
    public function __construct(
        public int $productId,
        public float $quantity,
        public float $discount = 0,
        public ?float $unitPrice = null,
    ) {}

    /**
     * @param  list<array{product_id: int|string, quantity: float|int|string, discount?: float|int|string|null, unit_price?: float|int|string|null}>  $lines
     * @return list<self>
     */
    public static function collection(array $lines): array
    {
        return array_map(fn (array $line): self => new self(
            (int) $line['product_id'],
            (float) $line['quantity'],
            (float) ($line['discount'] ?? 0),
            isset($line['unit_price']) && $line['unit_price'] !== '' ? (float) $line['unit_price'] : null,
        ), $lines);
    }
}
