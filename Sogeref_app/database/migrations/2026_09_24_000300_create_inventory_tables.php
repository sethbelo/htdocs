<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Entrepôts, stock & matériel
|--------------------------------------------------------------------------
| Entrepôts (central, atelier, chantier, showroom), niveaux de stock par
| entrepôt, mouvements tracés, inventaires physiques et parc matériel.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('type', 20)->default('central');          // central | atelier | chantier | showroom
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('reserved', 18, 4)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('type', 20);                               // in | out | transfer | adjust | consume | produce | sale | purchase
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('reference')->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->index(['company_id', 'moved_at']);
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 30);
            $table->date('count_date');
            $table->string('status', 20)->default('draft');           // draft | validated
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected', 18, 4)->default(0);
            $table->decimal('counted', 18, 4)->nullable();
            $table->timestamps();
        });

        // Parc matériel : outillage durable, engins, équipements louables.
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status', 20)->default('available');       // available | assigned | rented | maintenance | out_of_service
            $table->decimal('purchase_value', 14, 2)->default(0);
            $table->boolean('is_rentable')->default(false);
            $table->decimal('daily_rent_price', 12, 2)->default(0);
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        foreach (['equipment', 'inventory_count_lines', 'inventory_counts', 'stock_moves', 'stock_levels', 'warehouses'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
