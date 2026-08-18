<?php

use App\Enums\UserRole;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Models\Project;

it('records project activity and returns it to an authorized administrator', function (): void {
    $admin = userWithRole(UserRole::Admin);
    $project = Project::factory()->create(['owner_id' => $admin->id]);
    app(ActivityRecorder::class)->record('project.created', $admin, $project, ['project_id' => $project->id]);

    $this->withToken($admin->createToken('pest')->plainTextToken)->getJson('/api/v1/activities')
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.0.event', 'project.created')
        ->assertJsonPath('data.0.causer.id', $admin->id)->assertJsonPath('data.0.subject_id', $project->id);
});

it('denies activity APIs to unauthenticated and unauthorized users', function (): void {
    $this->getJson('/api/v1/activities')->assertUnauthorized();
    $member = userWithRole(UserRole::Member);
    $this->withToken($member->createToken('pest')->plainTextToken)->getJson('/api/v1/activities')->assertForbidden();
});
