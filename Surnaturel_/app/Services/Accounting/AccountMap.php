<?php

namespace App\Services\Accounting;

use App\Models\Setting;

/**
 * Correspondance opérations → comptes (SYSCOHADA par défaut, paramétrable dans Paramètres > Comptabilité).
 */
class AccountMap
{
    /**
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'customers' => '411',
        'suppliers' => '401',
        'sales_goods' => '701',
        'sales_services' => '706',
        'vat_collected' => '4431',
        'vat_deductible' => '4452',
        'purchases_goods' => '601',
        'purchases_materials' => '602',
        'other_purchases' => '605',
        'staff_expenses' => '638',
        'salaries' => '661',
        'salaries_payable' => '422',
        'staff_advances' => '421',
        'cash' => '571',
        'bank' => '521',
        'mobile_money' => '5211',
        'internal_transfers' => '585',
        'cash_differences' => '658',
        'discounts_granted' => '673',
    ];

    public function get(string $key): string
    {
        return (string) Setting::get("accounting.{$key}", self::DEFAULTS[$key]);
    }
}
