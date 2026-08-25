# TaskFlow repository instructions

## Authority and reading order

This repository is being rebuilt from the Ehmed implementation into a complete minimal Jira-like issue tracker. The inherited code is a starting point, not the specification.

Before changing code, read in this order:

1. `AGENTS.md`
2. `docs/PROJECT_BRIEF.md`
3. `docs/DECISIONS.md`
4. `docs/ARCHITECTURE.md`
5. `docs/SECURITY.md`
6. `docs/TEST_STRATEGY.md`
7. the active TASK-ID in `docs/IMPLEMENTATION_PLAN.md`
8. `docs/TASKS.md`

Read `docs/API_CONVENTIONS.md` for any API task and `docs/MEDIA.md` for any upload, image, preview, download, or deletion task. Read `docs/CURRENT_STATE_AUDIT.md` when repairing inherited code. `docs/ROADMAP.md` is deferred and must not be implemented unless the user explicitly starts a roadmap item.

When documents conflict, the priority is: explicit user instruction, `AGENTS.md`, `PROJECT_BRIEF`, `DECISIONS`, `ARCHITECTURE`, active implementation task, then supporting documents. Code is evidence of current state, not authority for target behavior.

## New Codex session bootstrap

This repository is the complete planning source. Do not look for or depend on an external `docs-rewrite` folder, pasted plan, previous chat, old handoff, or another TaskFlow repository.

Whenever this repository is opened in a new Codex session:

1. read the mandatory documents above completely before proposing or changing code;
2. inspect `docs/TASKS.md` for the current status and evidence;
3. if one task is `in_progress`, continue only that task;
4. otherwise select the earliest `pending` task whose dependencies are `verified` in `docs/IMPLEMENTATION_PLAN.md`;
5. announce the selected TASK-ID, scope, expected files, and verification before implementation;
6. implement its full acceptance criteria, update/add tests, and record exact evidence in `docs/TASKS.md`;
7. stop at the task boundary and report the next unblocked task.

At the packaged baseline, TF-000 is verified and TF-001 is the first unblocked task. This sentence is only a handoff hint; after work begins, `docs/TASKS.md` always determines the next task.

Do not create a replacement implementation plan, merge roadmap items into the active scope, or treat inherited code as completed behavior. Questions that the canonical documents already answer are not blockers; follow the recorded decision.

## Project identity

- Framework: Laravel 13, PHP 8.3+
- Architecture: modular monolith using `nwidart/laravel-modules`
- Frontend: Blade, Tailwind CSS, Vite, vanilla JavaScript
- Interactivity: limited Livewire
- API: REST under `/api/v1`
- Web auth: Laravel session authentication
- API auth: Laravel Sanctum personal access tokens
- Authorization: Spatie Permission plus Policies/Gates
- Audit: Spatie Activitylog through the Activity module
- Tests: Pest plus focused Playwright E2E tests

Target modules are Projects, Tasks, Media, Activity, and Dashboard. Authentication, internal user administration, personal token issuance, and user notifications remain in the host application. Do not create `Api`, `Web`, `Auth`, `Users`, `Core`, `Shared`, `Labels`, `Board`, or `Notifications` modules.

## Non-negotiable product rules

- The system is single-organization. Do not add workspaces or multi-tenancy.
- A work item belongs to one project and has one reporter.
- A work item has zero or one assignee. Never add multiple assignees.
- Project members may browse all work items in their project. Assignment represents responsibility, not visibility.
- Many interested project members use watchers.
- Work types are fixed: `task`, `bug`, `story`, and one-level `subtask`.
- Priorities are fixed and ordered `low < medium < high < urgent`; do not invent configurable priorities.
- Project-scoped labels are supported. Do not add a generic category system or components in the current scope.
- The fixed workflow is `backlog`, `todo`, `in_progress`, `review`, `done`, and `cancelled` with transitions owned by `TaskStatusService`.
- Every new work item starts in backlog; clients cannot choose an initial status or raw rank.
- Explicit backlog/column reordering is manager-only. An assignee may perform an allowed status transition; that move places the work item at the end of the target column without granting general reorder authority.
- Project keys generate project-local issue numbers such as `PAY-42`; global `TSK-000001` numbering is being retired.
- Archived projects are read-only everywhere. Completed projects are also read-only but can be reopened by an authorized manager; archived projects cannot be restored in the current scope.
- Media is private. All file and image metadata/storage operations go through the Media module.
- A multi-file media request is all-or-nothing: validate all inputs before persistence and compensate every stored file/record if any item fails.
- Public registration is disabled. Internal user provisioning, admin password reset, authenticated self-service password change, and account suspension are core host-application features.
- Suspension is never blocked by open assignments: suspend access and revoke sessions/tokens, unassign open work, remove watcher subscriptions, and preserve reporter/assignment history through Activity.
- Notifications are database/in-app and Web-only in v1. Dashboard includes My Watched Work, but the general Task filter set has no arbitrary watcher filter.
- Do not add sprints, epics, custom fields, custom workflows, dependencies, recurring tasks, automations, webhooks, or external integrations.

## Application flow and layer ownership

```text
Route
  -> Web/API Controller or approved Livewire component
  -> Form Request / component validation
  -> Policy / Gate and Sanctum ability when applicable
  -> purpose-specific DTO
  -> Service
  -> Repository interface
  -> Eloquent implementation and model
  -> Blade / API Resource / redirect
```

Controllers are adapters. They may authorize, consume validated data, construct a DTO, call a service/query service, and return a presentation response. They must not contain Eloquent queries, transactions, workflow rules, membership rules, storage operations, or duplicated Web/API logic.

Services own use-case orchestration, domain invariants, transactions, approved direct cross-module calls, and meaningful activity recording. A service does not replace entry-point authorization, but it must protect state invariants such as archived-project immutability, project membership, one-assignee rules, issue-key allocation, and parent/child consistency.

Repositories own Eloquent persistence and queries: actor/project scope, filters, sorting, pagination, eager loading, locking, and create/update/delete operations. Interfaces use `*RepositoryInterface` and implementations live under `Repositories/Eloquent`.

DTOs are readonly, purpose-specific, and built only from validated input. Use immutable date values where dates participate in business logic. Do not pass `request()->all()` into DTOs or queries.

API controllers return Resources. Blade templates must not query the database. JavaScript and Livewire must not duplicate backend business rules.

## Module boundaries during implementation

Direct dependencies are intentionally accepted until the current implementation plan is complete. Examples:

- Tasks may call `ProjectMemberService` and use the Project model.
- Tasks may call Media services for authorized attachment use cases.
- Projects and Tasks may call `ActivityRecorder`.
- Dashboard may query Projects and Tasks through their current services/repositories.

Keep those calls explicit and documented. Do not introduce speculative contracts, adapters, buses, or events to hide them. Loose coupling is a separate roadmap phase after behavior and tests are stable.

## Media rules

Never store task files directly from Tasks code. The Media module owns randomized storage paths, server-detected MIME, size, checksum, image dimensions, safe streaming, and physical cleanup. The consuming module owns authorization and its association table.

Do not use public disk URLs for private media. Do not allow SVG or executable content in the initial allowlist. Never trust client filenames, extensions, or MIME declarations. Media deletion must not leave database/file inconsistencies.

## Livewire and JavaScript

Livewire is restricted to:

- `QuickTaskCreate`
- `TaskFilters`
- `TaskStatusSelector`
- `TaskCommentForm`

Board drag/drop, modal behavior, preview controls, counters, and copy actions use focused vanilla JavaScript. Every core operation must still work or fail safely without JavaScript. The server remains authoritative.

## Validation, authorization, and errors

Form Requests validate input shape. Policies/Gates decide record/application access. Services protect domain state. Sanctum abilities narrow API access and never bypass permissions or policies.

Do not render every `LogicException` as a domain conflict. Use specific domain exceptions and map them deliberately:

- 401 unauthenticated
- 403 authorization/ability denial
- 404 missing or safely mismatched nested resource
- 409 documented state conflict
- 422 input validation failure
- 429 rate limit
- 500 unexpected programming/infrastructure failure

Nested resources must be scoped to their parent. Filters must never disclose inaccessible project, user, label, watcher, media, or activity metadata.

## Testing rules

Pest is the primary test layer. Use unit tests for transition/ranking/key rules; integration tests for repositories and database constraints; feature tests for Web/API/security/Livewire; and Playwright only for critical real-browser journeys.

Playwright complements Pest; it does not replace it. Browser tests must not be used to prove every validation or authorization branch.

The default automated test database is SQLite `:memory:`. A separate MySQL compatibility suite is allowed but cannot be the only test path. Module test directories must be discovered explicitly. New behavior is not complete without relevant tests.

## Safety and environment rules

- Never access or edit `.env` unless the active task explicitly authorizes a specific operation.
- Never expose credentials, token plaintext, hashes, private media paths, or secrets.
- Never install/update/remove dependencies without explicit approval in the active task.
- Never run migrations, seeders, destructive database commands, Git commands, or real browser automation without explicit approval.
- Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or equivalent against an unidentified database.
- Preserve unrelated user changes.

## Agent execution protocol

Work on one implementation TASK-ID at a time. Respect task dependencies. Do not mark work verified from file existence or documentation claims; verification requires recorded command/test evidence or an explicitly named manual acceptance owner.

When a task changes a decision, update the authoritative document in the same task. Do not create alternative plans or duplicate sources of truth.

Every implementation report must contain:

```text
TASK-ID:
Outcome:
Changed files:
Checks run and results:
Checks skipped and reasons:
Security/authorization impact:
Data/migration impact:
Remaining risks:
Documentation updated:
```

Only `docs/TASKS.md` tracks execution status. `docs/IMPLEMENTATION_PLAN.md` defines the work and should change only when scope or acceptance criteria change.
