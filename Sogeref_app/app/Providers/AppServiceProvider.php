<?php

namespace App\Providers;

use App\Models;
use App\Observers\AuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');
        Paginator::defaultView('components.ui.pagination');

        // Journal d'audit : toute création / modification / suppression de ces
        // objets métier est tracée (utilisateur, IP, anciennes et nouvelles valeurs).
        foreach ([
            Models\Contact::class, Models\Document::class, Models\Payment::class, Models\Product::class, Models\Opportunity::class,
            Models\Expense::class, Models\FinancialAccount::class, Models\BankTransaction::class, Models\ExchangeRate::class,
            Models\SalesOrder::class, Models\Voucher::class, Models\ManufacturingOrder::class, Models\Site::class,
            Models\Employee::class, Models\LeaveRequest::class, Models\PayrollPeriod::class, Models\ExpenseClaim::class,
            Models\Rental::class, Models\CashSession::class, Models\AttendanceSheet::class, Models\Warehouse::class, Models\Equipment::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
