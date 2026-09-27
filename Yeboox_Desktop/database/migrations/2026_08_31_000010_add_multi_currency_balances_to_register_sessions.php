<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->decimal('opening_balance_usd', 14, 2)->default(0)->after('opening_balance');
            $table->decimal('opening_balance_cdf', 16, 2)->default(0)->after('opening_balance_usd');
            $table->decimal('closing_balance_usd', 14, 2)->default(0)->after('closing_balance');
            $table->decimal('closing_balance_cdf', 16, 2)->default(0)->after('closing_balance_usd');
            $table->decimal('exchange_rate', 14, 4)->default(2800)->after('closing_balance_cdf');
        });

        DB::table('register_sessions')->update([
            'opening_balance_usd' => DB::raw('opening_balance'),
            'closing_balance_usd' => DB::raw('closing_balance'),
        ]);
    }

    public function down(): void
    {
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'opening_balance_usd', 'opening_balance_cdf',
                'closing_balance_usd', 'closing_balance_cdf', 'exchange_rate',
            ]);
        });
    }
};
