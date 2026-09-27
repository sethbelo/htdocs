<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;

class Stage4KdsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@yeboox.local')->first();
        if (! $admin) {
            return;
        }

        $establishmentId = $admin->establishment_id;
        $products = Product::where('establishment_id', $establishmentId)->get();
        $tables = Table::where('establishment_id', $establishmentId)->get();
        $customers = Customer::where('establishment_id', $establishmentId)->get();
        $cashSession = CashSession::where('user_id', $admin->id)->where('status', 'open')->first();
        $cashMethod = PaymentMethod::where('establishment_id', $establishmentId)->where('code', 'cash')->first();

        if ($products->isEmpty() || ! $cashSession || ! $cashMethod) {
            return;
        }

        // ── Order 1: Pending (just arrived) ────────────────────────────────
        $order1 = Order::create([
            'establishment_id' => $establishmentId,
            'order_number' => Order::generateNumber($establishmentId),
            'table_id' => $tables->where('number', 'T-01')->first()?->id,
            'user_id' => $admin->id,
            'cash_session_id' => $cashSession->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Confirmed->value,
            'subtotal' => 0,
            'total' => 0,
            'is_paid' => true,
        ]);

        $items1 = $products->whereIn('name', ['Poulet Braisé', 'Riz aux légumes', 'Coca-Cola 33cl'])->values();
        $total1 = 0;
        foreach ($items1 as $p) {
            OrderItem::create([
                'order_id' => $order1->id,
                'product_id' => $p->id,
                'name' => $p->name,
                'quantity' => rand(1, 2),
                'unit_price' => $p->selling_price,
                'total' => $p->selling_price * rand(1, 2),
                'kitchen_status' => 'pending',
                'sent_to_kitchen_at' => now()->subMinutes(3),
            ]);
            $total1 += $p->selling_price;
        }
        $order1->update(['subtotal' => $total1, 'total' => $total1, 'created_at' => now()->subMinutes(3)]);

        // ── Order 2: Preparing (in progress) ────────────────────────────────
        $order2 = Order::create([
            'establishment_id' => $establishmentId,
            'order_number' => Order::generateNumber($establishmentId),
            'table_id' => $tables->where('number', 'T-05')->first()?->id,
            'customer_id' => $customers->first()?->id,
            'user_id' => $admin->id,
            'cash_session_id' => $cashSession->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Preparing->value,
            'subtotal' => 0,
            'total' => 0,
            'notes' => 'Client allergique aux arachides',
            'is_paid' => true,
        ]);

        $items2 = $products->whereIn('name', ['Brochettes de Boeuf (x6)', 'Frites', 'Heineken 33cl'])->values();
        $total2 = 0;
        foreach ($items2 as $i => $p) {
            OrderItem::create([
                'order_id' => $order2->id,
                'product_id' => $p->id,
                'name' => $p->name,
                'quantity' => 1,
                'unit_price' => $p->selling_price,
                'total' => $p->selling_price,
                'kitchen_status' => $i === 0 ? 'preparing' : 'pending',
                'sent_to_kitchen_at' => now()->subMinutes(8),
            ]);
            $total2 += $p->selling_price;
        }
        $order2->update(['subtotal' => $total2, 'total' => $total2, 'created_at' => now()->subMinutes(8)]);

        // ── Order 3: Preparing with urgent timer ────────────────────────────────
        $order3 = Order::create([
            'establishment_id' => $establishmentId,
            'order_number' => Order::generateNumber($establishmentId),
            'user_id' => $admin->id,
            'cash_session_id' => $cashSession->id,
            'type' => 'takeaway',
            'status' => OrderStatus::Preparing->value,
            'subtotal' => 0,
            'total' => 0,
            'is_paid' => true,
        ]);

        $items3 = $products->whereIn('name', ['Spaghetti Bolognaise', 'Salade César', 'Café Espresso'])->values();
        $total3 = 0;
        foreach ($items3 as $p) {
            OrderItem::create([
                'order_id' => $order3->id,
                'product_id' => $p->id,
                'name' => $p->name,
                'quantity' => 1,
                'unit_price' => $p->selling_price,
                'total' => $p->selling_price,
                'kitchen_status' => 'preparing',
                'sent_to_kitchen_at' => now()->subMinutes(18),
            ]);
            $total3 += $p->selling_price;
        }
        $order3->update(['subtotal' => $total3, 'total' => $total3, 'created_at' => now()->subMinutes(18)]);

        // ── Order 4: Ready (waiting for service) ────────────────────────────────
        $order4 = Order::create([
            'establishment_id' => $establishmentId,
            'order_number' => Order::generateNumber($establishmentId),
            'table_id' => $tables->where('number', 'T-03')->first()?->id,
            'user_id' => $admin->id,
            'cash_session_id' => $cashSession->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Ready->value,
            'subtotal' => 0,
            'total' => 0,
            'is_paid' => true,
        ]);

        $items4 = $products->whereIn('name', ['Tilapia Frit', 'Banane Plantain', 'Primus 65cl'])->values();
        $total4 = 0;
        foreach ($items4 as $p) {
            OrderItem::create([
                'order_id' => $order4->id,
                'product_id' => $p->id,
                'name' => $p->name,
                'quantity' => 1,
                'unit_price' => $p->selling_price,
                'total' => $p->selling_price,
                'kitchen_status' => 'ready',
                'sent_to_kitchen_at' => now()->subMinutes(12),
                'prepared_at' => now()->subMinutes(2),
            ]);
            $total4 += $p->selling_price;
        }
        $order4->update(['subtotal' => $total4, 'total' => $total4, 'created_at' => now()->subMinutes(12)]);

        // Create payments for all orders
        foreach ([$order1, $order2, $order3, $order4] as $order) {
            Payment::create([
                'establishment_id' => $establishmentId,
                'order_id' => $order->id,
                'payment_method_id' => $cashMethod->id,
                'amount' => $order->total,
                'status' => 'completed',
                'cash_session_id' => $cashSession->id,
                'processed_by' => $admin->id,
            ]);
        }
    }
}
