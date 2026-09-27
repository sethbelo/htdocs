<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CurrencySeeder::class,
            DemoDataSeeder::class,
            Stage2DemoSeeder::class,
            Stage3PosSeeder::class,
            Stage4KdsSeeder::class,
            Stage5AccountingSeeder::class,
            Stage6ReportsSeeder::class,
        ]);
    }
}
