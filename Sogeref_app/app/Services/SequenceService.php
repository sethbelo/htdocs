<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Numérotation atomique et annuelle : PREFIXE-2026-00042.
 * Les préfixes peuvent être personnalisés dans le Master système.
 */
class SequenceService
{
    public const DEFAULTS = [
        'sales_order' => 'CMD', 'manufacturing_order' => 'OF', 'voucher_achat' => 'BA', 'voucher_decaissement' => 'BD',
        'voucher_sortie' => 'BS', 'site' => 'CH', 'project' => 'PRJ', 'rental' => 'LOC', 'pos_order' => 'TK',
        'cash_session' => 'SES', 'expense_claim' => 'NDF', 'leave' => 'CGE', 'payroll' => 'PAIE', 'payslip' => 'BUL',
        'inventory' => 'INV', 'mail_in' => 'CE', 'mail_out' => 'CS', 'loyalty_card' => 'FID', 'employee' => 'EMP',
    ];

    public function next(string $key, ?int $companyId = null, bool $yearly = true): string
    {
        $companyId ??= company_id();
        $year = $yearly ? (int) now()->format('Y') : 0;
        $prefix = erp_setting("numbering.{$key}", self::DEFAULTS[$key] ?? strtoupper($key));

        return DB::transaction(function () use ($companyId, $key, $year, $prefix, $yearly) {
            $row = DB::table('sequences')->where(['company_id' => $companyId, 'key' => $key, 'year' => $year])->lockForUpdate()->first();
            if (! $row) {
                DB::table('sequences')->insert(['company_id' => $companyId, 'key' => $key, 'prefix' => $prefix, 'year' => $year, 'next_value' => 2, 'created_at' => now(), 'updated_at' => now()]);
                $value = 1;
            } else {
                $value = $row->next_value;
                DB::table('sequences')->where('id', $row->id)->update(['next_value' => $value + 1, 'updated_at' => now()]);
            }

            return $yearly ? sprintf('%s-%d-%05d', $prefix, $year, $value) : sprintf('%s-%03d', $prefix, $value);
        });
    }
}
