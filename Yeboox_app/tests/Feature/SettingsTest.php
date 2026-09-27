<?php

namespace Tests\Feature;

use App\Enums\EstablishmentType;
use App\Livewire\Admin\Settings\Index;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dismissible_alert_exposes_an_accessible_close_button(): void
    {
        $this->blade('<x-dismissible-alert variant="success">Enregistré</x-dismissible-alert>')
            ->assertSee('Enregistré')
            ->assertSee('Fermer l’alerte')
            ->assertSee('visible = false');
    }

    public function test_admin_can_configure_currencies_ticket_and_loyalty(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $cdf = Currency::where('code', 'CDF')->firstOrFail();
        $usd = Currency::where('code', 'USD')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('default_currency_id', $cdf->id)
            ->set('enabled_currencies', ['CDF', 'USD'])
            ->set('usd_cdf_rate', 2900)
            ->call('saveCurrencies')
            ->assertHasNoErrors()
            ->set('paper_size', '58')
            ->set('ticket_font', 'Helvetica')
            ->set('ticket_font_size', 10)
            ->set('ticket_color', '#111827')
            ->set('ticket_template', 'elegant')
            ->set('ticket_code_type', 'barcode')
            ->set('ticket_logo_width', 22)
            ->set('ticket_header_text', 'FACTURE CLIENT')
            ->set('ticket_footer_text', 'Merci et à bientôt')
            ->call('saveTicket')
            ->assertHasNoErrors()
            ->set('loyalty_unit_amount', 2000)
            ->set('loyalty_points_per_unit', 3)
            ->set('loyalty_point_value', 20)
            ->call('saveLoyalty')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exchange_rates', [
            'establishment_id' => $admin->establishment_id,
            'from_currency_id' => $usd->id,
            'to_currency_id' => $cdf->id,
            'sell_rate' => 2900,
        ]);
        $this->assertSame('58', Setting::where('key', 'paper_size')->firstOrFail()->value);
        $this->assertSame('elegant', Setting::where('key', 'ticket_template')->firstOrFail()->value);
        $this->assertSame('barcode', Setting::where('key', 'ticket_code_type')->firstOrFail()->value);
        $this->assertSame('2000', Setting::where('key', 'loyalty_unit_amount')->firstOrFail()->value);
    }

    public function test_restaurant_or_bar_admin_can_manually_enable_the_kitchen_module(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('type', EstablishmentType::Restaurant->value)
            ->set('kitchen_enabled', true)
            ->call('saveCompany')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'establishment_id' => $admin->establishment_id,
            'group' => 'modules',
            'key' => 'kitchen_enabled',
            'value' => '1',
            'type' => 'boolean',
        ]);
        $this->assertTrue($admin->establishment->fresh()->kitchenModuleEnabled());
        $this->get(route('kitchen.display'))->assertOk();

        $admin->establishment->update(['type' => EstablishmentType::Bar]);
        $this->assertTrue($admin->establishment->fresh()->kitchenModuleEnabled());
        $this->get(route('kitchen.display'))->assertOk();
    }

    public function test_kitchen_module_is_blocked_when_disabled_or_store_type_is_not_eligible(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('kitchen.display'))
            ->assertForbidden();

        Livewire::test(Index::class)
            ->set('type', EstablishmentType::Pharmacy->value)
            ->set('kitchen_enabled', true)
            ->call('saveCompany')
            ->assertHasNoErrors()
            ->assertSet('kitchen_enabled', false);

        $this->assertSame('0', Setting::where('key', 'kitchen_enabled')->firstOrFail()->value);
        $this->assertFalse($admin->establishment->fresh()->kitchenModuleEnabled());
        $this->get(route('kitchen.display'))->assertForbidden();
    }

    public function test_admin_can_manually_enable_and_disable_reservations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        $this->get(route('admin.reservations.index'))->assertForbidden();

        Livewire::test(Index::class)
            ->set('reservations_enabled', true)
            ->call('saveModules')
            ->assertHasNoErrors();

        $this->assertTrue($admin->establishment->fresh()->reservationsModuleEnabled());
        $this->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('Planning des réservations');

        Livewire::test(\App\Livewire\Admin\Reservations\Index::class)
            ->assertSet('activeTab', 'planning')
            ->call('selectTab', 'list')
            ->assertSet('activeTab', 'list')
            ->call('goToCurrentMonth')
            ->assertSet('calendarMonth', now()->format('Y-m'));

        Livewire::test(Index::class)
            ->set('reservations_enabled', false)
            ->call('saveModules')
            ->assertHasNoErrors();

        $this->assertFalse($admin->establishment->fresh()->reservationsModuleEnabled());
        $this->get(route('admin.reservations.index'))->assertForbidden();
    }
}
