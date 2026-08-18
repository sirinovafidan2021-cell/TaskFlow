<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;

it('returns dashboard statistics for a permitted user', function (): void {
    $manager = userWithRole(UserRole::ProjectManager);
    $project = Project::factory()->create(['owner_id' => $manager->id, 'status' => ProjectStatus::Active]);
    Task::factory()->create(['project_id' => $project->id, 'creator_id' => $manager->id, 'assignee_id' => $manager->id, 'status' => TaskStatus::Todo]);

    $this->withToken($manager->createToken('pest')->plainTextToken)->getJson('/api/v1/dashboard')
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.active_projects', 1)
        ->assertJsonPath('data.total_tasks', 1)->assertJsonPath('data.todo', 1)
        ->assertJsonStructure(['data' => ['my_tasks', 'recent_activity']]);
});

it('protects the dashboard API with Sanctum and dashboard permission', function (): void {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    app(RolePermissionSeeder::class)->run();
    $user = User::factory()->create();
    $this->withToken($user->createToken('pest')->plainTextToken)->getJson('/api/v1/dashboard')->assertForbidden();
});
