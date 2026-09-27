<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clients
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer('points')->default(0);
            $table->timestamps();
        });

        // 2. Abonnements
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('plan_name'); // 'Mensuel Spa', 'Trimestriel Fitness', 'Annuel VIP'
            $table->decimal('price', 10, 2);
            $table->integer('points_granted')->default(0);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('active'); // active, expired, cancelled
            $table->timestamps();
        });

        // 3. Produits et Services (Inventaire)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('category'); // 'Bar', 'Salon de coiffure', 'Spa', 'Fitness'
            $table->string('type'); // 'product', 'service'
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->integer('stock_qty')->default(0); // Uniquement pour type='product'
            $table->integer('alert_qty')->default(5);
            $table->timestamps();
        });

        // 4. Rendez-vous
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('products')->onDelete('cascade');
            $table->dateTime('date_time');
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Sessions de caisse
        Schema::create('register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_balance', 10, 2);
            $table->decimal('closing_balance', 10, 2)->default(0.00);
            $table->decimal('cash_sales', 10, 2)->default(0.00);
            $table->decimal('mobile_sales', 10, 2)->default(0.00);
            $table->decimal('points_sales', 10, 2)->default(0.00);
            $table->string('status')->default('open'); // open, closed
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 6. Ventes (Transactions)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('register_session_id')->nullable()->constrained()->onDelete('set null');
            $table->string('department'); // 'bar', 'salon'
            $table->string('payment_method'); // 'cash', 'mpesa', 'airtel', 'orange', 'points'
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2);
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->timestamps();
        });

        // 7. Lignes de Ventes
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        // 8. Comptabilité SYSCOHADA RDC
        Schema::create('accounting_records', function (Blueprint $table) {
            $table->id();
            $table->date('record_date');
            $table->string('type'); // 'income', 'expense'
            $table->string('syscohada_code')->nullable(); // Ex: 701, 601, etc.
            $table->string('category'); // 'vente', 'achat_stock', 'loyer', 'salaire', 'autre'
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        // 9. Mouvements de stock (Optionnel pour détailler les achats/pertes)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'in' (achat/appro), 'out' (vente/perte/consommation)
            $table->integer('quantity');
            $table->string('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 10. Journal d'activités (Audit)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('accounting_records');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('register_sessions');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('products');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('customers');
    }
};
