<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'CDF',
                'name' => 'Franc Congolais',
                'symbol' => 'FC',
                'decimal_places' => 2,
                'position' => 'after',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'Dollar Américain',
                'symbol' => '$',
                'decimal_places' => 2,
                'position' => 'before',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'position' => 'after',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
