<?php

namespace App\Providers;

use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'client' => Models\Client::class,
            'order' => Models\Order::class,
            'quote' => Models\Quote::class,
            'invoice' => Models\Invoice::class,
            'sale' => Models\Sale::class,
            'payment' => Models\Payment::class,
            'cash_session' => Models\CashSession::class,
            'print_job' => Models\PrintJob::class,
            'goods_receipt' => Models\GoodsReceipt::class,
            'purchase_request' => Models\PurchaseRequest::class,
            'purchase_order' => Models\PurchaseOrder::class,
            'supplier_invoice' => Models\SupplierInvoice::class,
            'expense_claim' => Models\ExpenseClaim::class,
            'voucher' => Models\Voucher::class,
            'employee' => Models\Employee::class,
            'leave_request' => Models\LeaveRequest::class,
            'payslip' => Models\Payslip::class,
            'treasury_transfer' => Models\TreasuryTransfer::class,
            'document' => Models\Document::class,
            'delivery' => Models\Delivery::class,
            'proof' => Models\Proof::class,
            'machine' => Models\Machine::class,
            'product' => Models\Product::class,
            'site' => Models\Site::class,
            'warehouse' => Models\Warehouse::class,
            'opportunity' => Models\Opportunity::class,
            'task' => Models\Task::class,
            'design_job' => Models\DesignJob::class,
            'cash_register' => Models\CashRegister::class,
            'treasury_account' => Models\TreasuryAccount::class,
            'supplier' => Models\Supplier::class,
            'stock_operation' => Models\StockOperation::class,
            'stock_transfer' => Models\StockTransfer::class,
            'role' => Role::class,
            'permission' => Permission::class,
            'expense_claim_receipt' => Models\DocumentVersion::class,
            'loyalty_account' => Models\LoyaltyAccount::class,
            'purchase_order_item' => Models\PurchaseOrderItem::class,
            'invoice_item' => Models\InvoiceItem::class,
            'employee_contract' => Models\EmployeeContract::class,
            'machine_maintenance' => Models\MachineMaintenance::class,
            'approval_request' => Models\ApprovalRequest::class,
            'comment' => Models\Comment::class,
            'account' => Models\Account::class,
            'journal_entry' => Models\JournalEntry::class,
            'department' => Models\Department::class,
            'setting' => Models\Setting::class,
        ]);

        Gate::before(fn (Models\User $user): ?bool => $user->hasRole('directeur-general') ? true : null);
    }
}
