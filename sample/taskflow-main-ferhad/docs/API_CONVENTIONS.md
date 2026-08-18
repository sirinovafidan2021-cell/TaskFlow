# API conventions

## Base and authentication

Base prefix: `/api/v1`. Authentication uses Laravel Sanctum personal access tokens.

```text
POST   /api/v1/auth/token
GET    /api/v1/me
DELETE /api/v1/auth/token
```

## Endpoints

```text
GET    /api/v1/projects
POST   /api/v1/projects
GET    /api/v1/projects/{project}
PUT    /api/v1/projects/{project}
DELETE /api/v1/projects/{project}
GET    /api/v1/projects/{project}/members
POST   /api/v1/projects/{project}/members
DELETE /api/v1/projects/{project}/members/{user}

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

GET /api/v1/activity
GET /api/v1/tasks/{task}/activity
GET /api/v1/projects/{project}/activity

GET /api/v1/dashboard/summary
GET /api/v1/dashboard/my-tasks
GET /api/v1/dashboard/overdue
```

## Task lists

Supported filters: `search`, `status`, `priority`, `project_id`, `assignee_id`, `due_before`, `sort`, `page`, `per_page`. Example:

```text
GET /api/v1/tasks?search=api&status=in_progress&priority=high&project_id=10&assignee_id=5&due_before=2026-09-01&sort=-due_at&page=1&per_page=20
```

Maximum `per_page` is `100`. Sorting must use an explicit whitelist; never pass arbitrary request input to `orderBy`. Task lists eager-load `project`, `assignee`, and `creator` when needed.

## Resources and responses

Planned resources: `ProjectResource`, `ProjectCollection`, `TaskResource`, `TaskCollection`, `TaskCommentResource`, `ActivityResource`, and `DashboardSummaryResource`.

```json
{"data":{"id":1,"number":"TSK-000001","title":"Prepare API documentation","status":"in_progress","priority":"high"}}
```

```json
{"data":[],"meta":{"current_page":1,"per_page":20,"total":100,"last_page":5}}
```

Use Laravel-compatible validation-error responses. Sanctum abilities do not replace record-level Policies, and API controllers never return Eloquent models directly.
