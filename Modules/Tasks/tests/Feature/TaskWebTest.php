<?php

use App\Enums\UserRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;

it('keeps task web pages behind authentication and policies', function (): void {
    $this->get('/tasks')->assertRedirect('/login');

    $manager = userWithRole(UserRole::ProjectManager);
    $project = Project::factory()->create(['owner_id' => $manager->id, 'status' => ProjectStatus::Active]);
    $task = Task::factory()->create(['project_id' => $project->id, 'creator_id' => $manager->id]);
    $this->actingAs($manager)->get('/tasks')->assertOk()->assertViewIs('tasks::index');
    $this->actingAs($manager)->get("/tasks/{$task->id}")->assertOk()->assertViewIs('tasks::show');
    $this->actingAs($manager)->post("/projects/{$project->id}/tasks", ['title' => 'Web task', 'priority' => 'medium'])
        ->assertRedirect();
    $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'title' => 'Web task']);
});
