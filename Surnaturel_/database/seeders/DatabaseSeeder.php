<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ChartOfAccountsSeeder::class,
            ReferenceDataSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call([DemoDataSeeder::class, ExtendedDemoSeeder::class]);
        }
    }
}
