<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_token_issuance_and_order_endpoints(): void
    {
        $user = User::factory()->withRole('commercial', $this->headquarters)->create(['username' => 'api.user']);

        $token = $this->postJson(route('api.v1.auth.token'), ['login' => 'api.user', 'password' => 'password', 'device_name' => 'tablette'])
            ->assertOk()->json('token');

        $client = Client::factory()->create();
        $number = $this->withToken($token)->postJson(route('api.v1.orders.store'), [
            'client_id' => $client->id,
            'site_id' => $this->headquarters->id,
            'items' => [['description' => 'Affiches A3', 'quantity' => 20, 'unit_price' => 2.5, 'format' => 'A3']],
        ])->assertCreated()->assertJsonPath('data.totals.total', 50)->json('data.number');

        $this->withToken($token)->getJson(route('api.v1.orders.show', $number))
            ->assertOk()->assertJsonPath('data.status.code', 'awaiting_payment')->assertJsonPath('data.client.id', $client->id);

        $this->postJson(route('api.v1.auth.token'), ['login' => 'api.user', 'password' => 'nope', 'device_name' => 'x'])->assertUnprocessable();
    }

    public function test_payment_webhook_checks_signature_and_is_idempotent(): void
    {
        config(['services.payment_webhooks.mobile_money.secret' => 'shh']);
        $payment = Payment::query()->create([
            'number' => 'PAY-T-1', 'batch' => (string) Str::uuid(), 'payable_type' => 'client', 'payable_id' => Client::factory()->create()->id,
            'method' => 'mobile_money', 'amount' => 25, 'reference' => 'MM-42', 'paid_at' => now(),
        ]);

        $body = ['event_id' => 'evt_1', 'type' => 'payment.succeeded', 'reference' => 'MM-42', 'amount' => 25, 'status' => 'success'];
        $signature = hash_hmac('sha256', json_encode($body), 'shh');

        $this->postJson(route('webhooks.payments', 'mobile_money'), $body, ['X-Signature' => 'bad'])->assertUnauthorized();
        $this->postJson(route('webhooks.payments', 'mobile_money'), $body, ['X-Signature' => $signature])->assertOk()->assertJsonPath('status', 'confirmed');
        $this->postJson(route('webhooks.payments', 'mobile_money'), $body, ['X-Signature' => $signature])->assertOk()->assertJsonPath('status', 'already_processed');

        $this->assertTrue($payment->fresh()->meta['amount_matches']);
    }
}
