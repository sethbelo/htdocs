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
        foreach (['products' => ['price', 'cost'], 'sales' => ['total_amount', 'paid_amount', 'tax_amount'], 'sale_items' => ['unit_price', 'total_price', 'tax_amount']] as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $definition = $table->decimal($column, 20, 8);
                    if (in_array($column, ['cost', 'tax_amount'])) {
                        $definition->default(0);
                    }
                    $definition->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['products' => ['price', 'cost'], 'sales' => ['total_amount', 'paid_amount', 'tax_amount'], 'sale_items' => ['unit_price', 'total_price', 'tax_amount']] as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $definition = $table->decimal($column, 20, 2);
                    if (in_array($column, ['cost', 'tax_amount'])) {
                        $definition->default(0);
                    }
                    $definition->change();
                }
            });
        }
    }
};
