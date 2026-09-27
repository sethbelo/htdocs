<?php

namespace Tests\Feature;

use App\Livewire\Admin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisplaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_apply_a_pos_display_profile(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));

        Livewire::actingAs($administrator)
            ->test(Admin::class)
            ->set('activeTab', 'display')
            ->assertSeeText('Résolution de travail')
            ->assertSeeText('1920 × 1080')
            ->set('displayResolution', '1366x768')
            ->set('displayScale', 90)
            ->set('displayDensity', 'compact')
            ->call('saveDisplaySettings')
            ->assertHasNoErrors()
            ->assertDispatched('display-settings-updated');

        $this->assertSame('1366x768', Setting::get('display_resolution'));
        $this->assertSame('1366', Setting::get('display_width'));
        $this->assertSame('768', Setting::get('display_height'));
        $this->assertSame('90', Setting::get('display_scale'));
        $this->assertSame('compact', Setting::get('display_density'));
    }

    public function test_custom_display_dimensions_are_validated(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));

        Livewire::actingAs($administrator)
            ->test(Admin::class)
            ->set('displayResolution', 'custom')
            ->set('displayWidth', 600)
            ->set('displayHeight', 400)
            ->call('saveDisplaySettings')
            ->assertHasErrors(['displayWidth', 'displayHeight']);
    }

    public function test_administrator_can_choose_the_default_product_tax_mode(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));

        Livewire::actingAs($administrator)
            ->test(Admin::class)
            ->assertSet('default_tax_included', true)
            ->set('default_tax_included', false)
            ->call('saveCompany')
            ->assertHasNoErrors();

        $this->assertSame('0', Setting::get('default_tax_included'));
    }
}
