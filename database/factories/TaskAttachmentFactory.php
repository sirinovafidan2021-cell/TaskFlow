<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;

/** @extends Factory<TaskAttachment> */
class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'testing/'.Str::uuid().'.txt',
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(1, 10_240),
        ];
    }
}
