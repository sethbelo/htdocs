<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 40);
            $table->string('provider', 100)->nullable();
            $table->string('reference', 120)->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('points_redeemed')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        DB::table('payment_methods')->updateOrInsert(
            ['code' => 'points'],
            ['name' => 'Points fidélité', 'icon' => '⭐', 'is_active' => true, 'syscohada_code' => '467', 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
