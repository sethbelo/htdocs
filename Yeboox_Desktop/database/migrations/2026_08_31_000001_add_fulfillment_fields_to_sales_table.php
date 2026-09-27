<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('fulfillment_type', 24)->default('dine_in')->after('department');
            $table->string('table_name')->nullable()->after('fulfillment_type');
            $table->string('delivery_phone', 40)->nullable()->after('table_name');
            $table->text('delivery_address')->nullable()->after('delivery_phone');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_type', 'table_name', 'delivery_phone', 'delivery_address']);
        });
    }
};
