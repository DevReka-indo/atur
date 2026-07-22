<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $rolesTable = config('permission.table_names.roles', 'roles');

            if (Schema::hasTable($rolesTable)
                && Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'member',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
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

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => 'super_admin']);
    }

    public function contributor(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => 'contributor']);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => 'member']);
    }
}
