<?php
namespace Modules\Tasks\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Modules\Tasks\Http\Requests\UploadTaskAttachmentRequest;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Services\TaskAttachmentService;
class TaskAttachmentController { use AuthorizesRequests; public function __construct(private readonly TaskAttachmentService $attachments) {} public function store(UploadTaskAttachmentRequest $request, Task $task): RedirectResponse { $this->authorize('uploadAttachment',$task); $this->attachments->upload($task->load('project'),$request->user(),$request->file('attachment')); return back()->with('success','Attachment uploaded.'); } public function download(Task $task, TaskAttachment $attachment) { abort_unless($attachment->task_id===$task->id,404); $this->authorize('view',$task); return $this->attachments->download($attachment); } public function destroy(Task $task, TaskAttachment $attachment): RedirectResponse { abort_unless($attachment->task_id===$task->id,404); $this->authorize('deleteAttachment',[$attachment,$task]); $this->attachments->delete($attachment->load('task'),request()->user()); return back()->with('success','Attachment deleted.'); } }
