<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('category_id');
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('rfid_tag')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('type')->default('simple'); // simple, variable, composite, service
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('promo_price', 15, 4)->nullable();
            $table->datetime('promo_start')->nullable();
            $table->datetime('promo_end')->nullable();
            $table->uuid('tax_id')->nullable();
            $table->uuid('unit_id')->nullable();
            $table->decimal('alert_quantity', 15, 4)->default(5);
            $table->uuid('kitchen_station_id')->nullable();
            $table->uuid('printer_id')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('loyalty_points')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('tax_id')->references('id')->on('taxes')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();

            $table->index(['establishment_id', 'is_active', 'is_available']);
            $table->index(['establishment_id', 'category_id']);
            $table->index('barcode');
            $table->index('sku');
            $table->index('rfid_tag');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
