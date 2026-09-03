<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskLabelData;
use Modules\Tasks\Enums\TaskLabelColor;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskLabel;
use Modules\Tasks\Services\TaskLabelService;
use Modules\Tasks\Services\TaskService;

beforeEach(function (): void { $this->seed(RolePermissionSeeder::class); });

function labelContext(): array
{
    $manager = User::factory()->asProjectManager()->create(); $reporter = User::factory()->asMember()->create(); $member = User::factory()->asMember()->create(); $outsider = User::factory()->asMember()->create();
    $project = Project::factory()->active()->create(['owner_id' => $manager->id]); $other = Project::factory()->active()->create(['owner_id' => $manager->id]); $members = app(ProjectMemberService::class);
    $members->addMember($project, $reporter, ProjectMemberRole::Member, actor: $manager); $members->addMember($project, $member, ProjectMemberRole::Member, actor: $manager);
    return [$manager, $reporter, $member, $outsider, $project, $other];
}
function labelData(string $name = 'Backend', TaskLabelColor $color = TaskLabelColor::Blue): CreateTaskLabelData { return new CreateTaskLabelData($name, $color); }

test('label service normalizes names, rejects duplicate slug and foreign labels, and deleting detaches safely', function (): void {
    [$manager, $reporter, , , $project, $other] = labelContext(); $labels = app(TaskLabelService::class); $label = $labels->create($project, labelData(' Backend '), $manager);
    $task = app(TaskService::class)->create($reporter, $project, new \Modules\Tasks\Data\CreateTaskData($project->id, 'Labelled work', null, null, TaskPriority::Medium, null, labelIds: [$label->id]));
    expect($label->name)->toBe('Backend')->and($label->slug)->toBe('backend')->and($task->labels()->pluck('task_labels.id')->all())->toBe([$label->id]);
    expect(fn () => $labels->create($project, labelData('backend', TaskLabelColor::Red), $manager))->toThrow(LogicException::class);
    $foreign = $labels->create($other, labelData('Foreign'), $manager); expect(fn () => $labels->sync($task->fresh()->load('project'), [$foreign->id], $reporter))->toThrow(\Illuminate\Validation\ValidationException::class);
    $labels->delete($label, $manager); expect(TaskLabel::find($label->id))->toBeNull()->and($task->fresh()->labels)->toHaveCount(0)->and(Task::find($task->id))->not->toBeNull();
});

test('web task forms and task create update flows synchronize project labels', function (): void {
    [$manager, $reporter, , , $project] = labelContext(); $label = app(TaskLabelService::class)->create($project, labelData(), $manager);
    $this->actingAs($reporter)->get(route('tasks.create', $project))->assertOk()->assertSee('Labels')->assertSee('Backend');
    $this->actingAs($reporter)->post(route('tasks.store', $project), ['title' => 'Web labelled work', 'priority' => 'medium', 'label_ids' => [$label->id]])->assertRedirect(); $task = Task::query()->where('title', 'Web labelled work')->firstOrFail();
    expect($task->labels()->pluck('task_labels.id')->all())->toBe([$label->id]);
    $this->actingAs($reporter)->put(route('tasks.update', $task), ['title' => 'Web labelled work', 'priority' => 'high', 'label_ids' => []])->assertRedirect(route('tasks.show', $task)); expect($task->fresh()->labels)->toHaveCount(0);
});

test('API exposes scoped label CRUD, task resource labels, sync and filtering', function (): void {
    [$manager, $reporter, , , $project] = labelContext(); Sanctum::actingAs($manager, ['tasks:read', 'tasks:write']);
    $created = $this->postJson("/api/v1/projects/{$project->id}/labels", ['name' => 'Backend', 'color' => TaskLabelColor::Blue->value])->assertCreated()->assertJsonPath('data.name', 'Backend')->assertJsonPath('data.color', TaskLabelColor::Blue->value); $labelId = $created->json('data.id');
    $this->patchJson("/api/v1/projects/{$project->id}/labels/{$labelId}", ['name' => 'Platform', 'color' => TaskLabelColor::Purple->value])->assertOk()->assertJsonPath('data.slug', 'platform'); $this->getJson("/api/v1/projects/{$project->id}/labels")->assertOk()->assertJsonPath('data.0.name', 'Platform');
    Sanctum::actingAs($reporter, ['tasks:read', 'tasks:write']); $task = $this->postJson('/api/v1/tasks', ['project_id' => $project->id, 'title' => 'API labelled work', 'priority' => 'medium', 'label_ids' => [$labelId]])->assertCreated()->assertJsonPath('data.labels.0.id', $labelId)->json('data');
    $this->putJson('/api/v1/tasks/'.$task['id'].'/labels', ['label_ids' => [$labelId]])->assertOk()->assertJsonPath('data.labels.0.slug', 'platform'); $this->getJson('/api/v1/tasks?label_id='.$labelId)->assertOk()->assertJsonFragment(['id' => $task['id']]); $this->deleteJson("/api/v1/projects/{$project->id}/labels/{$labelId}")->assertForbidden();
    Sanctum::actingAs($manager, ['tasks:write']); $this->deleteJson("/api/v1/projects/{$project->id}/labels/{$labelId}")->assertNoContent();
});

test('label authorization matrix preserves project scope and read-only lifecycle', function (): void {
    [$manager, , $member, $outsider, $project, $other] = labelContext(); $label = app(TaskLabelService::class)->create($project, labelData(), $manager); $foreign = app(TaskLabelService::class)->create($other, labelData('Foreign'), $manager);
    Sanctum::actingAs($member, ['tasks:read', 'tasks:write']); $this->getJson("/api/v1/projects/{$project->id}/labels")->assertOk(); $this->postJson("/api/v1/projects/{$project->id}/labels", ['name' => 'Nope', 'color' => TaskLabelColor::Red->value])->assertForbidden(); $this->patchJson("/api/v1/projects/{$project->id}/labels/{$foreign->id}", ['name' => 'Nope', 'color' => TaskLabelColor::Red->value])->assertForbidden();
    Sanctum::actingAs($outsider, ['tasks:read', 'tasks:write']); $this->getJson("/api/v1/projects/{$project->id}/labels")->assertForbidden();
    Sanctum::actingAs($manager, ['tasks:write']); $this->postJson("/api/v1/projects/{$project->id}/labels", ['name' => 'Invalid', 'color' => '#112233'])->assertUnprocessable()->assertJsonValidationErrors('color'); $this->patchJson("/api/v1/projects/{$project->id}/labels/{$foreign->id}", ['name' => 'Mismatch', 'color' => TaskLabelColor::Red->value])->assertNotFound(); $project->update(['status' => ProjectStatus::Completed]); $this->deleteJson("/api/v1/projects/{$project->id}/labels/{$label->id}")->assertForbidden();
});

test('web label CRUD is manager-only and direct sync follows task edit authority', function (): void {
    [$manager, $reporter, $member, , $project, $other] = labelContext();
    $this->actingAs($member)->get(route('projects.labels.index', $project))->assertOk();
    $this->actingAs($member)->post(route('projects.labels.store', $project), ['name' => 'Denied', 'color' => TaskLabelColor::Red->value])->assertForbidden();
    $this->actingAs($manager)->post(route('projects.labels.store', $project), ['name' => 'Web label', 'color' => TaskLabelColor::Green->value])->assertRedirect();
    $label = TaskLabel::query()->where('name', 'Web label')->firstOrFail();
    $this->actingAs($manager)->patch(route('projects.labels.update', [$project, $label]), ['name' => 'Renamed', 'color' => TaskLabelColor::Purple->value])->assertRedirect();
    expect($label->fresh()->slug)->toBe('renamed');

    $task = app(TaskService::class)->create($reporter, $project, new \Modules\Tasks\Data\CreateTaskData($project->id, 'Authority work', null, null, TaskPriority::Medium, null));
    $foreign = app(TaskLabelService::class)->create($other, labelData('Foreign'), $manager);
    Sanctum::actingAs($member, ['tasks:write']);
    $this->putJson('/api/v1/tasks/'.$task->id.'/labels', ['label_ids' => [$label->id]])->assertForbidden();
    Sanctum::actingAs($reporter, ['tasks:write']);
    $this->putJson('/api/v1/tasks/'.$task->id.'/labels', ['label_ids' => [$foreign->id]])->assertUnprocessable()->assertJsonValidationErrors('label_ids');
    $this->actingAs($manager)->delete(route('projects.labels.destroy', [$project, $label]))->assertRedirect();
});
