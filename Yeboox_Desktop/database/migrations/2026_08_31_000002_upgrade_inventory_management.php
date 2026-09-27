<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('symbol', 16)->unique();
            $table->string('type', 24)->default('unit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('unit_of_measures')->insert([
            ['name' => 'Unité', 'symbol' => 'u', 'type' => 'unit', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kilogramme', 'symbol' => 'kg', 'type' => 'weight', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gramme', 'symbol' => 'g', 'type' => 'weight', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Litre', 'symbol' => 'L', 'type' => 'volume', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Millilitre', 'symbol' => 'ml', 'type' => 'volume', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mètre', 'symbol' => 'm', 'type' => 'length', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Boîte', 'symbol' => 'bte', 'type' => 'packaging', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $categories = DB::table('products')->select('category')->whereNotNull('category')->distinct()->pluck('category');
        foreach ($categories as $category) {
            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $category), 0, 12)) ?: 'CAT';
            $code = DB::table('product_categories')->where('code', $baseCode)->exists()
                ? substr($baseCode, 0, 15).'-'.strtoupper(substr(md5($category), 0, 4))
                : $baseCode;
            DB::table('product_categories')->insertOrIgnore([
                'name' => $category,
                'code' => $code,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('product_categories')->count() === 0) {
            DB::table('product_categories')->insert([
                ['name' => 'Général', 'code' => 'GENERAL', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_of_measure_id')->nullable()->after('category')->constrained()->nullOnDelete();
            $table->decimal('stock_qty', 14, 3)->default(0)->change();
            $table->decimal('alert_qty', 14, 3)->default(5)->change();
        });

        $defaultUnitId = DB::table('unit_of_measures')->where('symbol', 'u')->value('id');
        DB::table('products')->where('type', 'product')->update(['unit_of_measure_id' => $defaultUnitId]);

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 14, 3)->change();
            $table->string('reason', 32)->default('adjustment')->after('type');
            $table->string('reference')->nullable()->after('reason');
            $table->decimal('unit_cost', 12, 2)->nullable()->after('quantity');
            $table->decimal('stock_before', 14, 3)->nullable()->after('unit_cost');
            $table->decimal('stock_after', 14, 3)->nullable()->after('stock_before');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['reason', 'reference', 'unit_cost', 'stock_before', 'stock_after']);
            $table->integer('quantity')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_of_measure_id');
            $table->integer('stock_qty')->default(0)->change();
            $table->integer('alert_qty')->default(5)->change();
        });

        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('product_categories');
    }
};
