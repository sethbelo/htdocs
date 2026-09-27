<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('financial_account_id')
                ->nullable()
                ->after('account_type')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('financial_account_id')
                ->nullable()
                ->after('payment_method')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            // Une opération métier ne doit produire qu'une seule écriture automatique.
            $table->unique(['source_type', 'source_id'], 'journal_entries_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_source_unique');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financial_account_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financial_account_id');
        });
    }
};
