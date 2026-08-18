<?php

namespace Modules\Tasks\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;

/** @extends Factory<TaskAttachment> */
class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        $filename = fake()->unique()->lexify('attachment-??????').'.pdf';

        return [
            'task_id' => Task::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'task-attachments/'.$filename,
            'original_name' => $filename,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1_024, 5_242_880),
        ];
    }
}
