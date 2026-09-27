<?php

namespace App\Services\Support;

use App\Models\DocumentSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Numérotation automatique, séquentielle et sans trou des documents : PREFIXE-ANNÉE-000001.
 */
class NumberGenerator
{
    public const Client = 'CLI';

    public const Order = 'CMD';

    public const Quote = 'DEV';

    public const Proforma = 'PRO';

    public const Invoice = 'FAC';

    public const DepositInvoice = 'FAA';

    public const CreditNote = 'AV';

    public const Receipt = 'REC';

    public const Payment = 'PAY';

    public const Sale = 'VTE';

    public const CashSession = 'SES';

    public const StockMovement = 'MVT';

    public const Proof = 'BAT';

    public const PrintJob = 'BP';

    public const Delivery = 'BL';

    public const PurchaseRequest = 'DA';

    public const PurchaseOrder = 'BCF';

    public const GoodsReceipt = 'BR';

    public const SupplierInvoice = 'FF';

    public const JournalEntry = 'EC';

    public const TreasuryTransfer = 'TRF';

    public const ExpenseClaim = 'NDF';

    public const LeaveRequest = 'CGE';

    public const Payslip = 'BUL';

    public const LoyaltyCard = 'FID';

    public const StockEntry = 'ENT';

    public const StockExit = 'SOR';

    public const StockTransfer = 'TRS';

    public function next(string $prefix, ?Carbon $date = null, int $padding = 6): string
    {
        $year = ($date ?? now())->year;

        $number = DB::transaction(function () use ($prefix, $year): int {
            DocumentSequence::query()->insertOrIgnore([
                'prefix' => $prefix,
                'year' => $year,
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DocumentSequence::query()
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $current = $sequence->next_number;
            $sequence->increment('next_number');

            return $current;
        });

        return sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $number, $padding, '0', STR_PAD_LEFT));
    }
}
