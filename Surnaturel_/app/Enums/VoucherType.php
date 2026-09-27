<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VoucherType: string
{
    use HasOptions;

    case PurchaseOrder = 'purchase_order';
    case PurchaseVoucher = 'purchase_voucher';
    case DeliveryNote = 'delivery_note';
    case ReceiptNote = 'receipt_note';
    case ExitVoucher = 'exit_voucher';
    case TransferVoucher = 'transfer_voucher';
    case DisbursementVoucher = 'disbursement_voucher';
    case CashVoucher = 'cash_voucher';
    case ProductionVoucher = 'production_voucher';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseOrder => 'Bon de commande',
            self::PurchaseVoucher => 'Bon d’achat',
            self::DeliveryNote => 'Bon de livraison',
            self::ReceiptNote => 'Bon de réception',
            self::ExitVoucher => 'Bon de sortie',
            self::TransferVoucher => 'Bon de transfert',
            self::DisbursementVoucher => 'Bon de décaissement',
            self::CashVoucher => 'Bon de caisse',
            self::ProductionVoucher => 'Bon de production',
        };
    }
}
