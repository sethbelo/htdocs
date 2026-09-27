<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Payment Methods ──────────────────────────────
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('name'); // Espèces, Mobile Money, Carte bancaire
            $table->string('code', 30); // cash, mobile_money, card, etc.
            $table->string('type', 30)->default('cash'); // cash, card, mobile, ewallet
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_reference')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments');
            $table->unique(['establishment_id', 'code']);
        });

        // ── Cash Registers ──────────────────────────────
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('branch_id')->nullable();
            $table->string('name'); // Caisse 1, Caisse Bar
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments');
        });

        // ── Cash Sessions ──────────────────────────────
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cash_register_id');
            $table->uuid('user_id'); // Caissier
            $table->decimal('opening_amount', 15, 4)->default(0);
            $table->decimal('closing_amount', 15, 4)->nullable();
            $table->decimal('expected_amount', 15, 4)->nullable();
            $table->decimal('difference', 15, 4)->nullable();
            $table->text('difference_note')->nullable();
            $table->string('status', 20)->default('open'); // open, closed, validated
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->uuid('validated_by')->nullable();
            $table->json('cash_count')->nullable(); // Détail comptage espèces
            $table->timestamps();

            $table->foreign('cash_register_id')->references('id')->on('cash_registers');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('payment_methods');
    }
};
