<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('name'); // e.g., "Supplément fromage"
            $table->decimal('price', 15, 4)->default(0);
            $table->string('category')->default('supplement'); // supplement, accompaniment, sauce, topping
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->index(['establishment_id', 'is_active']);
        });

        // Pivot table: which addons are available for which products
        Schema::create('product_addon_product', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('product_addon_id');
            $table->primary(['product_id', 'product_addon_id']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_addon_id')->references('id')->on('product_addons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addon_product');
        Schema::dropIfExists('product_addons');
    }
};
