<?php

namespace Modules\Tasks\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Projects\Models\Project;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'number' => 'TSK-'.fake()->unique()->numerify('######'),
            'project_id' => Project::factory(),
            'creator_id' => User::factory(),
            'assignee_id' => User::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'due_at' => fake()->optional()->dateTimeBetween('+1 day', '+3 months'),
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function unassigned(): static
    {
        return $this->state(fn (): array => ['assignee_id' => null]);
    }
}
