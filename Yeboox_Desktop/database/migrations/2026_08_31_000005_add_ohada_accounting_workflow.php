<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_records', function (Blueprint $table) {
            $table->string('journal_code', 10)->default('OD')->after('reference');
            $table->string('entry_type', 30)->default('manual')->after('journal_code');
            $table->nullableMorphs('source');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable()->after('status');
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 30)->default('cash');
            $table->string('treasury_account', 20)->default('5711');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('credit_date');
            $table->string('status', 20)->default('posted');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('reason');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('expense_date');
            $table->string('supplier')->nullable();
            $table->string('description');
            $table->string('expense_account', 20)->default('658');
            $table->string('payment_method', 30)->default('cash');
            $table->string('credit_account', 20)->default('5711');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->string('receipt_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('posted');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('invoice_payments');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('posted_at');
        });

        Schema::table('accounting_records', function (Blueprint $table) {
            $table->dropMorphs('source');
            $table->dropColumn(['journal_code', 'entry_type']);
        });
    }
};
