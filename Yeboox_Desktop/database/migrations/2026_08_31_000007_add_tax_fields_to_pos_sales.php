<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('tax_amount', 14, 2)->default(0)->after('discount_amount');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('sales_tax_rate', 5, 2)->default(0)->after('unit_price');
            $table->decimal('tax_amount', 14, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['sales_tax_rate', 'tax_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });
    }
};
