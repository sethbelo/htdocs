<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V2\AccountingController as V2AccountingController;
use App\Http\Controllers\Api\V2\Company\LocationController as V2LocationController;
use App\Http\Controllers\Api\V2\Company\UserController as V2UserController;
use App\Http\Controllers\Api\V2\ConfigurationController as V2ConfigurationController;
use App\Http\Controllers\Api\V2\Master\CompanyController as V2CompanyController;
use App\Http\Controllers\Api\V2\ProductController as V2ProductController;
use App\Http\Controllers\Api\V2\StockController as V2StockController;
use App\Http\Controllers\Api\V2\SyncSaleController;
use App\Http\Controllers\Api\V2\TenantContextController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'throttle:api', 'tenant'])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->middleware('api.access:profile:read');
        Route::delete('/auth/logout', [AuthController::class, 'logout'])->middleware('api.access:profile:read');
        Route::get('/bootstrap', BootstrapController::class)->middleware('api.access:bootstrap:read');

        Route::get('/products', [ProductController::class, 'index'])->middleware(['pos.subscription', 'api.access:catalog:read,manage POS,manage inventory']);
        Route::get('/products/{product}', [ProductController::class, 'show'])->middleware(['pos.subscription', 'api.access:catalog:read,manage POS,manage inventory']);

        Route::get('/customers', [CustomerController::class, 'index'])->middleware(['pos.subscription', 'api.access:customers:read,manage customers']);
        Route::post('/customers', [CustomerController::class, 'store'])->middleware(['pos.subscription', 'api.access:customers:write,manage customers']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware(['pos.subscription', 'api.access:customers:read,manage customers']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware(['pos.subscription', 'api.access:customers:write,manage customers']);

        Route::get('/appointments', [AppointmentController::class, 'index'])->middleware(['pos.subscription', 'api.access:appointments:read,manage appointments']);
        Route::post('/appointments', [AppointmentController::class, 'store'])->middleware(['pos.subscription', 'api.access:appointments:write,manage appointments']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->middleware(['pos.subscription', 'api.access:appointments:read,manage appointments']);
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->middleware(['pos.subscription', 'api.access:appointments:write,manage appointments']);

        Route::get('/register/current', [RegisterController::class, 'current'])->middleware(['pos.subscription', 'api.access:register:read,manage POS']);
        Route::post('/register/open', [RegisterController::class, 'open'])->middleware(['pos.subscription', 'api.access:register:write,manage register']);
        Route::post('/register/close', [RegisterController::class, 'close'])->middleware(['pos.subscription', 'api.access:register:write,manage register']);

        Route::get('/sales', [SaleController::class, 'index'])->middleware(['pos.subscription', 'api.access:sales:read,manage POS,manage sales,manage reports']);
        Route::post('/sales', [SaleController::class, 'store'])->middleware(['pos.subscription', 'api.access:sales:write,manage POS']);
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->middleware(['pos.subscription', 'api.access:sales:read,manage POS,manage sales,manage reports']);
    });
});

Route::prefix('v2')->group(function (): void {
    Route::post('/sync/sales', SyncSaleController::class)->middleware(['sync.terminal', 'throttle:sync']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('/master/companies', [V2CompanyController::class, 'index'])->middleware('master.admin');
        Route::post('/master/companies', [V2CompanyController::class, 'store'])->middleware('master.admin');
        Route::get('/master/companies/{company}', [V2CompanyController::class, 'show'])->middleware('master.admin');
        Route::put('/master/companies/{company}', [V2CompanyController::class, 'update'])->middleware('master.admin');

        Route::middleware('tenant')->group(function (): void {
            Route::get('/auth/me', [AuthController::class, 'me'])->middleware('api.access:profile:read');
            Route::delete('/auth/logout', [AuthController::class, 'logout'])->middleware('api.access:profile:read');
            Route::get('/context', [TenantContextController::class, 'show'])->middleware('api.access:bootstrap:read');
            Route::put('/context', [TenantContextController::class, 'switch'])->middleware('api.access:bootstrap:read');
            Route::get('/bootstrap', BootstrapController::class)->middleware('api.access:bootstrap:read');

            Route::middleware(['company.admin'])->group(function (): void {
                Route::get('/company/locations', [V2LocationController::class, 'index'])->middleware('api.access:tenant:read,manage administration');
                Route::post('/company/establishments', [V2LocationController::class, 'storeEstablishment'])->middleware('api.access:tenant:write,manage administration');
                Route::post('/company/establishments/{establishment}/points-of-sale', [V2LocationController::class, 'storePointOfSale'])->middleware('api.access:tenant:write,manage administration');
                Route::put('/company/points-of-sale/{pointOfSale}', [V2LocationController::class, 'updatePointOfSale'])->middleware('api.access:tenant:write,manage administration');
                Route::post('/company/points-of-sale/{pointOfSale}/subscriptions', [V2LocationController::class, 'subscribe'])->middleware('api.access:tenant:write,manage administration');
                Route::get('/company/users', [V2UserController::class, 'index'])->middleware('api.access:users:read,manage users');
                Route::post('/company/users', [V2UserController::class, 'store'])->middleware('api.access:users:write,manage users');
                Route::put('/company/users/{user}', [V2UserController::class, 'update'])->middleware('api.access:users:write,manage users');
                Route::get('/configuration', [V2ConfigurationController::class, 'show'])->middleware('api.access:configuration:read,manage settings');
                Route::put('/configuration', [V2ConfigurationController::class, 'update'])->middleware('api.access:configuration:write,manage settings');
            });

            Route::middleware('pos.subscription')->group(function (): void {
                Route::get('/products', [V2ProductController::class, 'index'])->middleware('api.access:catalog:read,manage POS,manage inventory');
                Route::post('/products', [V2ProductController::class, 'store'])->middleware('api.access:catalog:write,manage inventory');
                Route::get('/products/{product}', [V2ProductController::class, 'show'])->middleware('api.access:catalog:read,manage POS,manage inventory');
                Route::match(['put', 'patch'], '/products/{product}', [V2ProductController::class, 'update'])->middleware('api.access:catalog:write,manage inventory');
                Route::delete('/products/{product}', [V2ProductController::class, 'destroy'])->middleware('api.access:catalog:write,manage inventory');
                Route::get('/stock/movements', [V2StockController::class, 'index'])->middleware('api.access:stock:read,manage inventory');
                Route::post('/stock/products/{product}/adjust', [V2StockController::class, 'adjust'])->middleware('api.access:stock:write,manage inventory');
                Route::get('/accounting/records', [V2AccountingController::class, 'index'])->middleware('api.access:accounting:read,manage accounting');
                Route::get('/customers', [CustomerController::class, 'index'])->middleware('api.access:customers:read,manage customers');
                Route::post('/customers', [CustomerController::class, 'store'])->middleware('api.access:customers:write,manage customers');
                Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('api.access:customers:read,manage customers');
                Route::match(['put', 'patch'], '/customers/{customer}', [CustomerController::class, 'update'])->middleware('api.access:customers:write,manage customers');
                Route::get('/appointments', [AppointmentController::class, 'index'])->middleware('api.access:appointments:read,manage appointments');
                Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('api.access:appointments:write,manage appointments');
                Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->middleware('api.access:appointments:read,manage appointments');
                Route::match(['put', 'patch'], '/appointments/{appointment}', [AppointmentController::class, 'update'])->middleware('api.access:appointments:write,manage appointments');
                Route::get('/register/current', [RegisterController::class, 'current'])->middleware('api.access:register:read,manage POS');
                Route::post('/register/open', [RegisterController::class, 'open'])->middleware('api.access:register:write,manage register');
                Route::post('/register/close', [RegisterController::class, 'close'])->middleware('api.access:register:write,manage register');
                Route::get('/sales', [SaleController::class, 'index'])->middleware('api.access:sales:read,manage POS,manage sales,manage reports');
                Route::post('/sales', [SaleController::class, 'store'])->middleware('api.access:sales:write,manage POS');
                Route::get('/sales/{sale}', [SaleController::class, 'show'])->middleware('api.access:sales:read,manage POS,manage sales,manage reports');
            });
        });
    });
});
