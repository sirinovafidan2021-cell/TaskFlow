<?php

use App\Enums\UserRole;
use App\Models\User;
use Modules\Projects\Models\Project;

it('manages project membership through the API and protects it by policy', function (): void {
    $manager = userWithRole(UserRole::ProjectManager);
    $project = Project::factory()->create(['owner_id' => $manager->id]);
    $member = User::factory()->create();
    $token = $manager->createToken('pest')->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/projects/{$project->id}/members", ['user_id' => $member->id, 'member_role' => 'member'])
        ->assertCreated()->assertJsonPath('data.user.id', $member->id);
    $this->withToken($token)->getJson("/api/v1/projects/{$project->id}/members")
        ->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->deleteJson("/api/v1/projects/{$project->id}/members/{$member->id}")->assertOk();
    $this->assertDatabaseMissing('project_members', ['project_id' => $project->id, 'user_id' => $member->id]);

    $outsider = userWithRole(UserRole::ProjectManager);
    $this->app['auth']->forgetGuards();
    $this->withToken($outsider->createToken('pest')->plainTextToken)->getJson("/api/v1/projects/{$project->id}/members")->assertForbidden();
});
