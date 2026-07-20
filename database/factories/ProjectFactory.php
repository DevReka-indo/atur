<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addMonth()->startOfDay(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
