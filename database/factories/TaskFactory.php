<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'status' => 'to_do',
            'priority' => 'medium',
            'weight' => 1,
            'start_date' => now()->startOfDay(),
            'due_date' => now()->addWeek()->startOfDay(),
            'created_by' => User::factory(),
            'dependency_type' => 'FS',
        ];
    }
}
