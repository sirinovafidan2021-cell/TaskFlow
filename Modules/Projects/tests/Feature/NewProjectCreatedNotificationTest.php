<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\NewProjectCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\Contracts\ProjectRepositoryInterface;
use Modules\Projects\Services\ProjectService;

it('queues one mail notification for every registered user after an admin creates a project', function (): void {
    Notification::fake();

    $admin = userWithRole(UserRole::Admin);
    $users = collect([
        $admin,
        User::factory()->create(['name' => 'User One']),
        User::factory()->create(['name' => 'User Two']),
        User::factory()->create(['name' => 'User Three']),
    ]);

    $this->actingAs($admin)->post('/projects', [
        'name' => 'Notification project',
        'description' => 'A complete project notification description.',
    ])->assertRedirect();

    $project = Project::query()->where('name', 'Notification project')->firstOrFail();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'owner_id' => $admin->id]);

    Notification::assertSentTo($users, NewProjectCreatedNotification::class, function (NewProjectCreatedNotification $notification, array $channels): bool {
        expect($notification)->toBeInstanceOf(ShouldQueue::class)
            ->and($channels)->toBe(['mail']);

        return true;
    });
    Notification::assertSentTimes(NewProjectCreatedNotification::class, $users->count());
    Notification::assertSentToTimes($admin, NewProjectCreatedNotification::class, 1);

    $message = (new NewProjectCreatedNotification($project, $admin))->toMail($users->first());
    expect($message->subject)->toBe('A New Project Has Been Created')
        ->and($message->greeting)->toBe('Hello '.$admin->name.',')
        ->and($message->introLines)->toContain('Project: Notification project')
        ->toContain('Description: A complete project notification description.')
        ->toContain('Created by: '.$admin->name)
        ->and($message->actionText)->toBe('View Project');
});

it('does not dispatch a project notification for unauthorized or invalid project creation', function (): void {
    Notification::fake();

    $member = userWithRole(UserRole::Member);
    $this->actingAs($member)->post('/projects', ['name' => 'Denied project'])->assertForbidden();
    Notification::assertNothingSent();

    $admin = userWithRole(UserRole::Admin);
    $this->actingAs($admin)->post('/projects', ['name' => 'x', 'due_at' => 'not-a-date'])
        ->assertSessionHasErrors(['name', 'due_at']);
    Notification::assertNothingSent();
});

it('does not dispatch a project notification when a non-admin creates a permitted project', function (): void {
    Notification::fake();

    $manager = userWithRole(UserRole::ProjectManager);
    $this->actingAs($manager)->post('/projects', ['name' => 'Manager project'])->assertRedirect();

    $this->assertDatabaseHas('projects', ['name' => 'Manager project', 'owner_id' => $manager->id]);
    Notification::assertNothingSent();
});

it('does not dispatch a notification when project persistence fails', function (): void {
    Notification::fake();

    $admin = userWithRole(UserRole::Admin);
    $projects = Mockery::mock(ProjectRepositoryInterface::class);
    $projects->shouldReceive('slugExists')->once()->with('failed-project', null)->andReturnFalse();
    $projects->shouldReceive('save')->once()->andThrow(new RuntimeException('Database failure'));
    app()->instance(ProjectRepositoryInterface::class, $projects);

    expect(fn () => app(ProjectService::class)->create($admin, ProjectData::fromArray(['name' => 'Failed project'])))
        ->toThrow(RuntimeException::class, 'Database failure');

    Notification::assertNothingSent();
});
