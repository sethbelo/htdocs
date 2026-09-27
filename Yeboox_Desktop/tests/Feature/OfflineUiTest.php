<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layouts_do_not_load_remote_fonts_or_scripts(): void
    {
        foreach (['app', 'guest', 'pos'] as $layout) {
            $source = file_get_contents(resource_path("views/layouts/{$layout}.blade.php"));

            $this->assertStringNotContainsString('fonts.bunny.net', $source);
            $this->assertDoesNotMatchRegularExpression('/<(script|link)[^>]+https?:\/\//i', $source);
        }

        $this->assertFileExists(public_path('build/manifest.json'));
        $this->assertFileExists(base_path('vendor/livewire/livewire/dist/livewire.min.js'));
        $this->assertFileExists(base_path('vendor/livewire/flux/dist/flux.min.js'));
    }

    public function test_legacy_currency_preference_route_keeps_the_configured_base_currency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('preferences.currency'), ['currency' => 'USD'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('ui.currency', 'CDF')
            ->assertSessionHas('pos.currency', 'CDF');
    }

    public function test_legacy_currency_preference_cannot_override_the_base_currency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('preferences.currency'), ['currency' => 'EUR'])
            ->assertSessionHas('ui.currency', 'CDF')
            ->assertSessionHas('pos.currency', 'CDF');
    }
}
