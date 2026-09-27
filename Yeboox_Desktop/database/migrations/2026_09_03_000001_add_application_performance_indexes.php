<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index composés selon les filtres et tris réellement employés par Livewire.
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['date_time', 'status'], 'appointments_date_status_idx');
            $table->index(['status', 'date_time'], 'appointments_status_date_idx');
            $table->index(['employee_id', 'date_time'], 'appointments_employee_date_idx');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->index('created_at', 'sales_created_idx');
            $table->index(['department', 'created_at'], 'sales_department_created_idx');
            $table->index(['register_session_id', 'created_at'], 'sales_session_created_idx');
            $table->index(['user_id', 'created_at'], 'sales_user_created_idx');
            $table->index(['customer_id', 'created_at'], 'sales_customer_created_idx');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['product_id', 'sale_id'], 'sale_items_product_sale_idx');
        });
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->index(['sale_id', 'payment_method_id'], 'sale_payments_sale_method_idx');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index(['type', 'is_active', 'name'], 'products_type_active_name_idx');
            $table->index(['category', 'type', 'is_active'], 'products_category_type_active_idx');
            $table->index(['is_active', 'name'], 'products_active_name_idx');
        });
        Schema::table('customers', fn (Blueprint $table) => $table->index(['is_active', 'name'], 'customers_active_name_idx'));
        Schema::table('payment_methods', fn (Blueprint $table) => $table->index(['is_active', 'id'], 'payment_methods_active_id_idx'));
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'stock_movements_product_created_idx');
            $table->index(['reason', 'created_at'], 'stock_movements_reason_created_idx');
            $table->index(['type', 'created_at'], 'stock_movements_type_created_idx');
        });
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->index(['status', 'opened_at'], 'register_sessions_status_opened_idx');
            $table->index(['user_id', 'opened_at'], 'register_sessions_user_opened_idx');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['status', 'ends_at'], 'subscriptions_status_ends_idx');
            $table->index(['customer_id', 'status', 'ends_at'], 'subscriptions_customer_status_ends_idx');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['status', 'invoice_date'], 'invoices_status_date_idx');
            $table->index(['customer_id', 'invoice_date'], 'invoices_customer_date_idx');
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index(['product_id', 'invoice_id'], 'invoice_items_product_invoice_idx');
        });
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->index(['invoice_id', 'payment_date'], 'invoice_payments_invoice_date_idx');
        });
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->index(['invoice_id', 'credit_date'], 'credit_notes_invoice_date_idx');
        });
        Schema::table('accounting_records', function (Blueprint $table) {
            $table->index(['record_date', 'syscohada_code'], 'accounting_records_date_account_idx');
            $table->index(['journal_code', 'record_date'], 'accounting_records_journal_date_idx');
        });
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['created_at', 'action'], 'activity_logs_created_action_idx');
            $table->index(['user_id', 'created_at'], 'activity_logs_user_created_idx');
        });
        Schema::table('expenses', fn (Blueprint $table) => $table->index(['status', 'expense_date'], 'expenses_status_date_idx'));
        Schema::table('purchases', fn (Blueprint $table) => $table->index(['status', 'purchase_date'], 'purchases_status_date_idx'));
        Schema::table('disbursement_requests', fn (Blueprint $table) => $table->index(['status', 'requested_date'], 'disbursements_status_date_idx'));
        Schema::table('equity_movements', fn (Blueprint $table) => $table->index(['type', 'movement_date'], 'equity_type_date_idx'));
        Schema::table('jobs', fn (Blueprint $table) => $table->index(['queue', 'reserved_at', 'available_at'], 'jobs_queue_ready_idx'));
    }

    public function down(): void
    {
        $indexes = [
            'appointments' => ['appointments_date_status_idx', 'appointments_status_date_idx', 'appointments_employee_date_idx'],
            'sales' => ['sales_created_idx', 'sales_department_created_idx', 'sales_session_created_idx', 'sales_user_created_idx', 'sales_customer_created_idx'],
            'sale_items' => ['sale_items_product_sale_idx'], 'sale_payments' => ['sale_payments_sale_method_idx'],
            'products' => ['products_type_active_name_idx', 'products_category_type_active_idx', 'products_active_name_idx'],
            'customers' => ['customers_active_name_idx'], 'payment_methods' => ['payment_methods_active_id_idx'],
            'stock_movements' => ['stock_movements_product_created_idx', 'stock_movements_reason_created_idx', 'stock_movements_type_created_idx'],
            'register_sessions' => ['register_sessions_status_opened_idx', 'register_sessions_user_opened_idx'],
            'subscriptions' => ['subscriptions_status_ends_idx', 'subscriptions_customer_status_ends_idx'],
            'invoices' => ['invoices_status_date_idx', 'invoices_customer_date_idx'],
            'invoice_items' => ['invoice_items_product_invoice_idx'], 'invoice_payments' => ['invoice_payments_invoice_date_idx'],
            'credit_notes' => ['credit_notes_invoice_date_idx'],
            'accounting_records' => ['accounting_records_date_account_idx', 'accounting_records_journal_date_idx'],
            'activity_logs' => ['activity_logs_created_action_idx', 'activity_logs_user_created_idx'],
            'expenses' => ['expenses_status_date_idx'], 'purchases' => ['purchases_status_date_idx'],
            'disbursement_requests' => ['disbursements_status_date_idx'], 'equity_movements' => ['equity_type_date_idx'],
            'jobs' => ['jobs_queue_ready_idx'],
        ];

        foreach ($indexes as $tableName => $tableIndexes) {
            // DROP INDEX IF EXISTS tolère une mise à jour Windows interrompue ou partielle.
            foreach ($tableIndexes as $index) {
                DB::statement('DROP INDEX IF EXISTS "'.str_replace('"', '""', $index).'"');
            }
        }
    }
};
