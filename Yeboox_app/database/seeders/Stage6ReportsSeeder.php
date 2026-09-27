<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class Stage6ReportsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@yeboox.local')->first();
        if (! $admin) {
            return;
        }

        $eid = $admin->establishment_id;
        $products = Product::where('establishment_id', $eid)->get();
        $customers = Customer::where('establishment_id', $eid)->get();
        $paymentMethods = PaymentMethod::where('establishment_id', $eid)->get();
        $cashSession = CashSession::first();

        if ($products->isEmpty() || $paymentMethods->isEmpty() || ! $cashSession) {
            return;
        }

        $types = [OrderType::DineIn, OrderType::Takeaway, OrderType::Delivery];
        $hours = [8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22];

        // Generate 30 orders over past 7 days
        for ($i = 0; $i < 30; $i++) {
            $daysAgo = rand(0, 6);
            $hour = $hours[array_rand($hours)];
            $date = now()->subDays($daysAgo)->setHour($hour)->setMinute(rand(0, 59));

            $order = Order::create([
                'establishment_id' => $eid,
                'order_number' => 'RPT-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'user_id' => $admin->id,
                'customer_id' => rand(0, 1) && $customers->isNotEmpty() ? $customers->random()->id : null,
                'cash_session_id' => $cashSession->id,
                'type' => $types[array_rand($types)],
                'status' => OrderStatus::Completed,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'service_charge' => 0,
                'total' => 0,
                'is_paid' => true,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // 1-4 items per order
            $itemCount = rand(1, 4);
            $subtotal = 0;
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $qty = rand(1, 3);
                $price = (float) $product->price;
                $total = $price * $qty;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total' => $total,
                    'kitchen_status' => 'served',
                    'sent_to_kitchen_at' => $date,
                    'prepared_at' => $date->copy()->addMinutes(rand(5, 20)),
                    'served_at' => $date->copy()->addMinutes(rand(20, 40)),
                ]);
                $subtotal += $total;
            }

            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            // Payment
            Payment::create([
                'establishment_id' => $eid,
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethods->random()->id,
                'amount' => $subtotal,
                'change_amount' => 0,
                'status' => 'completed',
                'cash_session_id' => $cashSession->id,
                'processed_by' => $admin->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }
}
