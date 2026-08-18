<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Projects\Models\Project;
use Tests\TestCase;

final class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_member_cannot_view_project(): void
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
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }
}
