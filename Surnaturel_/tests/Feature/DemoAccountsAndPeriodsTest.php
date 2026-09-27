<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Livewire\Auth\Login;
use App\Services\Accounting\FinancialStatements;
use App\Services\Cash\PaymentData;
use App\Services\Pos\CartLine;
use App\Services\Pos\PosCheckoutService;
use App\Support\DemoAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class DemoAccountsAndPeriodsTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_demo_accounts_cover_every_role_and_prefill_the_login_form(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $roles = collect(DemoAccounts::all())->pluck('role')->unique();
        $this->assertEqualsCanonicalizing(array_keys(config('erp.roles')), $roles->all());

        Livewire::test(Login::class)->call('useDemoAccount', 'magasinier')
            ->assertSet('identifier', 'magasinier')->assertSet('password', DemoAccounts::PASSWORD);

        Livewire::test(Login::class)->call('useDemoAccount', 'comptable')
            ->assertSet('identifier', 'comptable')->assertSet('password', DemoAccounts::PASSWORD);

        $this->get(route('login'))->assertOk()->assertSee('Comptes de démonstration');
    }

    public function test_demo_accounts_are_hidden_outside_local(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get(route('login'))->assertOk()->assertDontSee('Comptes de démonstration')->assertSee('Impression offset');
        Livewire::test(Login::class)->call('useDemoAccount', 'comptable')->assertNotFound();
    }

    public function test_period_reports_include_entries_of_the_last_day(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 5);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01');
        app(PosCheckoutService::class)->checkout([new CartLine($product->id, 1)], [new PaymentData(PaymentMethod::Cash, 10)], $session, $cashier);

        $today = today()->toDateString();
        $balance = app(FinancialStatements::class)->trialBalance($today, $today);

        $this->assertNotEmpty($balance);
        $this->assertEquals($balance->sum('debit'), $balance->sum('credit'));
    }
}
