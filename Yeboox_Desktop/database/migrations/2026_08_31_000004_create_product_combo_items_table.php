<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->timestamps();

            $table->unique(['combo_product_id', 'component_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_items');
    }
};
