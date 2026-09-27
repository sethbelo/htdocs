<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('name'); // e.g., "TVA 16%"
            $table->decimal('rate', 8, 4); // 16.0000 for 16%
            $table->string('type')->default('percentage'); // percentage, fixed
            $table->boolean('is_included')->default(false); // Included in price
            $table->string('applies_to')->default('all'); // all, product, category
            $table->uuid('account_id')->nullable(); // Accounting account link
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->index(['establishment_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
