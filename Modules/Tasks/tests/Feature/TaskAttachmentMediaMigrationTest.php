<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Projects\Models\Project;
use Modules\Tasks\Http\Resources\TaskAttachmentResource;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Services\TaskAttachmentService;
use Modules\Tasks\Support\TaskAttachmentMediaBackfill;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates a single task-to-media association while retaining legacy fields for migration verification', function (): void {
    $actor = User::factory()->asProjectManager()->create();
    $project = Project::factory()->active()->create(['owner_id' => $actor->id]);
    $task = Task::factory()->for($project)->create();

    $attachment = app(TaskAttachmentService::class)->upload(
        $task->load('project'),
        $actor,
        UploadedFile::fake()->createWithContent('evidence.txt', 'preserved task evidence'),
    );

    $attachment->load('media');
    expect($attachment->media)->toBeInstanceOf(Media::class)
        ->and($attachment->media_id)->toBe($attachment->media->id)
        ->and($attachment->path)->toBe($attachment->media->path)
        ->and($attachment->disk)->toBe($attachment->media->disk)
        ->and(Media::count())->toBe(1)
        ->and(TaskAttachment::count())->toBe(1);
    Storage::disk('local')->assertExists($attachment->media->path);
});

it('keeps the authorized download backed by Media metadata without exposing internal storage fields', function (): void {
    $actor = User::factory()->asProjectManager()->create();
    $project = Project::factory()->active()->create(['owner_id' => $actor->id]);
    $task = Task::factory()->for($project)->create();
    $attachment = app(TaskAttachmentService::class)->upload(
        $task->load('project'),
        $actor,
        UploadedFile::fake()->createWithContent('evidence.txt', 'preserved task evidence'),
    );

    $resource = (new TaskAttachmentResource($attachment->load('uploader', 'media')))->resolve();
    $response = app(TaskAttachmentService::class)->download($attachment);

    expect($resource)->toMatchArray([
        'media_uuid' => $attachment->media->uuid,
        'original_name' => 'evidence.txt',
        'mime_type' => 'text/plain',
        'size' => strlen('preserved task evidence'),
    ])->not->toHaveKeys(['disk', 'path', 'sha256'])
        ->and(json_encode($resource))->not->toContain($attachment->media->path)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-disposition'))->toContain('attachment');
});

it('removes the association and its Media record/file together', function (): void {
    $actor = User::factory()->asProjectManager()->create();
    $project = Project::factory()->active()->create(['owner_id' => $actor->id]);
    $task = Task::factory()->for($project)->create();
    $attachment = app(TaskAttachmentService::class)->upload(
        $task->load('project'),
        $actor,
        UploadedFile::fake()->createWithContent('evidence.txt', 'preserved task evidence'),
    );
    $media = $attachment->media;

    app(TaskAttachmentService::class)->delete($attachment->load('task.project', 'media'), $actor);

    expect(TaskAttachment::count())->toBe(0)
        ->and(Media::withTrashed()->find($media->id)?->trashed())->toBeTrue();
    Storage::disk('local')->assertMissing($media->path);
});

it('backfills a preserved legacy attachment without moving its file or losing its download', function (): void {
    $actor = User::factory()->asProjectManager()->create();
    $project = Project::factory()->active()->create(['owner_id' => $actor->id]);
    $task = Task::factory()->for($project)->create();
    $path = 'task-attachments/legacy-proof.txt';
    Storage::disk('local')->put($path, 'legacy evidence');
    $legacy = TaskAttachment::create([
        'task_id' => $task->id,
        'uploaded_by' => $actor->id,
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'legacy-proof.txt',
        'mime_type' => 'text/plain',
        'size' => strlen('legacy evidence'),
    ]);

    TaskAttachmentMediaBackfill::run();
    $legacy->refresh()->load('media');

    expect(TaskAttachment::count())->toBe(1)
        ->and(Media::count())->toBe(1)
        ->and($legacy->media)->toBeInstanceOf(Media::class)
        ->and($legacy->media->path)->toBe($path)
        ->and($legacy->media->sha256)->toBe(hash('sha256', 'legacy evidence'))
        ->and(app(TaskAttachmentService::class)->download($legacy)->getStatusCode())->toBe(200);
    Storage::disk('local')->assertExists($path);
});
