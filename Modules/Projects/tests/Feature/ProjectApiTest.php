<?php

use App\Enums\UserRole;
use Modules\Projects\Models\Project;

it('requires a bearer token for project APIs', function (): void {
    $this->getJson('/api/v1/projects')->assertUnauthorized();
});

it('allows a project manager to create, view, update, and delete an owned project', function (): void {
    $manager = userWithRole(UserRole::ProjectManager);
    $token = $manager->createToken('pest')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/projects', [
        'name' => 'Launch plan', 'description' => 'Initial project scope',
        'starts_at' => '2026-09-01', 'due_at' => '2026-10-01',
    ])->assertCreated()->assertJsonPath('data.name', 'Launch plan');

    $project = Project::query()->where('name', 'Launch plan')->firstOrFail();
    $this->assertDatabaseHas('project_members', ['project_id' => $project->id, 'user_id' => $manager->id, 'member_role' => 'manager']);

    $this->withToken($token)->getJson('/api/v1/projects')->assertOk()->assertJsonPath('success', true);
    $this->withToken($token)->getJson("/api/v1/projects/{$project->id}")->assertOk()->assertJsonPath('data.owner.id', $manager->id);
    $this->withToken($token)->putJson("/api/v1/projects/{$project->id}", ['name' => 'Revised launch', 'description' => 'Revised scope'])
        ->assertOk()->assertJsonPath('data.name', 'Revised launch');
    $this->withToken($token)->deleteJson("/api/v1/projects/{$project->id}")->assertOk();
    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

it('validates project input and denies users without project permissions', function (): void {
    $member = userWithRole(UserRole::Member);
    $token = $member->createToken('pest')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/projects', ['name' => 'Denied project'])
        ->assertForbidden();

    $manager = userWithRole(UserRole::ProjectManager);
    $this->withToken($manager->createToken('pest')->plainTextToken)->postJson('/api/v1/projects', ['name' => 'x', 'due_at' => 'not-a-date'])
        ->assertUnprocessable()->assertJsonValidationErrors(['name', 'due_at']);
});
