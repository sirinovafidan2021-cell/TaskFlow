<?php

use App\Enums\UserRole;
use App\Models\User;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Tasks\Models\Task;

it('requires authentication for task APIs', function (): void {
    $this->getJson('/api/v1/tasks')->assertUnauthorized();
});

it('creates, retrieves, updates, and deletes a task using its real project relationship', function (): void {
    $manager = userWithRole(UserRole::ProjectManager);
    $assignee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $manager->id, 'status' => ProjectStatus::Active]);
    ProjectMember::factory()->create(['project_id' => $project->id, 'user_id' => $assignee->id]);
    $token = $manager->createToken('pest')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/tasks', [
        'project_id' => $project->id, 'title' => 'Prepare release', 'description' => 'Prepare checklist',
        'assignee_id' => $assignee->id, 'priority' => 'high', 'due_at' => '2026-10-10',
    ])->assertCreated()->assertJsonPath('data.project_id', $project->id)->assertJsonPath('data.assignee_id', $assignee->id)
        ->assertJsonPath('data.status', 'todo');

    $task = Task::query()->where('title', 'Prepare release')->firstOrFail();
    expect($task->number)->toStartWith('TSK-');
    $this->withToken($token)->getJson('/api/v1/tasks')->assertOk()->assertJsonPath('success', true);
    $this->withToken($token)->getJson("/api/v1/tasks/{$task->id}")->assertOk()->assertJsonPath('data.project.id', $project->id);
    $this->withToken($token)->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Release checklist', 'description' => 'Updated', 'priority' => 'urgent'])
        ->assertOk()->assertJsonPath('data.priority', 'urgent');
    $this->withToken($token)->deleteJson("/api/v1/tasks/{$task->id}")->assertOk();
    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

it('validates task input and denies a member without management permission', function (): void {
    $member = userWithRole(UserRole::Member);
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $token = $member->createToken('pest')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/tasks', ['project_id' => $project->id, 'title' => 'Denied task', 'priority' => 'medium'])
        ->assertForbidden();

    $manager = userWithRole(UserRole::ProjectManager);
    $this->withToken($manager->createToken('pest')->plainTextToken)->postJson('/api/v1/tasks', ['project_id' => $project->id, 'title' => 'x', 'priority' => 'invalid'])
        ->assertUnprocessable()->assertJsonValidationErrors(['title', 'priority']);
});
