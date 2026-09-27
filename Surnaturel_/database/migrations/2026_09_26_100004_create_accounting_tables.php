<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plan comptable, journaux, écritures et trésorerie (caisses, banques, Mobile Money).
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->unsignedTinyInteger('class');
            $table->string('type', 20);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->foreignId('default_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('treasury_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency', 3)->default('CDF');
            $table->decimal('balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->date('entry_date')->index();
            $table->string('reference')->nullable()->index();
            $table->string('description');
            $table->nullableMorphs('source');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('posted');
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('label');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('treasury_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('from_treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->foreignId('to_treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('transferred_at');
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('treasury_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('direction', 3);
            $table->decimal('amount', 15, 2);
            $table->date('expected_on');
            $table->boolean('is_realized')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['treasury_forecasts', 'treasury_transfers', 'journal_entry_lines', 'journal_entries', 'treasury_accounts', 'journals', 'accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
