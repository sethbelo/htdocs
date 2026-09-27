<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('from_currency_id');
            $table->uuid('to_currency_id');
            $table->decimal('buy_rate', 15, 6);
            $table->decimal('sell_rate', 15, 6);
            $table->date('effective_date');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('from_currency_id')->references('id')->on('currencies');
            $table->foreign('to_currency_id')->references('id')->on('currencies');
            $table->index(['establishment_id', 'from_currency_id', 'to_currency_id', 'effective_date'], 'exchange_rates_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
