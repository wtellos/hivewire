<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
            'title' => fake()->sentence(4),
            'status' => fake()->randomElement(['backlog', 'to_do', 'in_progress', 'in_review', 'done']),
            'progress' => fake()->numberBetween(0, 100),
            'project_id' => Project::factory(),
            'creator_id' => User::factory(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'description' => fake()->paragraph(),
        ];
    }
}
