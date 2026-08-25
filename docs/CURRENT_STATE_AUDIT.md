# Inherited Ehmed implementation audit

## Purpose

This is a diagnosis of the selected starting code at `C:\Users\ziya\Desktop\exp\taskflow-main-Ehmed\taskflow-main`. It is not the target specification and it does not authorize roadmap work.

The audit was performed statically because the delivered directory does not contain `vendor/` or `node_modules/`. Historical test/build claims in legacy documents were therefore not reproduced during this audit.

## Useful foundation to retain

- Full Laravel application skeleton with lock files and enabled Projects, Tasks, Activity, and Dashboard modules.
- Laravel 13/PHP 8.3 dependency baseline with Sanctum, Livewire, Nwidart Modules, Spatie Permission/Activitylog, and Pest.
- Functional Blade/Tailwind layouts and core Web flows.
- Session login with throttling, session regeneration, logout invalidation, and CSRF regeneration.
- Project/task services, policies, repositories, DTOs, Resources, private attachment validation, activity recording, and dashboard queries.
- Actor-scoped Project and Task list queries, safe sort whitelisting, bounded API pagination, and nested task-resource checks.
- A substantial regression suite containing 35 test blocks, although stored in one nonstandard file and tied to MySQL.
- Internal Admin User Management with a final-admin safeguard.

## Critical correctness and security findings

### Authentication and API

- `/api/v1/tokens` is protected by `auth:sanctum`, so it cannot bootstrap the first token from credentials.
- Required `POST /api/v1/auth/token`, `GET /api/v1/me`, and current-token revoke endpoints are absent.
- Account administration has no admin password-reset or authenticated self-change lifecycle.
- Token creation is performed directly in the controller rather than an authentication service/DTO boundary.
- Almost all API routes are centralized in root `routes/api.php`, not owned by modules, and most routes are unnamed.
- Every API `LogicException` is rendered as 409, which can hide programming errors.

### Authorization and business integrity

- Project-context `manager` and global role permissions are not fully aligned; a context manager can be denied before the policy's record rule becomes useful.
- Members see only assigned tasks. That matches the inherited implementation but conflicts with the accepted Jira-like collaboration model where project membership grants browse access.
- Members cannot create issues even though issue reporting is core collaboration behavior.
- Archived-project immutability is enforced for project/member operations but not consistently for task update, assignment, status, comment, or media operations.
- Removing a project member does not resolve that user's open task assignments or future watcher links.
- Policy methods accept boolean bypass arguments such as `allowArchived`; services later reject some states, creating unclear 403/409 behavior.
- Project status `completed` exists but has no lifecycle action or documented state behavior in code.

### Data and migrations

- `DatabaseSeeder` creates a generic test user, does not call `RolePermissionSeeder`, and does not produce a usable initial administrator.
- `project_members` is created and then structurally aligned in a second migration; this is avoidable pre-production schema debt and weakens SQLite portability.
- The Activitylog migration has no `down()` method.
- Task numbers are global `TSK-000001`, not project-local Jira-style keys.
- Task numbers are nullable during a two-save create flow.
- No factories exist for Project, ProjectMember, Task, TaskComment, or TaskAttachment.
- No schema exists for issue types, backlog rank, parent/subtask, labels, watchers, centralized media, or database notifications.

### Services and transactions

- `ProjectMemberService` add/remove operations are not transactionally self-contained with activity recording.
- Project/task update activity records changed field names but not safe old/new values.
- Task controllers and services contain direct Eloquent lookups that should move to query/repository/service boundaries.
- The Web task index builds filters from unvalidated `request()->all()`.
- Attachment upload compensates database failure, but delete removes the file before the database/activity transaction is guaranteed to commit.
- Attachments are owned directly by Tasks rather than a centralized Media module.

### Portability and query design

- `ActivityQueryService::filterOptions` uses MySQL-specific `JSON_UNQUOTE(JSON_EXTRACT(...))`.
- Dashboard and Activity directly query other modules. This is accepted temporarily but must be recorded for the loose-coupling roadmap.
- The test configuration requires a local MySQL database named `taskflow_test`; a clean checkout cannot run tests with SQLite alone.

### UI and interaction

- Livewire is installed but none of the four approved components exists.
- Vanilla JavaScript implements mobile navigation and confirm dialogs only.
- There is no backlog, board, issue type, label, watcher, notification, project-key issue number, subtask, or centralized media UI.
- There are no real-browser automated tests.

### Tests and documentation

- `qa/` is not a QA framework. It contains one 766-line Pest file with 35 test blocks.
- Standard module unit/feature test directories and architecture tests are absent.
- `tests/Pest.php` still contains example scaffolding.
- `phpunit.xml` includes only `app` as source, not `Modules`.
- Legacy README is Laravel boilerplate.
- Legacy `TASKS.md` and `V1_HANDOFF.md` falsely claim completion.
- Legacy API/Postman documentation describes the token bootstrap gap as intentional.
- Legacy test counts are stale and inconsistent with the current test source.

## Initial risk order

1. Establish safe, portable test and seed baselines.
2. Correct authorization, archived-state, member-removal, and token-bootstrap behavior.
3. Standardize routes, repositories, DTOs, exceptions, and module ownership.
4. Introduce the central Media module before expanding attachment/image behavior.
5. Implement the approved minimal-Jira business model.
6. Complete Livewire, UI, API parity, activity, dashboard, and browser acceptance.

The ordered implementation is defined only in `IMPLEMENTATION_PLAN.md`.
