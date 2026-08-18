<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Projects\Models\Project;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProjectUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_project_cannot_be_updated(): void
    {
        Role::create([
            'name' => 'Project manager',
            'guard_name' => 'web',
        ]);

        $manager = User::factory()->create();
        $manager->assignRole('Project manager');

        $project = Project::query()->create([
            'name' => 'Archived Project',
            'slug' => 'archived-project',
            'description' => null,
            'status' => 'archived',
            'owner_id' => $manager->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('projects.edit', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Archived Project',
            'status' => 'archived',
        ]);
    }
}
