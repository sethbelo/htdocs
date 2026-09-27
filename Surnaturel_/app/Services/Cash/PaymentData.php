<?php

namespace App\Services\Cash;

use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;

/**
 * Ligne de paiement saisie en caisse (un paiement mixte = plusieurs lignes).
 */
final readonly class PaymentData
{
    public function __construct(
        public PaymentMethod $method,
        public float $amount,
        public ?string $reference = null,
    ) {}

    /**
     * @param  list<array{method: string|PaymentMethod, amount: float|int|string, reference?: string|null}>  $lines
     * @return list<self>
     */
    public static function collection(array $lines): array
    {
        $payments = [];

        foreach ($lines as $line) {
            $amount = round((float) $line['amount'], 2);

            if ($amount <= 0) {
                continue;
            }

            $method = $line['method'] instanceof PaymentMethod ? $line['method'] : PaymentMethod::from($line['method']);

            if (in_array($method, [PaymentMethod::Card, PaymentMethod::BankTransfer, PaymentMethod::Cheque, PaymentMethod::MobileMoney], true) && blank($line['reference'] ?? null)) {
                throw new BusinessRuleException("La référence est obligatoire pour un paiement « {$method->label()} ».");
            }

            $payments[] = new self($method, $amount, $line['reference'] ?? null);
        }

        if ($payments === []) {
            throw new BusinessRuleException('Aucun paiement saisi.');
        }

        return $payments;
    }

    /**
     * @param  list<self>  $payments
     */
    public static function total(array $payments, bool $includeCredit = true): float
    {
        return round(array_sum(array_map(
            fn (self $payment): float => ($includeCredit || $payment->method !== PaymentMethod::Credit) ? $payment->amount : 0,
            $payments,
        )), 2);
    }
}
