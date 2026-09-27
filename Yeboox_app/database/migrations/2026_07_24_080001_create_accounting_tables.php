<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Exercices comptables ──────────────────────────────
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('name'); // "Exercice 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closing', 'closed'])->default('open');
            $table->uuid('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });

        // ── Plan comptable OHADA ──────────────────────────────
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('code', 10)->index(); // OHADA account number: 1-9 classes
            $table->string('name');
            $table->unsignedTinyInteger('class'); // 1-9 OHADA classes
            $table->enum('type', [
                'asset',      // Actif (classes 2, 3, 4D, 5D)
                'liability',  // Passif (classes 1, 4C, 5C)
                'equity',     // Capitaux propres (classe 1)
                'revenue',    // Produits (classes 7, 8+)
                'expense',    // Charges (classes 6, 8-)
            ]);
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_group')->default(false); // true = compte collectif
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
            $table->unique(['establishment_id', 'code']);
        });

        // ── Journaux comptables ──────────────────────────────
        Schema::create('journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('code', 10); // VT, AC, BQ, CA, OD
            $table->string('name');
            $table->enum('type', ['sales', 'purchases', 'bank', 'cash', 'operations', 'opening', 'closing']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->unique(['establishment_id', 'code']);
        });

        // ── Écritures comptables ──────────────────────────────
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('journal_id');
            $table->uuid('fiscal_year_id');
            $table->string('entry_number'); // VT-2026-0001
            $table->date('entry_date');
            $table->string('reference')->nullable(); // N° facture, commande, etc.
            $table->string('description');
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->uuid('created_by');
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('source_type')->nullable(); // Order, Payment, Expense...
            $table->uuid('source_id')->nullable();
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('journal_id')->references('id')->on('journals')->cascadeOnDelete();
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });

        // ── Lignes d'écritures (débit/crédit) ──────────────────────────────
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('journal_entry_id');
            $table->uuid('account_id');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->string('label')->nullable();
            $table->string('third_party_type')->nullable(); // Customer, Supplier
            $table->uuid('third_party_id')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        // ── Dépenses ──────────────────────────────
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('account_id')->nullable(); // Compte de charge
            $table->uuid('payment_method_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('category'); // loyer, transport, salaires, fournitures...
            $table->string('description');
            $table->decimal('amount', 18, 4);
            $table->date('expense_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('fiscal_years');
    }
};
