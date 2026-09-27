<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('eyebrow')->nullable();
            $table->string('tagline');
            $table->decimal('monthly_price', 12, 2);
            $table->decimal('annual_price', 12, 2);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('max_locations')->default(1);
            $table->json('features');
            $table->string('cta_label')->default('Choisir cette offre');
            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
