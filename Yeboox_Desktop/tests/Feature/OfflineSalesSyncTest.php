<?php

namespace Tests\Feature;

use App\Models\PosSyncCredential;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SyncReceipt;
use App\Services\SaasProvisioner;
use App\Services\Sync\OfflineSalePublisher;
use App\Services\Sync\SaleOutboxSynchronizer;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfflineSalesSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sale_is_persisted_in_the_outbox_and_marked_synced_after_delivery(): void
    {
        [$sale, $event] = $this->localSale();
        config(['offline-sync.remote_url' => 'https://central.example', 'offline-sync.token' => 'test-token']);
        Http::fake(['central.example/*' => Http::response(['data' => ['status' => 'synced']], 200)]);

        $stats = app(SaleOutboxSynchronizer::class)->syncPending();

        $this->assertSame(1, $stats['synced']);
        $this->assertSame('synced', $event->fresh()->status);
        $this->assertSame('synced', $sale->fresh()->sync_status);
        Http::assertSentCount(1);
    }

    public function test_the_central_endpoint_authenticates_the_pos_and_is_idempotent(): void
    {
        [$sale, $event, $tenant] = $this->localSale(true);
        $token = 'terminal-secret-for-tests';
        PosSyncCredential::create([
            'company_id' => $tenant['company']->id,
            'point_of_sale_id' => $tenant['point_of_sale']->id,
            'name' => 'Terminal test',
            'token_hash' => hash('sha256', $token),
            'token_hint' => 'tests',
            'is_active' => true,
        ]);

        $request = [
            'event_uuid' => $event->event_uuid,
            'event_type' => 'sale.completed',
            'payload' => $event->payload,
        ];
        // Simule une base centrale qui ne possède pas encore cette vente.
        $sale->delete();

        $this->postJson('/api/v2/sync/sales', $request, ['X-Yeboox-Sync-Token' => $token])->assertOk();
        $this->postJson('/api/v2/sync/sales', $request, ['X-Yeboox-Sync-Token' => $token])->assertOk();

        $this->assertSame(1, SyncReceipt::query()->where('event_uuid', $event->event_uuid)->count());
        $this->assertSame(1, Sale::query()->where('sync_uuid', $sale->sync_uuid)->count());
        $this->assertDatabaseHas('sale_items', ['quantity' => 1, 'total_price' => 10]);
        $this->postJson('/api/v2/sync/sales', $request, ['X-Yeboox-Sync-Token' => 'incorrect'])->assertUnauthorized();
    }

    public function test_a_network_failure_keeps_the_sale_and_schedules_a_retry(): void
    {
        [$sale, $event] = $this->localSale();
        config(['offline-sync.remote_url' => 'https://central.example', 'offline-sync.token' => 'test-token']);
        Http::fake(fn () => Http::failedConnection('Internet indisponible'));

        $stats = app(SaleOutboxSynchronizer::class)->syncPending();

        $this->assertSame(1, $stats['failed']);
        $this->assertSame('retry', $event->fresh()->status);
        $this->assertSame('pending', $sale->fresh()->sync_status);
        $this->assertNotNull($event->fresh()->available_at);
    }

    public function test_a_sale_written_on_the_cloud_primary_database_is_immediately_central(): void
    {
        config(['offline-sync.runtime' => 'cloud', 'offline-sync.enabled' => false]);
        $tenant = app(SaasProvisioner::class)->createCompany([
            'name' => 'Entreprise PostgreSQL',
            'administrator_name' => 'Administrateur PostgreSQL',
            'administrator_email' => 'postgres@example.test',
            'administrator_password' => 'MotDePasse-2026',
        ]);

        $sale = app(TenantContext::class)->run(
            $tenant['company'],
            $tenant['establishment'],
            $tenant['point_of_sale'],
            fn () => Sale::query()->create([
                'user_id' => $tenant['administrator']->id,
                'department' => 'pos',
                'fulfillment_type' => 'takeaway',
                'payment_method' => 'cash',
                'total_amount' => 5,
                'paid_amount' => 5,
            ]),
        );

        $this->assertSame('synced', $sale->sync_status);
        $this->assertNotNull($sale->synced_at);
        $this->assertNotNull($sale->sync_uuid);
        $this->assertDatabaseCount('sync_outbox', 0);
    }

    /** @return array<int, mixed> */
    private function localSale(bool $withTenant = false): array
    {
        config(['offline-sync.enabled' => true, 'offline-sync.runtime' => 'desktop']);
        $tenant = app(SaasProvisioner::class)->createCompany([
            'name' => 'Entreprise Sync',
            'administrator_name' => 'Administrateur Sync',
            'administrator_email' => 'sync@example.test',
            'administrator_password' => 'MotDePasse-2026',
        ]);

        [$sale, $event] = app(TenantContext::class)->run(
            $tenant['company'],
            $tenant['establishment'],
            $tenant['point_of_sale'],
            function () use ($tenant): array {
                $product = Product::query()->create([
                    'name' => 'Produit hors ligne',
                    'sku' => 'SYNC-TEST-001',
                    'category' => 'Tests',
                    'type' => 'product',
                    'price' => 10,
                    'cost' => 4,
                    'stock_qty' => 10,
                    'alert_qty' => 1,
                    'is_active' => true,
                ]);
                $session = RegisterSession::query()->create([
                    'user_id' => $tenant['administrator']->id,
                    'opened_at' => now(),
                    'opening_balance' => 0,
                    'status' => 'open',
                ]);
                $sale = Sale::query()->create([
                    'user_id' => $tenant['administrator']->id,
                    'register_session_id' => $session->id,
                    'department' => 'pos',
                    'fulfillment_type' => 'takeaway',
                    'payment_method' => 'cash',
                    'total_amount' => 10,
                    'paid_amount' => 10,
                ]);
                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 10,
                    'total_price' => 10,
                ]);

                return [$sale, app(OfflineSalePublisher::class)->publish($sale)];
            },
        );

        return $withTenant ? [$sale, $event, $tenant] : [$sale, $event];
    }
}
