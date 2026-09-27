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
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->dropColumn(['cash_sales', 'mobile_sales', 'points_sales']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->decimal('cash_sales', 10, 2)->default(0.00);
            $table->decimal('mobile_sales', 10, 2)->default(0.00);
            $table->decimal('points_sales', 10, 2)->default(0.00);
        });
    }
};
