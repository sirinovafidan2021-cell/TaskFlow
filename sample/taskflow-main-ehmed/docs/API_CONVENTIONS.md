# API Conventions

## Versioning and Authentication

All documented API routes use the `/api/v1` prefix. API authentication uses Laravel Sanctum personal access tokens, not Passport. Sanctum establishes the authenticated user; token abilities scope what a token can do; Spatie Permission determines the user's general role; and Laravel Policies authorize actions on individual records. Token abilities do not replace Policies.

Token abilities are:

```text
projects:read
projects:write
tasks:read
tasks:write
comments:write
activity:read
dashboard:read
```

| Method | Endpoint             | Purpose                                |
| ------ | -------------------- | -------------------------------------- |
| POST   | `/api/v1/auth/token` | Create a personal access token.        |
| GET    | `/api/v1/me`         | Return the authenticated user context. |
| DELETE | `/api/v1/auth/token` | Delete the current token.              |

The token request contains `email`, `password`, and `device_name`. A successful token response places `token` and `abilities` inside `data`. The plaintext token is shown only at creation time.

## API Purpose and Shared Business Logic

The API is intended for:

- Junior developers practicing REST API development with Postman.
- Future internal integrations.
- Reporting scripts.
- Automated task creation.
- Preparation for a future mobile application.
- Learning how Web and API share the same business logic.

Blade pages do not have to call the API.

Web controllers, API controllers and Livewire components share the same Service layer:

```text
Web Controller ─────┐
                    ├── TaskService → TaskRepository
API Controller ─────┤
                    │
Livewire Component ─┘
```

Business logic must not be duplicated between Web and API controllers.

## Endpoints

### Projects

```text
GET    /api/v1/projects
POST   /api/v1/projects
GET    /api/v1/projects/{project}
PUT    /api/v1/projects/{project}
DELETE /api/v1/projects/{project}

GET    /api/v1/projects/{project}/members
POST   /api/v1/projects/{project}/members
DELETE /api/v1/projects/{project}/members/{user}
```

### Tasks, Comments, and Attachments

```text
GET    /api/v1/tasks
POST   /api/v1/tasks
GET    /api/v1/tasks/{task}
PUT    /api/v1/tasks/{task}
DELETE /api/v1/tasks/{task}

PATCH  /api/v1/tasks/{task}/status
PATCH  /api/v1/tasks/{task}/assignee

GET    /api/v1/tasks/{task}/comments
POST   /api/v1/tasks/{task}/comments
DELETE /api/v1/tasks/{task}/comments/{comment}

GET    /api/v1/tasks/{task}/attachments
POST   /api/v1/tasks/{task}/attachments
DELETE /api/v1/tasks/{task}/attachments/{attachment}
```

### Activity and Dashboard

```text
GET /api/v1/activity
GET /api/v1/tasks/{task}/activity
GET /api/v1/projects/{project}/activity

GET /api/v1/dashboard/summary
GET /api/v1/dashboard/my-tasks
GET /api/v1/dashboard/overdue
```

## Validation and Authorization

Use Form Requests for validation. Controllers obtain validated data, create DTOs, authorize, and call shared services. Use Policies for record-specific authorization and Spatie Permission for roles/permissions. Requests without a token return `401`; missing token ability or denied Policy authorization returns `403`; invalid input returns `422`; successful creation returns `201`.

Task transitions are enforced by `TaskStatusService`, not controllers. Allowed transitions are:

```text
todo        → in_progress, cancelled
in_progress → todo, review, cancelled
review      → in_progress, done
done        → may be reopened only by a manager
cancelled   → may be reopened only by a manager
```

## Filtering, Sorting, and Pagination

The task collection accepts these query parameters:

```text
search
status
priority
project_id
assignee_id
due_before
sort
page
per_page
```

Example: `/api/v1/tasks?search=api&status=in_progress&priority=high&project_id=10&assignee_id=5&due_before=2026-09-01&sort=-due_at&page=1&per_page=20`.

Pass the filters to the repository through `TaskFiltersData`. Cap `per_page` at `100`. List queries eager-load `project`, `assignee`, and `creator`.

## Resources and Response Formats

API controllers never return Eloquent models directly. Use `ProjectResource`, `ProjectCollection`, `TaskResource`, `TaskCollection`, `TaskCommentResource`, `ActivityResource`, and `DashboardSummaryResource`.

Single resources use a top-level `data` object. Paginated responses use top-level `data` plus `meta.current_page`, `meta.per_page`, `meta.total`, and `meta.last_page`. Validation failures use Laravel's `message` and field-keyed `errors` structure.

## Security Requirements

Apply Form Request validation, Policy authorization, roles/permissions, Sanctum abilities, and rate limiting. Guard mass assignment, XSS, and CSRF. Validate attachment MIME type and size, authorize attachment downloads, and ensure assignees belong to the task's project. Do not expose secret fields in API responses or passwords, tokens, or secrets in activity history.
