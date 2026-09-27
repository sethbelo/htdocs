<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Orders ──────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('branch_id')->nullable();
            $table->string('order_number', 30)->unique();
            $table->uuid('table_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->uuid('user_id'); // Serveur / Vendeur
            $table->uuid('cash_session_id')->nullable();
            $table->string('type', 20)->default('dine_in'); // dine_in, takeaway, delivery
            $table->string('status', 20)->default('draft'); // draft, confirmed, preparing, ready, served, completed, cancelled
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->string('discount_type', 20)->nullable(); // fixed, percentage
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('service_charge', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->uuid('currency_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('priority', 10)->default('normal'); // normal, high, urgent
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments');
            $table->foreign('table_id')->references('id')->on('tables')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->nullOnDelete();
            $table->index(['establishment_id', 'status']);
            $table->index(['establishment_id', 'created_at']);
        });

        // ── Order Items ──────────────────────────────
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('name'); // Snapshot du nom au moment de la commande
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4);
            $table->json('addons')->nullable();
            $table->string('comment')->nullable(); // Instructions spéciales
            $table->string('kitchen_status', 20)->default('pending'); // pending, preparing, ready, served, cancelled
            $table->dateTime('sent_to_kitchen_at')->nullable();
            $table->dateTime('prepared_at')->nullable();
            $table->dateTime('served_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });

        // ── Payments ──────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('order_id');
            $table->uuid('payment_method_id');
            $table->decimal('amount', 15, 4);
            $table->uuid('currency_id')->nullable();
            $table->string('reference')->nullable(); // Réf transaction mobile/carte
            $table->decimal('change_amount', 15, 4)->default(0); // Monnaie rendue
            $table->string('status', 20)->default('completed'); // pending, completed, refunded
            $table->uuid('cash_session_id')->nullable();
            $table->uuid('processed_by');
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');
            $table->foreign('processed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
