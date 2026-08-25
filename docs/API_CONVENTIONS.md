# Target REST API v1 contract

## Status

This document defines the target contract. The inherited route file does not yet implement it. Endpoint completion is tracked by `IMPLEMENTATION_PLAN.md` and `TASKS.md`.

Base path: `/api/v1`

JSON requests send `Accept: application/json`. Protected requests send `Authorization: Bearer {token}`.

## Authentication

### Issue token

```text
POST /api/v1/auth/token
```

Input:

```json
{
  "email": "person@example.test",
  "password": "secret",
  "device_name": "postman-local",
  "abilities": ["projects:read", "tasks:read"]
}
```

Valid credentials return 201 and plaintext token once. Invalid credentials return a generic 422 response. Suspended users cannot receive tokens. This endpoint has a dedicated low rate limit.

### Current user and revoke

```text
GET    /api/v1/me
DELETE /api/v1/auth/token
```

`DELETE` revokes only the token authenticating the current request and returns 204.

There is no public registration endpoint and no token-bootstrap route that itself requires an existing token. Token-list/rotation UI is not part of the current API contract.

Internal user administration, admin password reset, authenticated self-service password change, and notification inbox actions are Web-only in API v1.

## Abilities

Canonical Sanctum abilities remain:

- `projects:read`
- `projects:write`
- `tasks:read`
- `tasks:write`
- `comments:write`
- `activity:read`
- `dashboard:read`

Labels, watchers, backlog, board, and media use task read/write abilities because they are work-item capabilities. Ability checks are always followed by permission/policy authorization.

## Projects

| Method | Path | Ability | Result |
| --- | --- | --- | --- |
| GET | `/projects` | projects:read | scoped paginated projects |
| POST | `/projects` | projects:write | create Draft project, 201 |
| GET | `/projects/{project}` | projects:read | project detail |
| PUT | `/projects/{project}` | projects:write | update mutable details |
| PATCH | `/projects/{project}/status` | projects:write | validated lifecycle transition |
| GET | `/projects/{project}/members` | projects:read | paginated members |
| POST | `/projects/{project}/members` | projects:write | add member, 201 |
| PATCH | `/projects/{project}/members/{user}` | projects:write | change project role |
| DELETE | `/projects/{project}/members/{user}` | projects:write | remove member, 204 |

Project create accepts `name`, `key`, optional `description`, `starts_at`, and `due_at`. Key is uppercase 2–10 alphanumeric characters, starts with a letter, is globally unique, and becomes immutable after the first work item.

Status input is `active`, `completed`, or `archived`; the service validates the current-to-target transition. Completed reopen uses target `active`.

Member removal returns 409 while the user has open project assignments.

## Work items

The API path remains `/tasks` for compatibility even though UI/product language may say work item or issue.

| Method | Path | Ability | Result |
| --- | --- | --- | --- |
| GET | `/tasks` | tasks:read | scoped paginated work items |
| POST | `/tasks` | tasks:write | create work item, 201 |
| GET | `/tasks/{task}` | tasks:read | detail with safe relations |
| PUT | `/tasks/{task}` | tasks:write | update mutable detail |
| DELETE | `/tasks/{task}` | tasks:write | manager soft delete, 204 |
| PATCH | `/tasks/{task}/assignee` | tasks:write | assign/unassign |
| PATCH | `/tasks/{task}/status` | tasks:write | workflow transition |
| PATCH | `/tasks/{task}/rank` | tasks:write | backlog/column reorder |
| PUT | `/tasks/{task}/labels` | tasks:write | sync same-project labels |

Create accepts:

- `project_id`
- `type`: task, bug, story, or subtask
- `parent_id` only for subtask
- `title`
- optional `description`
- optional `assignee_id`
- `priority`
- optional `due_at`
- optional same-project `label_ids`

Display number, reporter, initial status/rank, timestamps, and auto-watchers are server-owned.

Priority is required and is one of `low`, `medium`, `high`, or `urgent`; canonical ordering is `low < medium < high < urgent`. Every new work item starts in Backlog at a server-calculated position.

Assignment body contains nullable `assignee_id`. A member may self-assign; assigning another user requires manager policy. Status body contains `status` and may contain `expected_updated_at` for optimistic conflict detection.

Rank body uses neighbor intent instead of trusting an arbitrary raw rank:

```json
{
  "before_task_id": 41,
  "after_task_id": 39,
  "expected_updated_at": "2026-08-24T10:00:00.000000Z"
}
```

The service validates same project/board context and produces the persisted rank.

Explicit rank/reorder is project-manager-only. An assignee may perform an allowed status transition; the server places that work item at the end of the target column without allowing that assignee to reorder other work.

## Backlog and board

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/projects/{project}/backlog` | tasks:read |
| GET | `/projects/{project}/board` | tasks:read |

These read models contain only actor-visible project work and safe card fields. They are filterable but not unbounded full-detail exports.

## Labels

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/projects/{project}/labels` | tasks:read |
| POST | `/projects/{project}/labels` | tasks:write |
| PATCH | `/projects/{project}/labels/{label}` | tasks:write |
| DELETE | `/projects/{project}/labels/{label}` | tasks:write |

Label mutation is manager-only. Nested label/project mismatch returns 404. Task label sync rejects labels from another project.

## Watchers

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/tasks/{task}/watchers` | tasks:read |
| POST | `/tasks/{task}/watchers` | tasks:write |
| DELETE | `/tasks/{task}/watchers/{user}` | tasks:write |

POST accepts optional `user_id`; omitted means current user. A non-manager can manage only their own watcher state. Only project members can watch.

## Comments

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/tasks/{task}/comments` | tasks:read |
| POST | `/tasks/{task}/comments` | comments:write |
| DELETE | `/tasks/{task}/comments/{comment}` | comments:write |

Comments are plain text, maximum 5,000 characters. Author or project manager may delete. Cross-task nesting returns 404.

## Media

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/tasks/{task}/media` | tasks:read |
| POST | `/tasks/{task}/media` | tasks:write |
| GET | `/tasks/{task}/media/{media}/preview` | tasks:read |
| GET | `/tasks/{task}/media/{media}/download` | tasks:read |
| DELETE | `/tasks/{task}/media/{media}` | tasks:write |

Upload is multipart form-data with `media[]`, maximum five files. The request is all-or-nothing: all files are validated first and any validation/storage/association/Activity failure compensates everything created by that request. Resource metadata never includes disk, path, checksum, or temporary URL internals. Cross-task association mismatch returns 404. HTTP Range/206 streaming is not part of v1.

## Activity

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/activity` | activity:read |
| GET | `/projects/{project}/activity` | activity:read |
| GET | `/tasks/{task}/activity` | activity:read |

Filters: event, project_id, task_id, actor_id, date_from, date_to, page, per_page. Filter validation/existence checks must not leak unauthorized metadata.

## Dashboard

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/dashboard/summary` | dashboard:read |
| GET | `/dashboard/my-tasks` | dashboard:read |
| GET | `/dashboard/reported` | dashboard:read |
| GET | `/dashboard/watched` | dashboard:read |
| GET | `/dashboard/overdue` | dashboard:read |

In-app notification pages remain Web-only in the current API version.

## Task filters

Supported query filters:

- `search` for key/title/description;
- `project_id`;
- `types[]`;
- `statuses[]`;
- `priorities[]`;
- `assignee_id`, including an explicit unassigned representation;
- `reporter_id`;
- `label_ids[]`;
- `parent_id`;
- `due_before`, `due_after`, `overdue`;
- `sort`;
- `page`, `per_page`.

There is no arbitrary `watcher_id` Task filter in v1. The authenticated user's watched work is exposed through `/dashboard/watched`.

Allowed sort values use signed notation:

```text
number, -number, created_at, -created_at, updated_at, -updated_at,
due_at, -due_at, priority, -priority, status, -status, rank, -rank
```

`per_page` is 1–100. Unknown filters/sorts return 422 rather than reaching raw SQL.

## Response envelopes

Single resource:

```json
{"data":{"id":42,"number":"PAY-42","type":"bug","title":"Payment retry fails"}}
```

Paginated collection uses Laravel Resource `data`, `links`, and `meta`. Delete success is bodyless 204.

Validation errors follow Laravel's 422 field-error shape. Domain conflicts have a stable machine code and safe message:

```json
{
  "message": "The member has open assignments.",
  "code": "member_has_open_assignments",
  "errors": {"user_id":["Reassign or unassign open work before removal."]}
}
```

## Status codes

| Status | Meaning |
| --- | --- |
| 200 | successful read/update |
| 201 | resource created |
| 204 | successful deletion/revoke, no body |
| 401 | missing/invalid/revoked token or suspended actor |
| 403 | ability/permission/policy denial |
| 404 | missing or safely mismatched nested resource |
| 409 | documented state/concurrency conflict |
| 422 | request validation failure |
| 429 | named rate limit exceeded |
| 500 | unexpected failure; safe generic production response |

## Route and contract rules

- Every route is named `api.v1.*`.
- Module API routes live in the owning module.
- API controllers return Resources/collections, not models.
- Web/API/Livewire use the same service for a mutation.
- No obsolete route alias is kept unless a documented compatibility requirement exists.
- Contract tests lock method/path, auth/ability/policy behavior, status, envelope, and private-field absence.
