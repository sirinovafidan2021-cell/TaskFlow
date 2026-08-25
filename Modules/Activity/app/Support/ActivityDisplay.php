<?php

namespace Modules\Activity\Support;

use Spatie\Activitylog\Models\Activity;

final class ActivityDisplay
{
    public static function label(Activity $activity): string
    {
        return match ($activity->event) {
            'project.created' => 'Project created',
            'project.updated' => 'Project updated',
            'project.activated' => 'Project activated',
            'project.archived' => 'Project archived',
            'project.member_added' => 'Member added',
            'project.member_removed' => 'Member removed',
            'task.created' => 'Task created',
            'task.updated' => 'Task updated',
            'task.assigned' => 'Task assignment changed',
            'task.status_changed' => 'Task status changed',
            'task.deleted' => 'Task deleted',
            'comment.created' => 'Comment added',
            'comment.deleted' => 'Comment deleted',
            'attachment.uploaded' => 'Attachment uploaded',
            'attachment.deleted' => 'Attachment deleted',
            default => 'Activity recorded',
        };
    }

    public static function summary(Activity $activity): ?string
    {
        $properties = $activity->properties->toArray();

        return match ($activity->event) {
            'project.created' => $properties['project_name'] ?? null,
            'task.created' => isset($properties['task_number'], $properties['task_title'])
                ? $properties['task_number'].' — '.$properties['task_title']
                : ($properties['task_title'] ?? null),
            'project.updated', 'task.updated' => ! empty($properties['changed'])
                ? 'Changed: '.implode(', ', array_map(static fn (string $field) => str_replace('_', ' ', $field), $properties['changed']))
                : null,
            'project.member_added' => isset($properties['member_name']) ? 'Added '.$properties['member_name'] : null,
            'project.member_removed' => isset($properties['member_name']) ? 'Removed '.$properties['member_name'] : null,
            'task.assigned' => self::assignmentSummary($properties),
            'task.status_changed' => isset($properties['old'], $properties['new'])
                ? self::status($properties['old']).' → '.self::status($properties['new'])
                : null,
            'attachment.uploaded', 'attachment.deleted' => $properties['filename'] ?? null,
            default => null,
        };
    }

    private static function assignmentSummary(array $properties): string
    {
        $old = $properties['old_assignee_name'] ?? 'Unassigned';
        $new = $properties['new_assignee_name'] ?? 'Unassigned';

        return $old.' → '.$new;
    }

    private static function status(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}
