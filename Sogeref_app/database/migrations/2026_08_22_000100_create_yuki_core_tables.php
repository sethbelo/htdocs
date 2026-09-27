<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country_code', 2)->default('CD');
            $table->string('base_currency', 3)->default('USD');
            $table->string('accounting_plan')->default('SYSCOHADA');
            $table->json('address')->nullable();
            $table->json('settings')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('accent_color', 20)->default('#2563eb');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->foreignId('current_branch_id')->nullable()->after('current_company_id')->constrained('branches')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('locale', 10)->default('fr')->after('phone');
            $table->string('theme', 10)->default('system')->after('locale');
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->primary(['company_id', 'user_id']);
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name');
            $table->string('symbol', 10);
            $table->unsignedTinyInteger('precision')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->date('effective_date');
            $table->string('source')->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'base_currency', 'quote_currency', 'effective_date'], 'exchange_rate_unique');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('client');
            $table->string('reference');
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->unsignedInteger('payment_terms')->default(30);
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('meta')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('portal_enabled')->default(false);
            $table->string('portal_token', 64)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#64748b');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedTinyInteger('probability')->default(10);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages')->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->date('expected_close_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('subject');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type')->default('product');
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('service');
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->default('unité');
            $table->decimal('sale_price', 18, 2)->default(0);
            $table->decimal('purchase_price', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('stock_quantity', 18, 4)->default(0);
            $table->decimal('reorder_level', 18, 4)->default(0);
            $table->boolean('track_stock')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'sku']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('converted_from_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->string('number');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('base_total', 18, 2)->default(0);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('template')->default('modern');
            $table->string('public_token', 64)->nullable()->unique();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'type', 'number']);
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 18, 4);
            $table->string('unit')->default('unité');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_rate', 8, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('subtotal', 18, 2);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('total', 18, 2);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference');
            $table->string('direction')->default('in');
            $table->string('method');
            $table->string('account_type')->default('bank');
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->dateTime('paid_at');
            $table->string('external_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'reference']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamps();
            $table->unique(['payment_id', 'document_id']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference');
            $table->string('description');
            $table->decimal('amount', 18, 2);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->date('expense_date');
            $table->string('status')->default('approved');
            $table->string('receipt_path')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'reference']);
        });

        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('reconcilable');
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->string('description');
            $table->decimal('amount', 18, 2);
            $table->string('status')->default('pending');
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('class', 2);
            $table->string('type');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number');
            $table->date('entry_date');
            $table->string('status')->default('posted');
            $table->string('description');
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('currency_code', 3)->nullable();
            $table->decimal('foreign_amount', 18, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('recurring_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('frequency');
            $table->unsignedSmallInteger('interval')->default(1);
            $table->date('next_run_at');
            $table->date('ends_at')->nullable();
            $table->boolean('auto_send')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel')->default('email');
            $table->string('status')->default('pending');
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('filename');
            $table->string('status')->default('pending');
            $table->json('mapping')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('recurring_documents');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('document_items');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('company_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_branch_id');
            $table->dropConstrainedForeignId('current_company_id');
            $table->dropColumn(['phone', 'locale', 'theme']);
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
