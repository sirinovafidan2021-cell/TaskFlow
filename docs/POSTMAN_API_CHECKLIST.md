# API manual acceptance checklist

## Status and safety

This collection/checklist describes the **target v1 API** in `API_CONVENTIONS.md`. Execute it in TF-1003 after automated tests pass. Use an isolated test database and disposable accounts/tokens. Never save raw credentials or bearer tokens in committed files.

Recommended variables: `base_url`, `admin_email`, `admin_password`, `admin_token`, `manager_token`, `member_token`, `outsider_token`, `project_id`, `other_project_id`, `task_id`, `subtask_id`, `label_id`, `media_id`, `comment_id`.

For every request verify status, JSON envelope, content type, error shape, authorization, data scoping, and absence of stack/SQL/path/secret leakage.

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
- [ ] Comment create/update/delete enforces membership, ownership/role, active-project, length, and sanitized resource output.
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
- [ ] Dashboard summary and assigned/reported/watched/overdue/recent queues match accessible source data, including `/dashboard/watched` for the authenticated user.
- [ ] Counts cannot be manipulated to reveal an inaccessible project's existence.

## Validation and protocol matrix

- [ ] JSON requests send `Accept: application/json`; unsupported media/type behavior is documented.
- [ ] 200/201/204 usage is consistent; 204 has no body.
- [ ] 400, 401, 403, 404, 409, 422, 429, and 500 cases follow the documented envelope and semantics.
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
