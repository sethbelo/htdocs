<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Rattache l'utilisateur à un siège et lui attribue un rôle.
     */
    public function withRole(string $role, ?Site $site = null): static
    {
        return $this->afterCreating(function (User $user) use ($role, $site): void {
            $user->assignRole($role);

            if ($site) {
                $user->sites()->syncWithoutDetaching([$site->id]);
                $user->update(['default_site_id' => $site->id]);
            }
        });
    }

    public function withPin(string $pin = '1234'): static
    {
        return $this->state(fn (): array => ['pin_hash' => Hash::make($pin)]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
