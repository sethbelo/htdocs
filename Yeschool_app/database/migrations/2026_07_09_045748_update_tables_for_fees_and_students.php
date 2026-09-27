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
        if (!Schema::hasColumn('students', 'avatar')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('avatar')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('fee_types', 'exchange_rate')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->decimal('exchange_rate', 10, 2)->default(2500.00)->after('currency');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });

        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
