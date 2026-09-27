<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('icon', 50)->nullable()->after('image_path');
            $table->boolean('track_stock')->default(true)->after('alert_quantity');
        });

        Schema::create('product_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('component_product_id');
            $table->uuid('unit_id')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('component_product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            $table->unique(['product_id', 'component_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['icon', 'track_stock']);
        });
    }
};
