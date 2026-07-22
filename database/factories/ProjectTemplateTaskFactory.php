<?php

namespace Database\Factories;

use App\Models\ProjectTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTemplateTask>
 */
class ProjectTemplateTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_template_id' => ProjectTemplate::factory(),
            'name' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'priority' => 'medium',
            'weight' => fake()->randomFloat(2, 1, 100),
            'position' => 0,
            'start_offset_days' => 0,
            'duration_days' => 1,
        ];
    }
}
