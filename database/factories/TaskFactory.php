<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
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
            'uuid'          => fake()->uuid(),
            'project_id'    => Project::factory(),
            'order'         => fake()->numberBetween(1, 10),
            'name'          => fake()->sentence(3),
            'description'   => fake()->paragraph(),
        ];
    }
}
