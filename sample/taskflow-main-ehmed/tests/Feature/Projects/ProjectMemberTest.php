<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Services\ProjectMemberService;
use Tests\TestCase;

final class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_membership_is_rejected(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $project = Project::query()->create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'description' => null,
            'status' => 'active',
            'owner_id' => $owner->id,
            'starts_at' => null,
            'due_at' => null,
        ]);

        ProjectMember::query()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'member_role' => 'member',
            'joined_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(ProjectMemberService::class)->addMember(
            projectId: $project->id,
            userId: $member->id,
            memberRole: 'member',
        );
    }
}
