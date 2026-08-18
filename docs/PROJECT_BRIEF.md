# TaskFlow project brief

## Objective

TaskFlow is an internal company project and task-management application, not a basic CRUD todo list. It teaches practical Laravel architecture. The finished application will include projects, project members, task assignment, statuses, priorities, comments, attachments, activity history, a dashboard, REST API, Sanctum tokens, roles, permissions, policies, filtering, sorting, pagination, and tests. Its planned supporting packages are Laravel Sanctum, `spatie/laravel-permission`, `spatie/laravel-activitylog`, and Pest (not installed yet).

## Roles

| Role | Capabilities |
| --- | --- |
| `admin` | View all projects; manage user roles; view/change every task; see the full activity log; manage API tokens. |
| `project_manager` | Create and manage owned/managable projects; manage project members; create and assign tasks; perform allowed status changes; view relevant activity. |
| `member` | View projects where they are a member; view authorized/assigned tasks; perform allowed transitions; add task comments. |

Members cannot access unrelated projects.

## Projects domain

The Projects module owns `projects` and `project_members`.

- Statuses: `draft`, `active`, `completed`, `archived`.
- Planned `projects` fields: `id`, `name`, `slug`, `description`, `status`, `owner_id`, `starts_at`, `due_at`, timestamps, `deleted_at`.
- Indexes: unique `slug`; `(owner_id, status)`; `(status, due_at)`.
- Planned `project_members` fields: `id`, `project_id`, `user_id`, `member_role`, `joined_at`, timestamps.
- Constraints: foreign keys to `projects.id` and `users.id`, plus unique `(project_id, user_id)`.

## Tasks domain

The Tasks module owns `tasks`, `task_comments`, and `task_attachments`. Task numbers use `TSK-000001` format. Statuses are `todo`, `in_progress`, `review`, `done`, and `cancelled`; priorities are `low`, `medium`, `high`, and `urgent`.

Planned task fields are `id`, `number`, `project_id`, `creator_id`, `assignee_id`, `title`, `description`, `status`, `priority`, `due_at`, `started_at`, `completed_at`, timestamps, and `deleted_at`.

Indexes: unique `number`; `(project_id, status)`; `(assignee_id, status)`; `(priority, due_at)`; `(project_id, assignee_id, status)`.

### Status transitions

```text
todo        -> in_progress, cancelled
in_progress -> todo, review, cancelled
review      -> in_progress, done
done        -> reopen only by manager/admin
cancelled   -> reopen only by manager/admin
```

`TaskStatusService` owns every transition rule, never controllers or JavaScript.

## Comments and attachments

Comments have `id`, `task_id`, `user_id`, `body`, timestamps, and `deleted_at`. Attachments have `id`, `task_id`, `uploaded_by`, `disk`, `path`, `original_name`, `mime_type`, `size`, and timestamps. Attachment downloads require authorization; a known storage path or URL never bypasses it.

## Dashboard metrics

The Dashboard will show active/archived project counts, total/todo/in-progress/review task counts, overdue tasks, tasks completed today, the current user's tasks, recent activities, and task distribution per project.
