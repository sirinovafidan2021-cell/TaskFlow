<?php

namespace Modules\Tasks\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Models\TaskComment;

class TaskPolicy
{
    public function __construct(private readonly ProjectMemberService $members) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksView->value);
    }

    public function create(User $user, Project $project, bool $allowInactive = false): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksCreate->value)
            && $this->members->canManage($project, $user)
            && ($allowInactive || $project->status === ProjectStatus::Active);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksView->value) && ($this->members->canManage($task->project, $user) || $task->assignee_id === $user->id);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksUpdate->value) && $this->members->canManage($task->project, $user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksDelete->value) && $this->members->canManage($task->project, $user);
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksAssign->value) && $this->members->canManage($task->project, $user);
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::TasksStatusChange->value) && ($this->members->canManage($task->project, $user) || $task->assignee_id === $user->id);
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::CommentsCreate->value) && $this->view($user, $task);
    }

    public function deleteComment(User $user, Task $task, TaskComment $comment): bool
    {
        return $user->hasPermissionTo(PermissionName::CommentsDelete->value) && ($user->id === $comment->user_id || $this->members->canManage($task->project, $user));
    }

    public function uploadAttachment(User $user, Task $task): bool
    {
        return $user->hasPermissionTo(PermissionName::AttachmentsUpload->value) && $this->view($user, $task);
    }

    public function deleteAttachment(User $user, Task $task, TaskAttachment $attachment): bool
    {
        return $user->hasPermissionTo(PermissionName::AttachmentsDelete->value) && ($attachment->uploaded_by === $user->id || $this->members->canManage($task->project, $user));
    }
}
