# API manual acceptance checklist

## Status and safety

This checklist reflects the verified runtime v1 contract in `API_CONVENTIONS.md`. Execute it in TF-1003 against an isolated test database with disposable accounts/tokens. Never save raw credentials or bearer tokens in committed files. An importable collection/OpenAPI file is not included because it has not been explicitly approved.

Recommended variables: `base_url`, `admin_email`, `admin_password`, `admin_token`, `manager_token`, `member_token`, `outsider_token`, `project_id`, `other_project_id`, `task_id`, `subtask_id`, `label_id`, `media_id`, `comment_id`.

For every request verify status, JSON envelope, content type, error shape, authorization, data scoping, and absence of stack/SQL/path/secret leakage.

## Runtime route manifest

The following matrix is locked to the runtime route inventory by automated contract tests. `—` means the endpoint has no Sanctum ability middleware; protected identity endpoints still require a valid token.

| Method | Path | Ability | Expected result |
| --- | --- | --- | --- |
| POST | `/auth/token` | — | 201 token envelope; 422 invalid credentials/input |
| GET | `/me` | — | 200 authenticated user resource |
| DELETE | `/auth/token` | — | 204 current token revoked |
| GET | `/projects` | projects:read | 200 paginated collection |
| POST | `/projects` | projects:write | 201 project resource |
| GET | `/projects/{project}` | projects:read | 200 scoped resource |
| PUT | `/projects/{project}` | projects:write | 200 project resource |
| PATCH | `/projects/{project}/status` | projects:write | 200 project resource; 409 state conflict |
| GET | `/projects/{project}/members` | projects:read | 200 paginated collection |
| POST | `/projects/{project}/members` | projects:write | 201 member resource |
| PATCH | `/projects/{project}/members/{user}` | projects:write | 200 member resource |
| DELETE | `/projects/{project}/members/{user}` | projects:write | 204; 409 open assignments |
| GET | `/tasks` | tasks:read | 200 paginated collection |
| POST | `/tasks` | tasks:write | 201 task resource |
| GET | `/tasks/{task}` | tasks:read | 200 scoped resource |
| PUT | `/tasks/{task}` | tasks:write | 200 task resource |
| DELETE | `/tasks/{task}` | tasks:write | 204 |
| PATCH | `/tasks/{task}/assignee` | tasks:write | 200 task resource |
| PATCH | `/tasks/{task}/status` | tasks:write | 200 task resource; 409 stale version |
| PATCH | `/tasks/{task}/rank` | tasks:write | 200 task resource; 409 stale version |
| PUT | `/tasks/{task}/labels` | tasks:write | 200 task resource |
| GET | `/projects/{project}/backlog` | tasks:read | 200 paginated collection |
| GET | `/projects/{project}/board` | tasks:read | 200 fixed-column resource envelope |
| GET | `/projects/{project}/labels` | tasks:read | 200 collection |
| POST | `/projects/{project}/labels` | tasks:write | 201 label resource |
| PATCH | `/projects/{project}/labels/{label}` | tasks:write | 200 label resource |
| DELETE | `/projects/{project}/labels/{label}` | tasks:write | 204 |
| GET | `/tasks/{task}/watchers` | tasks:read | 200 watcher collection |
| POST | `/tasks/{task}/watchers` | tasks:write | 204 |
| DELETE | `/tasks/{task}/watchers/{user}` | tasks:write | 204 |
| GET | `/tasks/{task}/comments` | tasks:read | 200 collection |
| POST | `/tasks/{task}/comments` | comments:write | 201 comment resource |
| DELETE | `/tasks/{task}/comments/{comment}` | comments:write | 204 |
| GET | `/tasks/{task}/media` | tasks:read | 200 paginated collection |
| POST | `/tasks/{task}/media` | tasks:write | 201 attachment collection |
| GET | `/tasks/{task}/media/{media}/preview` | tasks:read | 200 private stream |
| GET | `/tasks/{task}/media/{media}/download` | tasks:read | 200 private stream |
| DELETE | `/tasks/{task}/media/{media}` | tasks:write | 204 |
| GET | `/activity` | activity:read | 200 paginated collection |
| GET | `/projects/{project}/activity` | activity:read | 200 paginated collection |
| GET | `/tasks/{task}/activity` | activity:read | 200 paginated collection |
| GET | `/dashboard/summary` | dashboard:read | 200 summary resource |
| GET | `/dashboard/my-tasks` | dashboard:read | 200 collection |
| GET | `/dashboard/reported` | dashboard:read | 200 collection |
| GET | `/dashboard/watched` | dashboard:read | 200 collection |
| GET | `/dashboard/overdue` | dashboard:read | 200 paginated collection |

## Token bootstrap and identity

- [ ] `POST /api/v1/auth/token` accepts valid credentials/device name/abilities and returns a plaintext token once.
- [ ] Invalid/suspended credentials and excessive attempts fail safely without enumeration.
- [ ] Requested abilities are validated against the allowed set and caller policy.
- [ ] `GET /api/v1/me` returns the authenticated active user and effective abilities.
- [ ] `DELETE /api/v1/auth/token` revokes the current token; reuse fails.
- [ ] Missing, malformed, revoked, or insufficient-ability tokens return the documented 401/403 response.

## Projects and members

- [ ] List/show reveal only accessible projects and use stable pagination metadata.
- [ ] Admin create/update validates name, unique key, dates, and lifecycle rules.
- [ ] Lifecycle action permits only documented transitions; Completed/Archived mutation attempts return conflict/authorization correctly.
- [ ] Member list/add/role-change/remove enforce global eligibility, project role matrix, final-manager/integrity rules, and isolation.
- [ ] Foreign project/member IDs cannot be combined to infer or mutate data.
- [ ] Route names and v1 URL/resource shapes match `API_CONVENTIONS.md`.

## Work items

- [ ] Member creates Task/Bug/Story; Subtask requires a valid same-project parent.
- [ ] Priority accepts only `low`, `medium`, `high`, or `urgent`; invalid/configurable values return 422.
- [ ] Response includes immutable project-local issue key and approved resource fields only.
- [ ] List/show are visible to all project members, not only assignees; outsider sees no metadata.
- [ ] Update/delete follows reporter/role and lifecycle rules.
- [ ] Assignee endpoint accepts null or one current project member and rejects arrays, removed members, and cross-project users.
- [ ] Status action enforces the transition matrix and timestamps.
- [ ] Rank/backlog/board requests are project-scoped, deterministic, and reject stale/duplicate/foreign IDs.
- [ ] Search/filter/sort allow-lists reject unknown fields/directions and return stable pagination.

## Labels, watchers, and comments

- [ ] Project label CRUD validates scoped unique name/color and role permissions.
- [ ] Task label attach/detach rejects foreign-project labels and duplicates.
- [ ] Watch/unwatch is idempotent; watcher list is authorized and no multi-assignee behavior appears.
- [ ] Comment create/delete enforces membership, ownership/role, active-project, length, and sanitized resource output.
- [ ] Related Activity and notification side effects occur once and only after successful state changes.

## Central Media

- [ ] Multipart upload accepts a safe allowed file and returns Media metadata, never a public disk path.
- [ ] Empty, oversized, disallowed, MIME/extension-mismatched, and suspicious-name uploads return documented validation errors.
- [ ] Task Media list is scoped and paginated if defined.
- [ ] Authorized preview/download streams the complete correct bytes with safe `Content-Type`, `Content-Disposition`, nosniff, and cache behavior; v1 exposes no HTTP Range/206 contract.
- [ ] Outsider/cross-project/removed-member access returns no binary or metadata.
- [ ] One invalid item makes a multi-file upload all-or-nothing; no association, Media record, or physical file from that request remains.
- [ ] Delete removes the association transactionally, emits audit once, and cleans the unreferenced Media record/binary after commit.

## Activity, notifications, and Dashboard

- [ ] Activity list filters/paginates only accessible project/task records and exposes sanitized changes.
- [ ] No notification-inbox API route exists in v1; notification recipient/side-effect behavior is proved by automated tests and the Web checklist.
- [ ] Dashboard summary and assigned/reported/watched/overdue queues match accessible source data, including `/dashboard/watched` for the authenticated user.
- [ ] Counts cannot be manipulated to reveal an inaccessible project's existence.

## Validation and protocol matrix

- [ ] JSON requests send `Accept: application/json`; unsupported media/type behavior is documented.
- [ ] 200/201/204 usage is consistent; 204 has no body.
- [ ] 401, 403, 404, 409, 422, 429, and 500 cases follow the documented envelope and semantics.
- [ ] Expected domain conflicts map narrowly to 409; unexpected programming/runtime failures remain 500 and are logged.
- [ ] Validation errors use stable field keys, including nested/array input.
- [ ] Pagination `data`/`meta` and links/cursors cannot carry unvalidated parameters.
- [ ] Replayed destructive/reordering requests have the documented idempotency/conflict behavior.
- [ ] Rate-limit headers/behavior are present for authentication and other selected abuse-prone endpoints.

## Isolation attack pass

Repeat show/update/delete/member/label/comment/media/activity/dashboard operations while substituting:

- another project's valid ID;
- another user's valid ID;
- a valid child resource under the wrong parent URL;
- sequential/random nonexistent IDs;
- an ID that was accessible before membership removal;
- a token without the necessary ability.

No response may disclose private names, counts, timestamps, validation distinctions, files, or relationship existence.

## Evidence record

Export a secret-free collection/environment or record each request name, actor/ability, sanitized request, expected/actual status, schema assertion, side-effect assertion, date, and defect/TASK-ID. The collection is evidence only when the database assertions and negative cases also pass.
