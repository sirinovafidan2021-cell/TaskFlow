<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Tests\TestCase;

final class ProjectListApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_owned_and_member_projects_but_not_foreign_projects(): void
    {
        $user = User::factory()->create();

        $ownedProject = Project::query()->create([
            'name' => 'Owned Project',
            'slug' => 'owned-project',
            'description' => null,
            'status' => 'active',
            'owner_id' => $user->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        $memberOwner = User::factory()->create();

        $memberProject = Project::query()->create([
            'name' => 'Member Project',
            'slug' => 'member-project',
            'description' => null,
            'status' => 'active',
            'owner_id' => $memberOwner->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        ProjectMember::query()->create([
            'project_id' => $memberProject->id,
            'user_id' => $user->id,
            'member_role' => 'member',
            'joined_at' => now(),
        ]);

        $foreignOwner = User::factory()->create();

        $foreignProject = Project::query()->create([
            'name' => 'Foreign Project',
            'slug' => 'foreign-project',
            'description' => null,
            'status' => 'active',
            'owner_id' => $foreignOwner->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        Sanctum::actingAs(
            $user,
            ['projects:read'],
        );

        $response = $this->getJson('/api/v1/projects');

        $response->assertOk();

        $response->assertJsonFragment([
            'name' => 'Owned Project',
        ]);

        $response->assertJsonFragment([
            'name' => 'Member Project',
        ]);

        $response->assertJsonMissing([
            'name' => 'Foreign Project',
        ]);
    }
}
