<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Modules\Dashboard\Services\DashboardService;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Enums\TaskType;
use Modules\Tasks\Models\Task;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function dashboardMetricsContext(): array
{
    $manager = User::factory()->asProjectManager()->create();
    $member = User::factory()->asMember()->create();
    $outsider = User::factory()->asMember()->create();
    $project = Project::factory()->active()->create(['owner_id' => $manager->id, 'key' => 'MET']);
    $hiddenProject = Project::factory()->completed()->create(['owner_id' => $outsider->id, 'key' => 'HID']);
    app(ProjectMemberService::class)->addMember($project, $member, ProjectMemberRole::Member, actor: $manager);

    $assigned = Task::factory()->for($project)->for($manager, 'creator')->create([
        'title' => 'Assigned visible work', 'assignee_id' => $member->id, 'status' => TaskStatus::Todo, 'type' => TaskType::Bug, 'due_at' => now()->subDay(),
    ]);
    $reported = Task::factory()->for($project)->for($member, 'creator')->create([
        'title' => 'Reported visible work', 'status' => TaskStatus::InProgress, 'type' => TaskType::Story,
    ]);
    $completed = Task::factory()->for($project)->for($manager, 'creator')->create([
        'title' => 'Completed today visible work', 'status' => TaskStatus::Done, 'completed_at' => now(), 'type' => TaskType::Task,
    ]);
    $completed->watchers()->attach($member);
    $hidden = Task::factory()->for($hiddenProject)->for($outsider, 'creator')->create([
        'title' => 'Hidden dashboard work', 'assignee_id' => $member->id, 'status' => TaskStatus::Review,
    ]);

    return compact('manager', 'member', 'outsider', 'project', 'assigned', 'reported', 'completed', 'hidden');
}

test('dashboard metrics and personal queues use the canonical project visibility scope', function (): void {
    ['member' => $member, 'assigned' => $assigned, 'reported' => $reported, 'completed' => $completed, 'hidden' => $hidden] = dashboardMetricsContext();

    $summary = app(DashboardService::class)->summary($member);

    expect($summary['activeProjects'])->toBe(1)
        ->and($summary['totalTasks'])->toBe(3)
        ->and($summary['todo'])->toBe(1)
        ->and($summary['inProgress'])->toBe(1)
        ->and($summary['overdue'])->toBe(1)
        ->and($summary['completedToday'])->toBe(1)
        ->and($summary['projectStatusDistribution'])->toBe(['active' => 1])
        ->and($summary['taskTypeDistribution'])->toBe(['bug' => 1, 'story' => 1, 'task' => 1])
        ->and($summary['myTasks']->pluck('id')->all())->toBe([$assigned->id])
        ->and($summary['reportedTasks']->pluck('id')->all())->toBe([$reported->id])
        ->and($summary['watchedTasks']->pluck('id')->all())->toBe([$completed->id])
        ->and($summary['overdueTasks']->pluck('id')->all())->toBe([$assigned->id])
        ->and($summary['myTasks']->pluck('id')->all())->not->toContain($hidden->id);
});

test('admin, context manager and member dashboard totals follow the same visibility matrix as task lists', function (): void {
    ['manager' => $manager, 'member' => $member, 'hidden' => $hidden] = dashboardMetricsContext();
    $admin = User::factory()->asAdmin()->create();

    expect(app(DashboardService::class)->summary($manager)['totalTasks'])->toBe(3)
        ->and(app(DashboardService::class)->summary($member)['totalTasks'])->toBe(3)
        ->and(app(DashboardService::class)->summary($admin)['totalTasks'])->toBe(4)
        ->and(app(DashboardService::class)->paginateOverdue($member, 20)->pluck('id')->all())->not->toContain($hidden->id);
});

test('dashboard web and API queues have parity and do not disclose inaccessible work', function (): void {
    ['member' => $member, 'assigned' => $assigned, 'reported' => $reported, 'completed' => $completed, 'hidden' => $hidden] = dashboardMetricsContext();

    $this->actingAs($member)->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('My Assigned Work')
        ->assertSee('Reported by Me')
        ->assertSee('My Watched Work')
        ->assertSee($assigned->title)
        ->assertSee($reported->title)
        ->assertSee($completed->title)
        ->assertDontSee($hidden->title);

    Sanctum::actingAs($member, ['dashboard:read']);
    $this->getJson('/api/v1/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 3)
        ->assertJsonPath('data.project_status_distribution.active', 1)
        ->assertJsonPath('data.task_type_distribution.bug', 1);
    $this->getJson('/api/v1/dashboard/my-tasks')->assertOk()->assertJsonPath('data.0.id', $assigned->id);
    $this->getJson('/api/v1/dashboard/reported')->assertOk()->assertJsonPath('data.0.id', $reported->id);
    $this->getJson('/api/v1/dashboard/watched')->assertOk()->assertJsonPath('data.0.id', $completed->id);
    $this->getJson('/api/v1/dashboard/overdue')->assertOk()->assertJsonPath('data.0.id', $assigned->id)->assertJsonMissing(['id' => $hidden->id]);
});

test('dashboard API requires both a dashboard ability and dashboard authorization', function (): void {
    ['member' => $member] = dashboardMetricsContext();

    Sanctum::actingAs($member, ['tasks:read']);
    $this->getJson('/api/v1/dashboard/summary')->assertForbidden();

    $unprivileged = User::factory()->create();
    Sanctum::actingAs($unprivileged, ['dashboard:read']);
    $this->getJson('/api/v1/dashboard/summary')->assertForbidden();
});
