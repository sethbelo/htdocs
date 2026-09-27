<?php

namespace Tests\Feature;

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Reservations\Index as ReservationsIndex;
use App\Livewire\Admin\Tables\Index as TablesIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_use_the_new_crud_workflows(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        Setting::create([
            'establishment_id' => $admin->establishment_id,
            'group' => 'modules',
            'key' => 'reservations_enabled',
            'value' => '1',
            'type' => 'boolean',
        ]);
        $this->actingAs($admin);

        $product = Product::firstOrFail();
        Livewire::test(ProductsIndex::class)
            ->call('toggleStatus', $product->id)
            ->assertHasNoErrors();
        $this->assertNotSame($product->is_active, $product->fresh()->is_active);

        Livewire::test(TablesIndex::class)
            ->call('openForm')
            ->set('name', 'Table responsive')
            ->set('number', 'RESP-01')
            ->set('capacity', 6)
            ->set('shape', 'round')
            ->call('save')
            ->assertHasNoErrors();

        $table = Table::where('number', 'RESP-01')->firstOrFail();
        $this->assertSame(6, $table->capacity);

        Livewire::test(ReservationsIndex::class)
            ->call('openForm')
            ->set('guest_name', 'Client Test Responsive')
            ->set('guest_phone', '+243000000000')
            ->set('table_id', $table->id)
            ->set('reservation_date', now()->addDay()->format('Y-m-d'))
            ->set('reservation_time', '18:30')
            ->set('party_size', 4)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Reservation::where('guest_name', 'Client Test Responsive')->exists());

        Livewire::test(UsersIndex::class)
            ->call('openForm')
            ->set('name', 'Nouvel Opérateur')
            ->set('email', 'operateur@example.test')
            ->set('role', 'vendeur')
            ->set('status', 'active')
            ->set('password', 'MotDePasse@2026')
            ->call('save')
            ->assertHasNoErrors();

        $operator = User::where('email', 'operateur@example.test')->firstOrFail();
        $this->assertTrue($operator->hasRole('vendeur'));
    }
}
