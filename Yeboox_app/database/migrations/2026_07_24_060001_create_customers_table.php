<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 3)->default('CD');
            $table->string('nif')->nullable();
            $table->string('rccm')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable(); // male, female, other
            $table->integer('loyalty_points')->default(0);
            $table->string('loyalty_level')->default('standard');
            $table->decimal('total_purchases', 20, 4)->default(0);
            $table->integer('total_orders')->default(0);
            $table->datetime('last_purchase_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->index(['establishment_id', 'is_active']);
            $table->index('phone');
            $table->index('email');
            $table->index('loyalty_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
