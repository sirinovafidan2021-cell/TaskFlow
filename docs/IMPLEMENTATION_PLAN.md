# TaskFlow final implementation plan

## Purpose

This plan turns the inherited Ehmed Laravel application into the complete product defined in `PROJECT_BRIEF.md`. It combines:

- inherited architecture/security correctness fixes;
- the original Web/API/Livewire completion work;
- the approved minimal Jira-like business features;
- a central Media module;
- a reproducible Pest + Playwright quality gate.

It does **not** include the loose-coupling or advanced-security roadmap. Direct cross-module calls remain accepted throughout this plan.

## Execution rules

- Work on one TASK-ID at a time and honor dependencies.
- `TASKS.md` is the only mutable status/evidence tracker.
- A task is verified only when all acceptance criteria and recorded checks pass.
- Do not install dependencies, run migrations/seeders, use Git, or run Playwright without the active task's explicit approval.
- Schema work defaults to additive migration/backfill. Squashing requires explicit confirmation that persistent data is disposable.
- Every task must preserve Web/API/Livewire parity where the channel exists.
- Security and tests are part of each task, not a final afterthought.

## Completion overview

| Phase | Outcome |
| --- | --- |
| 0 | canonical scope, reproducible baseline, traceability |
| 1 | portable test infrastructure, decomposed regression, factories/seeders |
| 2 | architecture, routing, repository/DTO, exceptions, authorization corrections |
| 3 | complete internal account, session, and Sanctum authentication |
| 4 | complete project identity, lifecycle, membership, and APIs |
| 5 | central Media module and safe attachment migration |
| 6 | minimal-Jira work-item domain: keys, types, labels, watchers, backlog, board |
| 7 | target UI, four Livewire components, progressive JavaScript |
| 8 | canonical Activity, notifications, and complete Dashboard |
| 9 | final API contract/resource/ability acceptance |
| 10 | full Pest/Playwright/security/manual release gates and final documentation |

---

## Phase 0 — Canonical baseline and traceability

### TF-000 — Replace legacy documentation

**Dependencies:** none

**Actions**

- Replace Laravel boilerplate README and stale architecture/product/API documents.
- Remove false completion handoff and stale learning duplication.
- Add documentation index, current-state audit, accepted decisions, target architecture, security, media, test strategy, this plan, and deferred roadmap.
- Make every document distinguish current inherited state from target behavior.

**Acceptance**

- No document claims the inherited system is complete.
- `AGENTS.md` routes agents to one source of truth per concern.
- Product, architecture, API, security, media, tests, plan, and roadmap do not conflict.

### TF-001 — Establish reproducible dependency/runtime baseline

**Dependencies:** TF-000

**Actions**

- Verify supported PHP/Composer/Node versions against lock files.
- With explicit approval, install locked dependencies without updating versions.
- Validate Composer manifest/lock and npm lock consistency.
- Record module status, route inventory, migration inventory, test discovery, frontend inputs, and build prerequisites.
- Do not change dependency versions merely to make setup convenient.

**Acceptance**

- A clean setup procedure is documented and reproducible.
- Installed package/runtime versions are recorded in `TASKS.md`.
- No unapproved lock-file drift exists.

### TF-002 — Build requirement-to-code traceability

**Dependencies:** TF-000

**Actions**

- Create a matrix mapping each Product Brief rule and API family to implementation TASK-ID, files/layer, tests, and current status.
- Register every Current State Audit P0/P1 finding.
- Explicitly record direct module dependencies accepted until roadmap.

**Acceptance**

- No product rule, endpoint family, security control, or release gate is untracked.
- Roadmap items are not mixed into implementation status.

---

## Phase 1 — Safe test, factory, and seed foundation

### TF-100 — Portable Pest infrastructure

**Dependencies:** TF-001

**Actions**

- Configure SQLite `:memory:`, test APP_KEY, array cache/session/mail, sync queue, and fake storage defaults.
- Include `app` and all Modules in source/test discovery.
- Configure root and module Unit/Feature directories.
- Remove example Pest helpers/tests and prevent zero-test success.
- Add a separately named, opt-in MySQL compatibility configuration; never make it the default.

**Acceptance**

- The suite discovers application/module tests without a MySQL server.
- Module migrations load in tests.
- Test environment cannot accidentally use an unidentified persistent database.

### TF-101 — Decompose inherited `qa` regression

**Dependencies:** TF-100

**Actions**

- Map all 35 inherited test blocks to Auth/Admin/Projects/Tasks/Media/Activity/Dashboard/API concerns.
- Move/rewrite useful tests into standard root/module directories.
- Replace implementation-detail assertions with Product Brief behavior.
- Delete `qa/` only after equivalent relevant tests pass.

**Acceptance**

- No useful inherited critical assertion is silently lost.
- No single feature test file becomes a second monolith.
- The custom `qa/` directory no longer exists.

### TF-102 — Factories and deterministic seeders

**Dependencies:** TF-100

**Actions**

- Add Project, ProjectMember, Task, TaskComment, inherited attachment, and later-ready factory states.
- Make `DatabaseSeeder` call `RolePermissionSeeder`.
- Create explicit local/demo admin, project manager, and member accounts only under safe environment rules.
- Remove generic unroled `test@example.com` seeding.
- Add enum-based role/permission factory helpers.

**Acceptance**

- Tests use factories rather than repeated manual model construction.
- Seeded local data produces a usable active admin and canonical roles.
- Production cannot be silently seeded with known demo credentials.

### TF-103 — Architecture guard tests

**Dependencies:** TF-100

**Actions**

- Detect Eloquent/DB/Storage in controllers.
- Detect repositories/Eloquent in approved Livewire components.
- Detect raw Eloquent API returns, unapproved Livewire classes, direct task storage outside Media, catch-all LogicException mapping, route ownership/naming violations, and undocumented module dependencies.
- Add module enable/binding smoke tests.

**Acceptance**

- Guards fail on representative forbidden examples and pass on the actual compliant structure.
- Accepted direct dependencies are allowlisted narrowly, not globally ignored.

---

## Phase 2 — Architecture and inherited correctness repairs

### TF-200 — Clean/reversible inherited schema baseline

**Dependencies:** TF-100, TF-102

**Actions**

- Add missing Activity migration rollback.
- Resolve split `project_members` create/align schema through an approved additive or pre-production squash strategy.
- Audit foreign-key delete behavior, nullability, unique/composite indexes, soft deletes, and enum-string columns.
- Prove all current migrations on SQLite; run approved MySQL compatibility separately.

**Acceptance**

- Every migration has a safe `down()` path.
- Fresh SQLite schema matches documented inherited baseline.
- Upgrade path preserves existing data when persistent data is in scope.

### TF-201 — Module-owned route registration

**Dependencies:** TF-103

**Actions**

- Move Projects, Tasks, Activity, and Dashboard APIs from root `routes/api.php` to module `routes/api.php` files.
- Register API prefix/middleware/name prefix exactly once in each provider.
- Keep only host authentication APIs in root.
- Standardize controller namespaces to `Http/Controllers/Web` and `Http/Controllers/Api/V1` when moving files; preserve casing on Linux.
- Name all Web/API routes.

**Acceptance**

- Static and runtime route inventories contain no duplicate method/path/name.
- Every business route is owned by its module.
- Auth/ability/policy middleware remains correct after the move.

### TF-202 — Repository and query boundaries

**Dependencies:** TF-103

**Actions**

- Move interfaces under `Repositories/Contracts` and Eloquent classes under `Repositories/Eloquent`; use `*RepositoryInterface`.
- Add missing host User repository/query boundary if needed to remove controller Eloquent lookups.
- Move direct Project/User lookups out of Task controllers/services into approved query/repository services.
- Create validated Web Project/Task index Requests; remove `request()->all()`.
- Centralize visibility/filter-option scopes and whitelist sorting/pagination.

**Acceptance**

- Controllers contain no Eloquent queries.
- Web/API/Livewire filter options use the same visibility scope.
- Provider binds every interface to exactly one implementation.

### TF-203 — Purpose-specific DTO and date normalization

**Dependencies:** TF-202

**Actions**

- Split ProjectData into CreateProjectData, UpdateProjectData, ProjectFiltersData, and ChangeProjectStatusData.
- Normalize Task create/update/filter/assign/status DTOs; add reorder/label/watcher DTOs when their phases start.
- Lock Task priority input and ordering to `low < medium < high < urgent` across DTOs, validation, filters, sorting, Resources, and UI.
- Convert validated business dates to immutable values.
- Remove unused DTOs or make them part of real flows; do not retain decorative classes.

**Acceptance**

- Every mutation DTO is constructed from validated input only.
- Web/API/Livewire pass the same DTO type to the same service.
- Invalid dates/enums/priorities/sorts cannot reach domain/query code.

### TF-204 — Domain exceptions and response mapping

**Dependencies:** TF-201

**Actions**

- Replace generic LogicException business rules with purpose-specific exceptions and stable machine codes.
- Remove the catch-all LogicException-to-409 renderer.
- Map only documented conflicts to 409/field errors; keep authorization 403 and unexpected failures 500.
- Provide equivalent Web session errors and API JSON without leaking internals.

**Acceptance**

- Expected domain violations never return 500.
- Unexpected LogicException is not disguised as 409.
- API/Web tests lock status and safe error shapes.

### TF-205 — Authorization, visibility, and immutable-project matrix

**Dependencies:** TF-202, TF-204

**Actions**

- Align global permissions with independent project-context manager/member policy decisions.
- Change task visibility from assigned-only to all project members.
- Give project members report/comment/upload/watch capabilities while keeping manager/reporter/assignee ownership rules.
- Remove policy boolean bypass parameters.
- Add one reusable project-mutability invariant to every Project/Task/Comment/Watcher/Label/Media mutation service.
- Lock role x membership x actor-relation x project-state matrix in datasets.

**Acceptance**

- Context manager capability works regardless of compatible global member role.
- Non-members see no project/work/filter/activity metadata.
- Completed/Archived project mutations fail identically across entry points and direct services.
- Token ability never bypasses permission/policy.

---

## Phase 3 — Accounts, Web authentication, and Sanctum

### TF-300 — Internal user lifecycle

**Dependencies:** TF-102, TF-204, TF-205

**Actions**

- Keep Admin User Management as core because public registration is disabled.
- Add active/suspended account state, suspend/reactivate actions, last-active-admin guard, and token revocation on suspension.
- Make suspension independent of open assignments: revoke sessions/tokens, unassign open work, remove watcher subscriptions, and preserve safe historical Activity.
- Add admin password reset and authenticated self-service password change with the invalidation/session-regeneration rules in the Product Brief.
- Normalize email and preserve historical user references; do not hard-delete users.
- Keep global role changes independent from project roles and expose consequences clearly.

**Acceptance**

- Only admins manage accounts/global roles.
- Suspended users cannot log in or use old/new tokens.
- Final active admin cannot be demoted/suspended.
- Admin reset and self-change enforce their current-password, authorization, token, and session rules.
- Secrets/password hashes never appear in pages, logs, resources, or activity.

### TF-301 — Stabilize session authentication

**Dependencies:** TF-300

**Actions**

- Preserve credential validation, remember behavior, five-attempt throttle, session regeneration, logout invalidation, and CSRF regeneration.
- Reject suspended users before session establishment and on protected requests as documented.
- Use a dedicated guest layout and safe generic failure messages.
- Keep registration absent.

**Acceptance**

- Guest/authenticated/suspended/throttled/logout scenarios pass feature tests.
- No session fixation or stale suspended session remains accepted.

### TF-302 — Credential-to-token API

**Dependencies:** TF-300, TF-301

**Actions**

- Implement CreatePersonalAccessTokenData, AuthenticationService, token Form Request, AuthenticationController, and AuthenticatedUserResource.
- Add POST `/api/v1/auth/token`, GET `/api/v1/me`, DELETE current token.
- Validate device name and canonical ability list; enforce dedicated rate limit.
- Return plaintext only once and revoke current token with 204.
- Remove/retire inherited authenticated `/tokens` bootstrap endpoints unless a separately accepted requirement exists.

**Acceptance**

- Correct 201/401/403/422/429/204 behavior.
- Revoked and suspended tokens fail immediately.
- Plaintext/token hash is absent from database plaintext, logs, Activity, Resources, and persistent UI state.

### TF-303 — Auth/admin audit and security pass

**Dependencies:** TF-300, TF-302

**Actions**

- Add account/token audit events with sanitized payloads.
- Verify rate-limit keys, safe errors, mass assignment, hidden fields, and final-admin concurrency.
- Add full role/ability/account-state dataset tests.

**Acceptance**

- No auth/admin Critical or High issue remains.
- Account/token actions are traceable without secrets.

---

## Phase 4 — Projects completion

### TF-400 — Project key, lifecycle, and sequence schema

**Dependencies:** TF-200, TF-203, TF-204, TF-205

**Actions**

- Add unique uppercase project key and project-local next issue sequence.
- Backfill keys deterministically for inherited projects with collision handling/report.
- Implement Draft -> Active -> Completed -> Active and archive transitions per Product Brief.
- Make key immutable after first issue allocation.
- Add safe old/new activity payloads.

**Acceptance**

- Key validation/uniqueness/immutability and lifecycle transition tables pass.
- Completed/Archived read-only rules are service-level invariants.
- Backfill is reversible/verified under the selected data strategy.

### TF-401 — Membership roles and removal integrity

**Dependencies:** TF-205, TF-300, TF-400

**Actions**

- Add project-role update use case/API/Web flow.
- Make add/update/remove transactionally include activity.
- Preserve owner manager membership and prevent owner removal/demotion.
- Block member removal with open assignments and report actionable conflict counts.
- Remove project watcher subscriptions on successful removal while preserving history.
- Scope available-user/member queries.

**Acceptance**

- Duplicate, owner, role, open-assignment, archived/completed, and outsider cases pass.
- Context manager permissions behave consistently.
- No membership partial failure leaves contradictory data/activity.

### TF-402 — Projects Web/API presentation

**Dependencies:** TF-201, TF-203, TF-400, TF-401

**Actions**

- Complete validated/scoped project list, create, detail, edit, status, members, and empty/error states.
- Show key, lifecycle, member/task summary, latest activity, and authorized actions.
- Implement target Project/Member Resources and API routes.
- Reuse forms/components and keep Blade query-free.

**Acceptance**

- Web/API call identical project/member services.
- Exact API statuses/envelopes and policy outcomes pass.
- Responsive/manual project flow checklist is ready for final gate.

---

## Phase 5 — Central Media

### TF-500 — Scaffold and register Media module

**Dependencies:** TF-200, TF-201, TF-202

**Actions**

- Create `Modules/Media` with provider, migration, model, repository interface/Eloquent implementation, DTO/value helpers, services, Resources, and tests only as needed.
- Add `media` schema from `MEDIA.md` with private metadata and indexes.
- Register module status/provider and module-owned tests/migrations.

**Acceptance**

- Media module boots independently in the monolith and owns no Task authorization.
- No speculative thumbnail/scanner/queue abstraction is created.

### TF-501 — Secure Media storage and streaming services

**Dependencies:** TF-203, TF-204, TF-500

**Actions**

- Implement randomized private paths, server-side extension/MIME pairing, size/count/image-dimension limits, checksum, metadata, safe filename, preview/download response, and physical cleanup.
- Reject SVG/executable/unknown types.
- Add compensation/after-commit behavior and failure-path tests.

**Acceptance**

- Spoofed MIME/extension, oversized count/file/image, path/header injection, missing file, and storage/DB failure tests pass.
- Resources/activity never expose disk/path/checksum.

### TF-502 — Migrate inherited task attachments

**Dependencies:** TF-501

**Actions**

- Add/backfill `media_id` through the migration sequence in `MEDIA.md` or use an explicitly approved disposable-data clean schema.
- Convert Tasks attachment repository/service/model into a task/media association boundary.
- Preserve authorized downloads and safe metadata during migration.

**Acceptance**

- Record/file counts and downloads match before/after for preserved data.
- No orphan file/record or inaccessible association is introduced.
- Old disk/path fields are not removed before verification.

### TF-503 — Task media Web/API flows

**Dependencies:** TF-502, TF-205

**Actions**

- Implement multiple upload, list, preview, download, and delete through Task authorization plus Media services.
- Enforce uploader/manager delete rules and project mutability.
- Use target task media API endpoints/Resources and canonical activity.

**Acceptance**

- Cross-task/media tampering returns 404.
- Non-member/unauthorized/read-only project cases pass.
- Up to five valid files work; one invalid file rejects the whole request and compensates every file/record created by that request.

---

## Phase 6 — Minimal Jira work-item domain

### TF-600 — Project-local issue allocation

**Dependencies:** TF-400, TF-202

**Actions**

- Add project sequence/display-number fields and unique indexes.
- Allocate `KEY-N` atomically with row locking and no committed nullable number.
- Backfill inherited `TSK-*` records deterministically and preserve an audit/report mapping if data exists.
- Update routes/search/Resources/UI to use stable internal ID for binding and display key for humans.

**Acceptance**

- Parallel/rollback tests produce no duplicate, skipped-commit inconsistency, or null display number beyond documented sequence gaps.
- Two projects can each have sequence 1 but display keys remain globally unique.

### TF-601 — Work types and one-level subtasks

**Dependencies:** TF-600, TF-203

**Actions**

- Add TaskType enum and nullable parent foreign key/index.
- Support Task, Bug, Story, Subtask in create/update/filters/Resources/UI.
- Enforce same-project standard parent, one-level hierarchy, no cycles, and open-subtask completion guard.
- Eager-load parent/subtask summaries without N+1.

**Acceptance**

- Cross-project parent, subtask-of-subtask, missing parent, type-change invalidation, and parent completion conflicts pass.
- Parent/subtasks may have different valid assignees.

### TF-602 — Work-item visibility, report, edit, and delete rules

**Dependencies:** TF-205, TF-401, TF-600, TF-601

**Actions**

- Allow project members to browse all project work and report work in Active projects.
- Implement manager edit/delete and reporter early-state edit rules.
- Keep non-members excluded from lists, filters, details, activity, board, and counts.
- Standardize create/update/delete transactions and safe old/new activity.

**Acceptance**

- Role/project-role/reporter/outsider/state matrix passes Web/API/direct-service tests.
- Soft-deleted work remains auditable and absent from normal lists.

### TF-603 — Single-assignee rules

**Dependencies:** TF-602, TF-401

**Actions**

- Preserve nullable single `assignee_id`; do not add an assignee pivot.
- Allow member self-assignment and manager assignment of any project member.
- Validate membership and read-only project state inside service.
- Assignment auto-watches the new assignee and sends one notification; record old/new safe activity.

**Acceptance**

- Foreign/non-member/removed/suspended assignee, unauthorized other-user assignment, no-op, unassign, and notification de-duplication tests pass.

### TF-604 — Fixed workflow and timestamps

**Dependencies:** TF-602, TF-603

**Actions**

- Add Backlog status and implement exact Product Brief transition map.
- Apply assignee/manager transition authority and project mutability.
- Preserve started/completed semantics and parent open-subtask guard.
- Add optimistic conflict input for board/status writes.

**Acceptance**

- Dataset tests cover every from/to/actor/state combination.
- Web/API/Livewire/board call the same TaskStatusService.
- Stale status write returns documented conflict and does not duplicate activity/notification.

### TF-605 — Project-scoped labels

**Dependencies:** TF-400, TF-602

**Actions**

- Add Task-owned label and pivot schemas, enum/value color validation, repositories, manager CRUD service, and task sync service.
- Add project label UI/API and task create/edit/filter/resource integration.
- Enforce project scope and read-only lifecycle.

**Acceptance**

- Duplicate slug/name, invalid color, foreign-project label, unauthorized CRUD/sync, deletion detach, and filter tests pass.
- No generic category or Component model is added.

### TF-606 — Watchers and in-app notifications

**Dependencies:** TF-300, TF-401, TF-602, TF-603

**Actions**

- Add watcher pivot and host database-notification migration/model flows.
- Implement self watch/unwatch, manager watcher management, auto-watch reporter/assignee, and membership-removal cleanup.
- Notify distinct eligible watchers on assignment, comment, and status change; exclude actor.
- Add Web notification list/unread count/mark read/mark all read with reauthorized links.

**Acceptance**

- Only project members watch; watcher grants no mutation authority.
- Removed/suspended/non-member users receive no new notification/access.
- Retry/repeated no-op actions do not produce duplicate notifications for the same domain action.

### TF-607 — Ranked backlog

**Dependencies:** TF-604

**Actions**

- Add project-local rank/position and Backlog query service.
- Implement project-manager-only neighbor-based reorder with locking/optimistic conflict and deterministic rebalance.
- When an assignee performs an allowed status transition, place only that work item at the end of the target column; do not grant general reorder authority.
- Default new work to Backlog at the documented position.
- Add Web/API backlog presentation and filters.

**Acceptance**

- Cross-project neighbor, duplicate rank, concurrent reorder, stale write, non-manager explicit reorder, assignee status-move placement, and pagination/filter interactions pass.
- Priority and rank remain separate concepts.

### TF-608 — Kanban board

**Dependencies:** TF-604, TF-607

**Actions**

- Build project board read model grouped by fixed statuses.
- Add accessible server-rendered columns/cards and progressive drag/drop.
- Route every move through status/reorder services with expected version.
- Provide keyboard/form fallback and filter state.

**Acceptance**

- JS cannot bypass workflow/policy/project state.
- Board includes only the selected visible project and eager-loaded card data.
- Drag/drop conflict recovers visibly; keyboard/JS-disabled transition remains usable.

### TF-609 — Search and filters

**Dependencies:** TF-601, TF-605, TF-606, TF-607

**Actions**

- Implement validated TaskFiltersData for key/text/project/types/statuses/priorities/assignee/reporter/labels/parent/due/overdue/signed sort/page.
- Reuse one scope for Web/API/Livewire/backlog/board filter options.
- Add indexes justified by actual queries.
- Keep database search simple; do not add external/full-text infrastructure without evidence.

**Acceptance**

- Unknown/tampered sort/filter returns safe 422/default per channel.
- No inaccessible project/user/label/key leaks through options or counts.
- Shared URL restores filter state.

### TF-610 — Comments business completion

**Dependencies:** TF-602, TF-606

**Actions**

- Enforce project-member visibility, Active-project mutability, 5,000-character plain text, author/manager delete, nested binding, activity, watcher notifications, and safe rendering.
- Standardize repository/service/API Resources.

**Acceptance**

- Cross-task, outsider, read-only project, XSS string, double-submit/no-op notification, author/manager delete, and activity sanitization pass.

---

## Phase 7 — UI, Livewire, and progressive JavaScript

### TF-700 — Shared layout and component system

**Dependencies:** TF-402, TF-503, TF-608, TF-609, TF-610

**Actions**

- Separate guest/app layouts and reusable navigation/header/flash/error/badge/button/modal/media components.
- Make navigation permission-aware and active-route-aware.
- Remove unused Laravel welcome view/assets.
- Ensure responsive table/card/board layouts and visible focus.

**Acceptance**

- No duplicated create/edit form families without justification.
- Mobile/desktop/keyboard structures pass component/manual checks.

### TF-701 — Livewire TaskFilters

**Dependencies:** TF-609, TF-700

**Actions**

- Implement all approved filters, signed sort, pagination, URL state, search debounce, page reset, loading/empty/error states.
- Authorize and call Task query service; never inject repository/Eloquent.

**Acceptance**

- Shared URLs restore state and unrelated options never render.
- Component and Web/API parity tests pass.

### TF-702 — Livewire TaskStatusSelector

**Dependencies:** TF-604, TF-700

**Actions**

- Render service-provided available statuses, authorize each action, submit ChangeTaskStatusData with optimistic version, and show loading/conflict/success state.

**Acceptance**

- Tampered/invalid/manager-only/read-only/stale transitions fail through canonical backend rules.

### TF-703 — Livewire TaskCommentForm

**Dependencies:** TF-610, TF-700

**Actions**

- Validate/authorize/comment via TaskCommentService, reset errors/input, refresh list, and block double-submit.

**Acceptance**

- Comment appears without page refresh and creates one comment/activity/notification action.

### TF-704 — Livewire QuickTaskCreate

**Dependencies:** TF-601, TF-605, TF-602, TF-700

**Actions**

- Support dashboard project selection and fixed project context, type, title, priority, optional self/manager assignee, labels, and default Backlog creation.
- Refresh project-scoped options and enforce service invariants.

**Acceptance**

- Foreign project/user/label/parent and read-only project tampering fail.
- Standard Web/API/Livewire create paths produce equivalent data/activity/watch behavior.

### TF-705 — Focused vanilla JavaScript

**Dependencies:** TF-503, TF-608, TF-700

**Actions**

- Add accessible modal, confirmations, media preview selection, character counters, issue-key/token copy, and board drag/drop.
- Use CSRF-safe requests and visible conflict/error recovery.
- Remove token plaintext from DOM when the one-time view closes; never persist it in console/storage.

**Acceptance**

- Core forms remain functional/safe without JS.
- Keyboard, focus return, Escape, mobile, copy, preview, and board behaviors pass Playwright/manual checks.

---

## Phase 8 — Activity, notifications, and Dashboard

### TF-800 — Canonical Activity schema and scoped queries

**Dependencies:** TF-400 through TF-610

**Actions**

- Replace raw event strings with canonical enum/constants.
- Version a safe payload convention with approved old/new values and recursive sensitive-key filtering.
- Make Activity filters portable across SQLite/MySQL.
- Align visibility with project-member browse rules and add user/admin/media/label/watcher/rank events.
- Complete global/project/task Web/API routes and human-readable display.

**Acceptance**

- Every documented business mutation creates exactly one canonical event.
- Filter ID tampering leaks no metadata.
- Password/token/path/checksum/content/secrets are absent.

### TF-801 — Notification center completion

**Dependencies:** TF-606, TF-700

**Actions**

- Complete unread badge/list, mark one/all read, safe summaries, pagination, and authorized target links.
- Ensure action recipient calculation is centralized and testable.

**Acceptance**

- Reading a stale/deleted/inaccessible target does not disclose it.
- Counts/read state and recipient rules pass.

### TF-802 — Dashboard metrics and work queues

**Dependencies:** TF-609, TF-800, TF-704

**Actions**

- Rework metrics for new visibility/workflow/types/project states.
- Add My Assigned, Reported by Me, My Watched Work, overdue, completed today, recent activity, project status/type distribution, and QuickTaskCreate.
- Reuse canonical scopes and avoid N+1/repeated aggregate drift.
- Complete summary/my-tasks/reported/watched/overdue API Resources.

**Acceptance**

- Admin/context manager/member counts/lists match list/board scopes.
- Empty states, date/timezone semantics, query-budget review, and API/Web parity pass.

---

## Phase 9 — API contract completion

### TF-900 — Resource, route, and ability matrix

**Dependencies:** TF-302, TF-402, TF-503, TF-600 through TF-610, TF-800, TF-802

**Actions**

- Implement every target route in `API_CONVENTIONS.md` with module ownership and `api.v1.*` name.
- Standardize Resources/envelopes/meta/statuses, nested 404s, domain codes, signed sort/filter arrays, and private-field absence.
- Test every protected route for authentication and required ability, then record policy/validation success/failure.
- Remove inherited route aliases not in the target contract.

**Acceptance**

- Exact static/runtime endpoint matrix has no missing/duplicate/obsolete operation.
- Web/API/Livewire share services and business outcomes.
- Every route has explicit auth/ability/policy/validation/resource tests.

### TF-901 — API reference and manual collection

**Dependencies:** TF-900

**Actions**

- Update API/Postman checklist from actual routes.
- Add a versioned OpenAPI file or importable Postman collection if explicitly approved; it must be generated/reviewed against runtime route inventory.
- Document examples without real credentials/tokens.

**Acceptance**

- Documentation method/path/body/status matches contract tests and runtime routes.
- No sample contains a valid secret.

---

## Phase 10 — Stabilization and release acceptance

### TF-1000 — Complete risk-based Pest suite

**Dependencies:** all implementation tasks through TF-900

**Actions**

- Close every test item in `TEST_STRATEGY.md` and requirement traceability.
- Add architecture, domain, repository, Web, API, Livewire, security, storage-failure, activity, notification, and dashboard suites.
- Run full SQLite suite and approved MySQL compatibility.

**Acceptance**

- Every Critical/High risk has explicit passing tests.
- No zero-test, skipped-critical, order-dependent, or flaky behavior remains.
- Results/runtime are recorded in `TASKS.md`.

### TF-1001 — Add and stabilize Playwright E2E

**Dependencies:** TF-700 through TF-705, TF-1000

**Actions**

- With explicit approval, add locked `@playwright/test`, configuration, deterministic test setup, and scripts.
- Implement only the critical journeys in `TEST_STRATEGY.md`.
- Use stable accessible locators/test IDs only where semantics are insufficient.
- Capture traces/screenshots on failure without secrets.

**Acceptance**

- Critical desktop/mobile/JavaScript journeys pass reliably.
- E2E does not duplicate exhaustive backend validation matrices.
- Browser artifacts contain no plaintext token/password/private media path.

### TF-1002 — Quality, performance, and baseline security gate

**Dependencies:** TF-1000, TF-1001

**Actions**

- Run Pint, Composer validation, full Pest, frontend build, Playwright, route/architecture checks, dependency audit if approved, N+1/query review, and selected MySQL verification.
- Review mass assignment, CSRF/XSS, IDOR, rate limits, token secrecy, account suspension, media headers/MIME, activity/notification sanitization, indexes/constraints, and safe errors.
- Fix all Critical/High findings; record accepted lower risks.

**Acceptance**

- No open Critical/High issue.
- Checks are green or explicitly blocked with owner/reason; blocked critical checks prevent completion.

### TF-1003 — Manual Web/API acceptance

**Dependencies:** TF-901, TF-1002

**Actions**

- Execute `MANUAL_BROWSER_CHECKLIST.md` for admin, project manager, member, outsider/suspended contexts on desktop/mobile and core JS-disabled paths.
- Execute `POSTMAN_API_CHECKLIST.md` against an approved local/test environment.
- Record date, environment, actor, failures, and retest evidence.

**Acceptance**

- Every checklist item passes or has an explicitly accepted non-critical exception.
- No production stress/destructive action or real secret enters artifacts.

### TF-1004 — Final documentation and handoff

**Dependencies:** TF-1003

**Actions**

- Reconcile README, Product Brief, Decisions, Architecture, Media, API, Security, Tests, and task evidence with verified code.
- Mark all implementation tasks verified only from evidence.
- Create a new dated release handoff; do not revive old claims.
- Confirm roadmap remains deferred and direct-dependency debt is documented.

**Acceptance**

- A new agent can explain product rules, layers, authorization, media, tests, current limitations, and next roadmap gate without reading stale material.
- Product Definition of Completeness is fully satisfied.

---

## Final Definition of Done

The implementation plan is complete only when:

- the full Product Brief is implemented and no explicit non-goal slipped into scope;
- all P0/P1 Current State Audit findings are closed;
- central Media owns every file/image path and binary lifecycle;
- one-assignee/watcher, project-member visibility, project-local keys, work types/subtasks, labels, backlog, board, notifications, and filters behave as specified;
- Web/API/Livewire reuse service rules and return channel-appropriate responses;
- every module owns routes/migrations/views/tests and accepted direct dependencies are documented;
- Pest, Playwright, build, formatting, architecture, security, and approved DB compatibility gates pass;
- manual Web/API acceptance passes;
- docs describe verified reality and the roadmap has not been implemented accidentally.
