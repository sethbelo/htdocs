<?php

namespace App\Services\Sync;

use App\Models\AccountingRecord;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\PosSyncCredential;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\SyncReceipt;
use App\Models\User;
use App\Support\DisplayCurrency;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Applique un événement de vente une seule fois dans la base centrale. */
final class CentralSaleIngestor
{
    public function ingest(string $eventUuid, array $payload, string $tokenHash): array
    {
        $credential = PosSyncCredential::query()
            ->with(['company', 'pointOfSale.establishment'])
            ->where('token_hash', $tokenHash)
            ->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->where('uuid', $payload['company_uuid']))
            ->whereHas('pointOfSale', fn ($query) => $query->where('uuid', $payload['point_of_sale_uuid']))
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'terminal' => 'La clé fournie ne correspond pas à ce point de vente.',
            ]);
        }

        $checksum = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $company = $credential->company;
        $point = $credential->pointOfSale;

        $result = app(TenantContext::class)->run(
            $company,
            $point->establishment,
            $point,
            fn () => DB::transaction(function () use ($eventUuid, $payload, $checksum, $credential, $company, $point): array {
                // PostgreSQL sérialise deux reprises simultanées du même
                // événement sans verrouiller les autres terminaux.
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', [$eventUuid]);
                }

                $receipt = SyncReceipt::query()->where('event_uuid', $eventUuid)->lockForUpdate()->first();
                if ($receipt) {
                    if (! hash_equals($receipt->payload_checksum, $checksum)) {
                        throw ValidationException::withMessages([
                            'event_uuid' => 'Cet identifiant a déjà été reçu avec un contenu différent.',
                        ]);
                    }

                    return $receipt->response ?: [
                        'status' => 'already_synced',
                        'event_uuid' => $eventUuid,
                        'sale_id' => $receipt->sale_id,
                    ];
                }

                $user = $this->resolveUser($payload['user'] ?? [], $company->id, $point);
                $customer = $this->resolveCustomer($payload['customer'] ?? null);
                $session = $this->resolveSession($payload['register_session'] ?? null, $user);
                $saleData = $payload['sale'];

                $sale = Sale::query()->where('sync_uuid', $saleData['sync_uuid'])->first();
                if (! $sale) {
                    $sale = Sale::create([
                        'sync_uuid' => $saleData['sync_uuid'],
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'client_reference' => $saleData['client_reference'] ?? null,
                        'source' => 'desktop-offline',
                        'customer_id' => $customer?->id,
                        'user_id' => $user->id,
                        'register_session_id' => $session?->id,
                        'department' => $saleData['department'] ?? 'pos',
                        'fulfillment_type' => $saleData['fulfillment_type'] ?? 'takeaway',
                        'table_name' => $saleData['table_name'] ?? null,
                        'delivery_address' => $saleData['delivery_address'] ?? null,
                        'delivery_phone' => $saleData['delivery_phone'] ?? null,
                        'payment_method' => $saleData['payment_method'] ?? 'cash',
                        'payment_reference' => $saleData['payment_reference'] ?? null,
                        'total_amount' => $saleData['total_amount'],
                        'discount_amount' => $saleData['discount_amount'] ?? 0,
                        'tax_amount' => $saleData['tax_amount'] ?? 0,
                        'paid_amount' => $saleData['paid_amount'],
                        'points_earned' => $saleData['points_earned'] ?? 0,
                        'points_redeemed' => $saleData['points_redeemed'] ?? 0,
                    ]);
                    $sale->forceFill([
                        'created_at' => $payload['occurred_at'],
                        'updated_at' => $payload['occurred_at'],
                    ])->saveQuietly();

                    foreach ($payload['items'] as $line) {
                        $product = $this->resolveProduct($line['product'], $line);
                        $sale->items()->create([
                            'product_id' => $product->id,
                            'quantity' => $line['quantity'],
                            'unit_price' => $line['unit_price'],
                            'sales_tax_rate' => $line['sales_tax_rate'] ?? 0,
                            'total_price' => $line['total_price'],
                            'tax_amount' => $line['tax_amount'] ?? 0,
                        ]);

                        if ($product->type === 'product') {
                            $before = (float) $product->stock_qty;
                            $after = $before - (float) $line['quantity'];
                            $product->update(['stock_qty' => $after]);
                            StockMovement::create([
                                'product_id' => $product->id,
                                'type' => 'out',
                                'reason' => 'offline_sync',
                                'reference' => 'SYNC-'.Str::upper(Str::substr($eventUuid, 0, 8)),
                                'quantity' => $line['quantity'],
                                'unit_cost' => $product->cost,
                                'stock_before' => $before,
                                'stock_after' => $after,
                                'description' => 'Vente reçue depuis un terminal hors ligne',
                                'user_id' => $user->id,
                            ]);
                        }
                    }

                    foreach ($payload['payments'] ?? [] as $payment) {
                        $method = PaymentMethod::query()->firstOrCreate(
                            ['code' => $payment['method']],
                            ['name' => Str::headline($payment['method']), 'is_active' => true],
                        );
                        SalePayment::create([
                            'sale_id' => $sale->id,
                            'payment_method_id' => $method->id,
                            'method' => $payment['method'],
                            'provider' => $payment['provider'] ?? null,
                            'reference' => $payment['reference'] ?? null,
                            'amount' => $payment['amount'],
                            'currency' => $payment['currency'] ?? 'USD',
                            'points_redeemed' => $payment['points_redeemed'] ?? 0,
                            'metadata' => $payment['metadata'] ?? null,
                        ]);
                    }

                    foreach ($payload['accounting_records'] ?? [] as $record) {
                        AccountingRecord::create([
                            ...$record,
                            'user_id' => $user->id,
                            'sale_id' => $sale->id,
                            'source_type' => $sale->getMorphClass(),
                            'source_id' => $sale->id,
                        ]);
                    }

                    if ($customer) {
                        $customer->update([
                            'points' => max(0, (int) $customer->points - (int) ($saleData['points_redeemed'] ?? 0) + (int) ($saleData['points_earned'] ?? 0)),
                        ]);
                    }
                }

                $response = ['status' => 'synced', 'event_uuid' => $eventUuid, 'sale_id' => $sale->id];
                SyncReceipt::create([
                    'event_uuid' => $eventUuid,
                    'company_id' => $company->id,
                    'point_of_sale_id' => $point->id,
                    'sale_id' => $sale->id,
                    'payload_checksum' => $checksum,
                    'response' => $response,
                    'received_at' => now(),
                ]);
                $credential->forceFill(['last_used_at' => now()])->save();

                return $response;
            }, 3),
        );

        return $result;
    }

    private function resolveUser(array $data, int $companyId, PointOfSale $point): User
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? 'terminal@'.$point->uuid.'.local')));
        $user = User::query()->where('company_id', $companyId)->where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $user = User::create([
            'company_id' => $companyId,
            'current_establishment_id' => $point->establishment_id,
            'current_point_of_sale_id' => $point->id,
            'name' => $data['name'] ?? 'Terminal hors ligne',
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'is_active' => true,
        ]);
        $user->pointsOfSale()->syncWithoutDetaching([$point->id => ['access_level' => 'operator']]);

        return $user;
    }

    private function resolveCustomer(?array $data): ?Customer
    {
        if (! $data) {
            return null;
        }

        $query = Customer::query();
        if (! empty($data['email'])) {
            $query->where('email', $data['email']);
        } elseif (! empty($data['phone'])) {
            $query->where('phone', $data['phone']);
        } else {
            return null;
        }

        return $query->firstOrCreate([], [
            'name' => $data['name'] ?: 'Client',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'points' => 0,
            'is_active' => true,
        ]);
    }

    private function resolveSession(?array $data, User $user): ?RegisterSession
    {
        if (! $data || empty($data['sync_uuid'])) {
            return null;
        }

        return RegisterSession::query()->updateOrCreate(
            ['sync_uuid' => $data['sync_uuid']],
            [
                'user_id' => $user->id,
                'opened_at' => $data['opened_at'] ?? now(),
                'closed_at' => $data['closed_at'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'opening_balance_usd' => 0,
                'opening_balance_cdf' => 0,
                'exchange_rate' => max(0.00000001, (float) ($data['base_to_secondary_rate'] ?? $data['exchange_rate'] ?? 1)),
                'base_currency' => $data['base_currency'] ?? $data['currency'] ?? DisplayCurrency::baseCode(),
                'secondary_currency' => $data['secondary_currency'] ?? DisplayCurrency::secondaryCode(),
                'base_to_secondary_rate' => max(0.00000001, (float) ($data['base_to_secondary_rate'] ?? $data['exchange_rate'] ?? DisplayCurrency::rate())),
                'status' => $data['status'] ?? 'closed',
            ],
        );
    }

    private function resolveProduct(array $data, array $line): Product
    {
        $sku = trim((string) ($data['sku'] ?? '')) ?: 'SYNC-'.Str::upper(Str::random(12));

        return Product::query()->firstOrCreate(
            ['sku' => $sku],
            [
                'name' => $data['name'] ?? 'Article synchronisé',
                'category' => $data['category'] ?? 'Général',
                'type' => $data['type'] ?? 'product',
                'price' => $line['unit_price'] ?? 0,
                'sales_tax_rate' => $line['sales_tax_rate'] ?? 0,
                'tax_included' => $data['tax_included'] ?? false,
                'cost' => $data['cost'] ?? 0,
                'stock_qty' => 0,
                'alert_qty' => 0,
                'is_active' => true,
            ],
        );
    }
}
