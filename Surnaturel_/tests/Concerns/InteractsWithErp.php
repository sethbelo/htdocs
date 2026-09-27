<?php

namespace Tests\Concerns;

use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Cash\CashSessionService;
use App\Services\Inventory\StockService;
use App\Services\Sales\OrderService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

trait InteractsWithErp
{
    protected Site $headquarters;

    protected Site $shop;

    protected function setUpErp(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, ChartOfAccountsSeeder::class, ReferenceDataSeeder::class]);
        $this->headquarters = Site::query()->where('code', 'SIE-KIN')->firstOrFail();
        $this->shop = Site::query()->where('code', 'BTQ-LIM')->firstOrFail();
    }

    protected function userWithRole(string $role, ?Site $site = null): User
    {
        return User::factory()->withRole($role, $site ?? $this->headquarters)->withPin('1234')->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createOrder(?User $salesRep = null, float $unitPrice = 100, float $quantity = 1, array $overrides = []): Order
    {
        $salesRep ??= $this->userWithRole('commercial');

        return app(OrderService::class)->create($overrides + [
            'client_id' => Client::factory()->create(['site_id' => $this->headquarters->id])->id,
            'site_id' => $this->headquarters->id,
            'deposit_rate' => 50,
            'requires_design' => true,
        ], [[
            'description' => 'Flyers A5',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => 0,
            'discount' => 0,
            'format' => 'A5',
            'paper' => 'Couché brillant',
            'grammage' => 135,
        ]], $salesRep);
    }

    protected function openSession(User $cashier, string $registerCode = 'CS-SIE-01', float $opening = 50): CashSession
    {
        $this->actingAs($cashier);

        return app(CashSessionService::class)->open(CashRegister::query()->where('code', $registerCode)->firstOrFail(), $cashier, $opening, '1234');
    }

    protected function stockedProduct(Warehouse|string $warehouse, float $quantity = 10, array $attributes = []): Product
    {
        $warehouse = $warehouse instanceof Warehouse ? $warehouse : Warehouse::query()->where('code', $warehouse)->firstOrFail();
        $product = Product::factory()->create($attributes + ['sale_price' => 10, 'cost_price' => 4, 'tax_rate' => 0, 'min_stock' => 2]);

        app(StockService::class)->move($product, $warehouse, StockMovementType::In, $quantity, null, 'Stock initial');

        return $product;
    }
}
