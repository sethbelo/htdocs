<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Establishment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class Stage3PosSeeder extends Seeder
{
    public function run(): void
    {
        $establishment = Establishment::first();
        $mainBranch = $establishment->mainBranch;
        $admin = User::where('email', 'admin@yeboox.local')->first();

        // ── Payment Methods ──────────────────────────────
        $methods = [
            ['name' => 'Espèces', 'code' => 'cash', 'type' => 'cash', 'icon' => 'banknotes', 'sort_order' => 1],
            ['name' => 'Mobile Money (M-Pesa)', 'code' => 'mobile_money', 'type' => 'mobile', 'icon' => 'device-phone-mobile', 'requires_reference' => true, 'sort_order' => 2],
            ['name' => 'Airtel Money', 'code' => 'airtel_money', 'type' => 'mobile', 'icon' => 'device-phone-mobile', 'requires_reference' => true, 'sort_order' => 3],
            ['name' => 'Orange Money', 'code' => 'orange_money', 'type' => 'mobile', 'icon' => 'device-phone-mobile', 'requires_reference' => true, 'sort_order' => 4],
            ['name' => 'Carte bancaire', 'code' => 'card', 'type' => 'card', 'icon' => 'credit-card', 'requires_reference' => true, 'sort_order' => 5],
            ['name' => 'Virement bancaire', 'code' => 'transfer', 'type' => 'transfer', 'icon' => 'arrows-right-left', 'requires_reference' => true, 'sort_order' => 6],
        ];

        foreach ($methods as $m) {
            PaymentMethod::create(array_merge($m, [
                'establishment_id' => $establishment->id,
                'is_active' => true,
            ]));
        }

        // ── Cash Registers ──────────────────────────────
        $caisse1 = CashRegister::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'name' => 'Caisse Principale',
            'device_name' => 'Terminal Salle',
            'is_active' => true,
        ]);

        CashRegister::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'name' => 'Caisse Bar',
            'device_name' => 'Terminal Bar',
            'is_active' => true,
        ]);

        // ── Open a Cash Session for admin ──────────────────────────────
        CashSession::create([
            'cash_register_id' => $caisse1->id,
            'user_id' => $admin->id,
            'opening_amount' => 50000, // 50,000 FC de fond de caisse
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
