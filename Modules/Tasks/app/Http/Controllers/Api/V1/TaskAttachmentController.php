<?php

namespace Modules\Tasks\Http\Controllers\Api\V1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tasks\Http\Requests\Api\V1\TaskAttachmentIndexRequest;
use Modules\Tasks\Http\Requests\UploadTaskAttachmentRequest;
use Modules\Tasks\Http\Resources\TaskAttachmentResource;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Repositories\TaskAttachmentRepository;
use Modules\Tasks\Services\TaskAttachmentService;

class TaskAttachmentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TaskAttachmentRepository $attachments,
        private readonly TaskAttachmentService $attachmentService,
    ) {}

    public function index(TaskAttachmentIndexRequest $request, Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        return TaskAttachmentResource::collection(
            $this->attachments->paginateForTask($task, $request->integer('per_page', 20)),
        );
    }

    public function store(UploadTaskAttachmentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('uploadAttachment', $task);

        $attachment = $this->attachmentService->upload(
            $task->load('project'),
            $request->user(),
            $request->file('attachment'),
        );

        return (new TaskAttachmentResource($attachment->load('uploader')))->response()->setStatusCode(201);
    }

    public function download(Task $task, TaskAttachment $attachment)
    {
        abort_unless($attachment->task_id === $task->id, 404);
        $this->authorize('view', $task);

        return $this->attachmentService->download($attachment);
    }

    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->task_id === $task->id, 404);
        $this->authorize('deleteAttachment', [$task, $attachment]);
        $this->attachmentService->delete($attachment->load('task'), request()->user());

        return response()->json(null, 204);
    }
}
