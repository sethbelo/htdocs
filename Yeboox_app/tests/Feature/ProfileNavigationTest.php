<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_navigation_supports_a_user_without_a_current_team(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Aucune équipe')
            ->assertSee('Aucune équipe n’est actuellement sélectionnée.');
    }
}
