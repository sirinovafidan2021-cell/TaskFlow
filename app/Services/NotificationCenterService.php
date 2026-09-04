<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Repositories\TaskRepository;

class NotificationCenterService
{
    public function __construct(private readonly TaskRepository $tasks) {}

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage)->through(fn (DatabaseNotification $notification): array => $this->present($user, $notification));
    }

    /** @return array{notification: DatabaseNotification, summary: string, task_url: ?string} */
    public function present(User $user, DatabaseNotification $notification): array
    {
        $task = null;
        try {
            $taskId = filter_var($notification->data['task_id'] ?? null, FILTER_VALIDATE_INT);
            $task = $taskId ? $this->tasks->findOrFail($taskId) : null;
        } catch (ModelNotFoundException) {
            $task = null;
        }

        if ($task === null || ! Gate::forUser($user)->allows('view', $task)) {
            return ['notification' => $notification, 'summary' => 'A task update is no longer available.', 'task_url' => null];
        }

        $event = match ($notification->data['event'] ?? null) {
            'task.assigned' => 'You were assigned a task',
            'task.status_changed' => 'Task status changed',
            'comment.created' => 'New task comment',
            default => 'Task update',
        };

        return ['notification' => $notification, 'summary' => $event.' · '.$task->number.' — '.$task->title, 'task_url' => route('tasks.show', $task)];
    }

    public function markRead(User $user, DatabaseNotification $notification): void
    {
        abort_unless($notification->notifiable_id === $user->id && $notification->notifiable_type === $user::class, 404);
        $notification->markAsRead();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
