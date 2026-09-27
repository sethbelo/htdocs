<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->unsignedInteger('loyalty_service_count')->default(0);
            $table->unsignedInteger('loyalty_reward_balance')->default(0);
            $table->index(['is_active', 'loyalty_reward_balance'], 'customers_loyalty_rewards_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_loyalty_rewards_index');
            $table->dropColumn(['loyalty_service_count', 'loyalty_reward_balance']);
        });
    }
};
