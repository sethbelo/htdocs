<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('register_sessions', function (Blueprint $table): void {
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->string('secondary_currency', 3)->default('CDF')->after('base_currency');
            $table->decimal('base_to_secondary_rate', 24, 12)->default(2800)->after('secondary_currency');
        });

        DB::table('register_sessions')->update([
            'base_to_secondary_rate' => DB::raw('exchange_rate'),
        ]);
    }

    public function down(): void
    {
        Schema::table('register_sessions', function (Blueprint $table): void {
            $table->dropColumn(['base_currency', 'secondary_currency', 'base_to_secondary_rate']);
        });
    }
};
