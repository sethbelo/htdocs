<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modes de connexion, clôture de caisse détaillée, transferts inter-entrepôts suivis
     * et bons d'entrée / de sortie magasin (avec la personne qui effectue l'action).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_mode', 20)->default('password')->after('password');
        });

        Schema::table('login_histories', function (Blueprint $table) {
            $table->string('method', 20)->nullable()->after('identifier');
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->json('denominations')->nullable()->after('counted_balance');
            $table->foreignId('supervisor_id')->nullable()->after('closed_by')->constrained('users')->nullOnDelete();
            $table->decimal('deposit_amount', 15, 2)->default(0)->after('difference');
            $table->foreignId('deposit_treasury_account_id')->nullable()->after('deposit_amount')->constrained('treasury_accounts')->nullOnDelete();
            $table->timestamp('accounting_synced_at')->nullable()->after('closed_at');
        });

        Schema::create('stock_operations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('type', 10)->index();
            $table->string('reason', 40);
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('counterparty_type', 20)->default('other');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('performed_by_name');
            $table->string('performed_by_phone', 30)->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_value', 15, 2)->default(0);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('operated_at')->index();
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status', 20)->default('requested')->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('requested_at');
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->string('carrier_name')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('reception_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_requested', 15, 3);
            $table->decimal('quantity_shipped', 15, 3)->nullable();
            $table->decimal('quantity_received', 15, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_operations');

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('deposit_treasury_account_id');
            $table->dropColumn(['denominations', 'deposit_amount', 'accounting_synced_at']);
        });

        Schema::table('login_histories', fn (Blueprint $table) => $table->dropColumn('method'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('login_mode'));
    }
};
