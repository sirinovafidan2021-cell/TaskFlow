<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskWatcherService;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void { $this->seed(RolePermissionSeeder::class); });

function canonicalActivityContext(): array
{
    $manager = User::factory()->asProjectManager()->create(); $member = User::factory()->asMember()->create(); $other = User::factory()->asProjectManager()->create();
    $project = Project::factory()->active()->create(['owner_id' => $manager->id]); $foreignProject = Project::factory()->active()->create(['owner_id' => $other->id]);
    app(ProjectMemberService::class)->addMember($project, $member, ProjectMemberRole::Member, actor: $manager);
    $task = Task::factory()->for($project)->for($manager, 'creator')->create(); $foreignTask = Task::factory()->for($foreignProject)->for($other, 'creator')->create();
    return compact('manager', 'member', 'other', 'project', 'foreignProject', 'task', 'foreignTask');
}

test('canonical recorder versions payloads, removes sensitive values recursively, and gives mutations one event', function (): void {
    extract(canonicalActivityContext());
    app(ActivityRecorder::class)->record(ActivityEvent::TaskUpdated, $manager, $task, ['project_id' => $project->id, 'task_id' => $task->id, 'old' => ['title' => 'Before', 'description' => 'private'], 'new' => ['title' => 'After', 'token' => 'secret'], 'path' => '/private', 'checksum' => 'hash', 'content' => 'body']);
    $properties = Activity::query()->latest('id')->firstOrFail()->properties->toArray();
    expect($properties)->toMatchArray(['schema_version' => 1, 'project_id' => $project->id, 'task_id' => $task->id])
        ->and($properties['old'])->toBe(['title' => 'Before'])
        ->and($properties['new'])->toBe(['title' => 'After'])
        ->and($properties)->not->toHaveKeys(['path', 'checksum', 'content']);

    $watchers = app(TaskWatcherService::class);
    $watchers->watch($task->load('project'), $member, $member);
    $watchers->watch($task->fresh()->load('project'), $member, $member);
    $watchers->unwatch($task->fresh()->load('project'), $member, $member);
    expect(Activity::query()->where('event', ActivityEvent::WatcherAdded->value)->count())->toBe(1)
        ->and(Activity::query()->where('event', ActivityEvent::WatcherRemoved->value)->count())->toBe(1);
});

test('global project and task activity routes keep foreign filter and nested resource metadata scoped', function (): void {
    extract(canonicalActivityContext());
    app(ActivityRecorder::class)->record(ActivityEvent::TaskCreated, $manager, $task, ['project_id' => $project->id, 'task_id' => $task->id, 'task_number' => $task->number, 'task_title' => $task->title]);
    app(ActivityRecorder::class)->record(ActivityEvent::TaskCreated, $other, $foreignTask, ['project_id' => $foreignProject->id, 'task_id' => $foreignTask->id, 'task_number' => $foreignTask->number, 'task_title' => $foreignTask->title]);
    Sanctum::actingAs($manager, ['activity:read']);
    $this->getJson('/api/v1/activity?project_id='.$foreignProject->id)->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/activity?task_id='.$foreignTask->id)->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/projects/'.$foreignProject->id.'/activity')->assertForbidden();
    $this->getJson('/api/v1/tasks/'.$foreignTask->id.'/activity')->assertForbidden();
    $this->getJson('/api/v1/projects/'.$project->id.'/activity')->assertOk()->assertJsonPath('data.0.schema_version', 1)->assertJsonMissing(['path' => '/private']);
    $this->actingAs($manager)->get(route('projects.activity', $project))->assertOk()->assertSee($task->number);
    expect(app(ActivityQueryService::class)->filterOptions($manager)['projects']->pluck('id')->all())->toContain($project->id)->not->toContain($foreignProject->id);
});
