<?php

namespace Tests\Feature;

use App\Enums\StockOperationReason;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockOperationService;
use App\Services\Inventory\StockTransferService;
use App\Services\Reports\ReportBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ScreensRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn () => 'local');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_screen_renders_for_the_director_general(): void
    {
        $admin = User::query()->where('username', 'directeur.general')->firstOrFail();
        $this->actingAs($admin);

        $parameterless = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true))
            ->filter(fn ($route) => in_array('password.changed', $route->gatherMiddleware(), true))
            ->reject(fn ($route) => str_contains($route->uri(), '{') || $route->getName() === 'home')
            ->map(fn ($route) => $route->getName())
            ->filter();

        $this->assertGreaterThan(35, $parameterless->count());

        foreach ($parameterless as $name) {
            $response = $this->get(route($name));
            $this->assertSame(200, $response->status(), "L’écran « {$name} » ne s’affiche pas : ".$response->exception?->getMessage());
        }

        $this->get(route('home'))->assertRedirect(route('launcher'));

        $order = Order::query()->firstOrFail();
        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->number);
        $this->get(route('clients.show', Client::query()->firstOrFail()))->assertOk();

        foreach (['vue' => ['levels', 'movements']] as $param => $values) {
            foreach ($values as $value) {
                $this->get(route('inventory.index', [$param => $value]))->assertOk();
            }
        }

        foreach (['journal', 'ledger', 'balance', 'income', 'sheet', 'reconcile', 'chart'] as $tab) {
            $this->get(route('accounting.index', ['vue' => $tab, 'compte' => 1]))->assertOk();
        }

        foreach (array_keys(ReportBuilder::REPORTS) as $report) {
            $this->get(route('reports.index', ['rapport' => $report]))->assertOk();
        }

        foreach (['new', 'mine', 'urgent', 'in_progress', 'correction', 'proof', 'done', 'blocked'] as $view) {
            $this->get(route('design.board', ['vue' => $view]))->assertOk();
        }
    }

    public function test_every_printable_document_renders_as_pdf(): void
    {
        $this->actingAs(User::query()->where('username', 'directeur.general')->firstOrFail());

        $invoice = Invoice::query()->whereNotNull('order_id')->firstOrFail();
        $user = auth()->user();
        [$central, $shop] = [Warehouse::query()->where('code', 'ENT-CEN')->first(), Warehouse::query()->where('code', 'ENT-LIM')->first()];
        $product = Product::query()->where('sku', 'PAP-A4-80')->firstOrFail();
        $operation = app(StockOperationService::class)->record($central, StockOperationReason::InternalUse, ['performed_by_name' => 'Ruth Nsimba'], [['product_id' => $product->id, 'quantity' => 2]], $user);
        $transfer = app(StockTransferService::class)->transferNow($central, $shop, [['product_id' => $product->id, 'quantity' => 3]], $user, 'Coursier');
        $urls = [
            route('print.order-ticket', Order::query()->firstOrFail()),
            route('print.invoice', [$invoice, 'a4']),
            route('print.invoice', [$invoice, 'a5']),
            route('print.invoice', [$invoice, 'ticket']),
            route('print.sale-receipt', Sale::query()->firstOrFail()),
            route('print.loyalty-card', Client::query()->firstOrFail()),
            route('print.cash-session', CashSession::query()->firstOrFail()),
            route('print.stock-operation', $operation),
            route('print.stock-transfer', $transfer),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        }
    }
}
