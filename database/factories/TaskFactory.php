<?php

namespace Database\Factories;

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
            'project_id'    => null, // This should be set when creating a task for a specific project
            'order'         => fake()->numberBetween(1, 10),
            'name'          => fake()->sentence(3),
            'description'   => fake()->paragraph(),
        ];
    }
}
