<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Pos\Terminal;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_closing_fields_from_an_outdated_livewire_snapshot(): void
    {
        $terminal = new Terminal;

        unset($terminal->closingCdfAmount, $terminal->closingUsdAmount, $terminal->closingNote);

        $terminal->hydrate();

        $this->assertSame(0.0, $terminal->closingCdfAmount);
        $this->assertSame(0.0, $terminal->closingUsdAmount);
        $this->assertSame('', $terminal->closingNote);
    }

    public function test_cashier_must_close_the_session_before_leaving_and_can_reopen_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $register = CashRegister::where('establishment_id', $admin->establishment_id)->firstOrFail();
        $this->actingAs($admin);

        $component = Livewire::test(Terminal::class)
            ->assertSet('showOpenSession', false);
        $expectedCash = (float) $component->get('session_expected_cash');

        $component
            ->call('requestExit', 'logout')
            ->assertSet('showCloseSession', true)
            ->assertSet('exitAfterClose', 'logout')
            ->set('closingCdfAmount', $expectedCash)
            ->set('closingUsdAmount', 0)
            ->call('closeSession')
            ->assertHasNoErrors()
            ->assertDispatched('submit-pos-logout');

        $closed = CashSession::where('user_id', $admin->id)->latest('closed_at')->firstOrFail();
        $this->assertSame('closed', $closed->status);
        $this->assertSame($expectedCash, (float) $closed->expected_amount);
        $this->assertSame($expectedCash, (float) $closed->cash_count['cdf']);

        $component
            ->set('selectedCashRegisterId', $register->id)
            ->set('openingAmount', 75000)
            ->set('openingNote', 'Relève équipe du soir')
            ->call('openSession')
            ->assertHasNoErrors()
            ->assertSet('showOpenSession', false);

        $this->assertDatabaseHas('cash_sessions', [
            'user_id' => $admin->id,
            'opening_amount' => 75000,
            'opening_note' => 'Relève équipe du soir',
            'status' => 'open',
        ]);
    }

    public function test_restaurant_can_enable_and_print_a_usd_proforma_without_payment(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(SettingsIndex::class)
            ->set('pos_provisional_receipt_enabled', true)
            ->set('pos_provisional_receipt_type', 'proforma')
            ->call('saveModules')
            ->assertHasNoErrors();

        $cart = [
            'product-1' => ['name' => 'Menu test', 'qty' => 2, 'price' => 28500, 'total' => 57000],
        ];

        $this->withSession(['pos_tables_carts' => [
            'default' => [
                'cart' => $cart,
                'discountAmount' => 0,
                'discountType' => 'fixed',
            ],
        ]])->get(route('pos.print-addition', [
            'tableKey' => 'default',
            'document' => 'proforma',
            'currency' => 'USD',
        ]))
            ->assertOk()
            ->assertSee('REÇU PROFORMA')
            ->assertSee('Document provisoire — non encaissé')
            ->assertSee('USD');
    }
}
