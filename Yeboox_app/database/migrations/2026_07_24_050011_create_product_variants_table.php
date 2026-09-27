<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('name'); // e.g., "Grande", "Rouge"
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('selling_price', 15, 4);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->json('attributes')->nullable(); // {taille: "L", couleur: "Rouge"}
            $table->decimal('stock_quantity', 15, 4)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['product_id', 'is_active']);
            $table->index('barcode');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
