<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('syscohada_code')->nullable()->after('code');
        });

        Schema::table('accounting_records', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('id');
            $table->decimal('debit', 12, 2)->default(0)->after('syscohada_code');
            $table->decimal('credit', 12, 2)->default(0)->after('debit');
        });

        // Migrate existing data
        DB::table('accounting_records')->get()->each(function ($record) {
            if ($record->type === 'income') {
                DB::table('accounting_records')->where('id', $record->id)->update(['credit' => $record->amount]);
            } else {
                DB::table('accounting_records')->where('id', $record->id)->update(['debit' => $record->amount]);
            }
        });

        Schema::table('accounting_records', function (Blueprint $table) {
            $table->dropColumn(['type', 'amount', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_records', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
        });

        DB::table('accounting_records')->get()->each(function ($record) {
            if ($record->debit > 0) {
                DB::table('accounting_records')->where('id', $record->id)->update(['type' => 'expense', 'amount' => $record->debit, 'category' => 'autre']);
            } else {
                DB::table('accounting_records')->where('id', $record->id)->update(['type' => 'income', 'amount' => $record->credit, 'category' => 'autre']);
            }
        });

        Schema::table('accounting_records', function (Blueprint $table) {
            $table->dropColumn(['reference', 'debit', 'credit']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('syscohada_code');
        });
    }
};
