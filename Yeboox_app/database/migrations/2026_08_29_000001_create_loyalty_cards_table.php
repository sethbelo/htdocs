<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('customer_id');
            $table->string('card_number', 32);
            $table->string('type', 12)->default('simple');
            $table->string('nfc_uid', 100)->nullable();
            $table->string('token', 64);
            $table->string('reader_type', 30)->nullable();
            $table->string('status', 12)->default('active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->unique(['establishment_id', 'customer_id']);
            $table->unique(['establishment_id', 'card_number']);
            $table->unique('token');
            $table->index(['establishment_id', 'nfc_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_cards');
    }
};
