<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('offline_reference', 100)->nullable()->after('order_number');
            $table->unique(['establishment_id', 'offline_reference'], 'orders_establishment_offline_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_establishment_offline_reference_unique');
            $table->dropColumn('offline_reference');
        });
    }
};
