<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Projects\Models\Project;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_manager_cannot_archive_project(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $project = Project::query()->create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'description' => null,
            'status' => 'active',
            'owner_id' => $owner->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('projects.archive', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'active',
        ]);
    }

    public function test_project_manager_can_archive_owned_project(): void
    {
        Role::create([
            'name' => 'Project manager',
            'guard_name' => 'web',
        ]);
        $manager = User::factory()->create();
        $manager->assignRole('Project manager');

        $project = Project::query()->create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'description' => null,
            'status' => 'active',
            'owner_id' => $manager->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        $this->actingAs($manager)
            ->patch(route('projects.archive', $project))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'archived',
        ]);
    }
}
