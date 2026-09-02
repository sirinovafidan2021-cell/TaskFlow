<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Dashboard\Services\DashboardService;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Services\TaskService;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function taskVisibilityContext(): array
{
    $manager = User::factory()->asProjectManager()->create();
    $reporter = User::factory()->asMember()->create();
    $member = User::factory()->asMember()->create();
    $outsider = User::factory()->asMember()->create();
    $project = Project::factory()->active()->create(['owner_id' => $manager->id, 'key' => 'VIS']);
    $members = app(ProjectMemberService::class);
    $members->addMember($project, $reporter, ProjectMemberRole::Member, actor: $manager);
    $members->addMember($project, $member, ProjectMemberRole::Member, actor: $manager);

    return [$manager, $reporter, $member, $outsider, $project];
}

function taskData(Project $project, string $title): CreateTaskData
{
    return new CreateTaskData($project->id, $title, 'Safe description', null, TaskPriority::Medium, null);
}

test('project members browse and report all active-project work while outsiders stay outside lists, activity and counts', function (): void {
    [$manager, $reporter, $member, $outsider, $project] = taskVisibilityContext();
    $service = app(TaskService::class);
    $reported = $service->create($reporter, $project, taskData($project, 'Member reported work'));
    $second = $service->create($manager, $project, taskData($project, 'Manager reported work'));
    $repository = app(TaskRepository::class);

    expect($repository->paginateFor($member, TaskFiltersData::fromArray([]))->pluck('id')->all())
        ->toContain($reported->id, $second->id)
        ->and($repository->paginateFor($outsider, TaskFiltersData::fromArray([]))->total())->toBe(0)
        ->and(app(ActivityQueryService::class)->recentForUser($member)->pluck('event')->all())->toContain('task.created')
        ->and(app(ActivityQueryService::class)->recentForUser($outsider))->toHaveCount(0)
        ->and(app(DashboardService::class)->summary($member)['totalTasks'])->toBe(2);

    Sanctum::actingAs($member, ['tasks:read', 'tasks:write']);
    $this->getJson('/api/v1/tasks')->assertOk()->assertJsonFragment(['id' => $reported->id]);
    $this->postJson('/api/v1/tasks', [
        'project_id' => $project->id,
        'title' => 'API member report',
        'priority' => TaskPriority::High->value,
    ])->assertCreated();

    Sanctum::actingAs($outsider, ['tasks:read']);
    $this->getJson('/api/v1/tasks/'.$reported->id)->assertForbidden();
});

test('manager and early-state reporter edits are authorized in web api and direct-service flows', function (): void {
    [$manager, $reporter, $member, , $project] = taskVisibilityContext();
    $service = app(TaskService::class);
    $reported = $service->create($reporter, $project, taskData($project, 'Reporter task'));
    $managerTask = $service->create($manager, $project, taskData($project, 'Manager task'));

    $this->actingAs($reporter)
        ->put(route('tasks.update', $reported), ['title' => 'Reporter edited task', 'description' => 'Changed', 'priority' => TaskPriority::High->value])
        ->assertRedirect(route('tasks.show', $reported));

    Sanctum::actingAs($manager, ['tasks:write']);
    $this->putJson('/api/v1/tasks/'.$reported->id, [
        'title' => 'Manager edited task',
        'description' => 'Manager changed it',
        'priority' => TaskPriority::Urgent->value,
    ])->assertOk()->assertJsonPath('data.title', 'Manager edited task');

    expect(fn () => $service->update($managerTask->fresh()->load('project'), new UpdateTaskData('Forbidden', null, TaskPriority::Low, null), $member))
        ->toThrow(LogicException::class);

    $reported->refresh()->update(['status' => TaskStatus::InProgress]);
    expect(fn () => $service->update($reported->fresh()->load('project'), new UpdateTaskData('Late edit', null, TaskPriority::Low, null), $reporter))
        ->toThrow(LogicException::class);

    $activity = Activity::query()->where('event', 'task.updated')->latest()->firstOrFail()->properties->toArray();
    expect($activity['old'])->toHaveKey('title')
        ->and($activity['new'])->toHaveKey('title')
        ->and($activity['changed'])->toContain('title')
        ->and($activity['old'])->not->toHaveKey('description');
});

test('completed and archived projects reject create update and delete at policy and service boundaries', function (ProjectStatus $status): void {
    [$manager, $reporter, , , $project] = taskVisibilityContext();
    $task = app(TaskService::class)->create($manager, $project, taskData($project, 'Frozen task'));
    $project->update(['status' => $status]);
    $task = $task->fresh()->load('project');
    $service = app(TaskService::class);

    expect($reporter->can('create', [Task::class, $project->fresh()]))->toBeFalse()
        ->and($manager->can('update', $task))->toBeFalse()
        ->and($manager->can('delete', $task))->toBeFalse()
        ->and(fn () => $service->create($reporter, $project->fresh(), taskData($project, 'Blocked report')))->toThrow(LogicException::class)
        ->and(fn () => $service->update($task, new UpdateTaskData('Blocked update', null, TaskPriority::Low, null), $manager))->toThrow(LogicException::class)
        ->and(fn () => $service->delete($task, $manager))->toThrow(LogicException::class);
})->with([[ProjectStatus::Completed], [ProjectStatus::Archived]]);

test('manager soft deletion remains auditable and disappears from normal task queries', function (): void {
    [$manager, $reporter, $member, , $project] = taskVisibilityContext();
    $task = app(TaskService::class)->create($reporter, $project, taskData($project, 'Delete me safely'));

    Sanctum::actingAs($manager, ['tasks:write']);
    $this->deleteJson('/api/v1/tasks/'.$task->id)->assertNoContent();

    expect(Task::query()->find($task->id))->toBeNull()
        ->and(Task::withTrashed()->findOrFail($task->id)->trashed())->toBeTrue()
        ->and(app(TaskRepository::class)->paginateFor($member, TaskFiltersData::fromArray([]))->pluck('id')->all())->not->toContain($task->id)
        ->and(DB::table('activity_log')->where('event', 'task.deleted')->where('subject_id', $task->id)->exists())->toBeTrue();
});
