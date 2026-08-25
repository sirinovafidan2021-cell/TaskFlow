# AI-agent implementation workflow

## Purpose

This workflow tells an AI coding agent how to implement TaskFlow safely without inventing scope, trusting stale completion claims, or mixing roadmap work into the core release.

## Mandatory reading order

Before changing code, read these files completely:

1. root `AGENTS.md`;
2. `docs/README.md`;
3. `docs/PROJECT_BRIEF.md`;
4. `docs/DECISIONS.md`;
5. `docs/CURRENT_STATE_AUDIT.md`;
6. `docs/ARCHITECTURE.md`;
7. `docs/SECURITY.md` and `docs/MEDIA.md` when relevant;
8. `docs/API_CONVENTIONS.md` and `docs/TEST_STRATEGY.md` when relevant;
9. the selected task in `docs/IMPLEMENTATION_PLAN.md`;
10. current status/evidence in `docs/TASKS.md`.

Read `ROADMAP.md` only to protect the boundary: do not implement it during the core plan.

## One-task execution cycle

### 1. Select

- Select the earliest `pending` task whose dependencies are `verified`.
- Confirm no other task is `in_progress`.
- Change only that task to `in_progress` and record the intended verification commands.
- If task order must change, update dependencies/reasoning before editing code.

### 2. Inspect

- Trace the existing route/controller or Livewire component/service/repository/policy/model/migration/test path.
- Search all call sites before changing a public signature or schema.
- Check module providers, policies, API resources, seeders/factories, and both positive and negative tests.
- Separate inherited behavior from target behavior; `PROJECT_BRIEF.md` and `DECISIONS.md` win over old code/docs.
- Record unrelated user/worktree changes and preserve them.

### 3. Design the smallest complete slice

- Keep controllers and approved Livewire components thin.
- Put transactions and business rules in the owning service.
- Put reusable/scoped reads in repositories or query objects.
- Put authorization in policies plus project-context rules, with services rechecking invariants.
- Use DTOs/value objects at boundaries; never pass raw request arrays into services.
- Use documented direct module service calls. Do not introduce roadmap contracts/events prematurely.
- Route every binary/file/image through Media.

### 4. Implement

- Honor module ownership for routes, migrations, views, tests, and domain code.
- Preserve the Web/API response boundary: services do not return redirects, JSON, or views.
- Make schema changes reversible and define preservation/backfill before destructive transformation.
- Dispatch external side effects only after successful persistence where applicable.
- Add/update automated tests in the same task; do not postpone correctness coverage to Phase 10.

### 5. Verify proportionally

Run the narrowest relevant checks first, then the broader task gate:

- targeted Pest unit/feature/architecture tests;
- formatting/static checks used by the repository;
- frontend build and relevant component checks;
- Playwright only for tasks that establish or change an approved browser journey;
- approved MySQL/production-like compatibility when schema/query behavior depends on it.

Do not claim a command ran if dependencies/runtime are absent. Record the exact blocker and perform all safe static checks still possible.

### 6. Review against business invariants

Before closing a task, explicitly check:

- organization/project role and membership visibility;
- active versus completed/archived mutation rules;
- reporter versus single assignee versus multiple watchers;
- project-local issue key integrity;
- one-level subtask rule;
- private Media authorization and storage safety;
- activity/notification side effects;
- Web/API behavioral parity;
- cross-project information leakage and negative cases.

### 7. Record evidence and close

- Add an evidence entry to `TASKS.md` with exact checks/results and remaining risk.
- Change the task to `verified` only when every acceptance item passes.
- If genuinely blocked, set `blocked` and record the repeated condition, checks attempted, and required outside action.
- Leave unrelated tasks pending.
- Update canonical docs when verified behavior or a deliberate decision changed; do not create duplicate planning notes.

## Approval boundaries

The agent must not assume authorization to:

- install/update dependencies or regenerate lock files;
- run migrations, seeders, destructive database resets, or mutate real data;
- run Git history/branch/commit/push operations;
- start services or Playwright/browser automation;
- delete or mass-move files;
- change roadmap/product scope.

The active task must authorize these, and any destructive action needs explicit target verification and rollback/preservation planning.

## Failure handling

- A failing test is evidence, not permission to weaken an assertion.
- Fix the production behavior if the test represents the approved rule; fix the test if it contradicts the approved rule.
- Do not replace errors with generic success/fallback behavior.
- Map expected domain conflicts to documented responses; unexpected exceptions remain visible to logs/monitoring.
- Never convert every `LogicException` or runtime error into HTTP 409.
- If existing data prevents a migration, stop before destruction and design a verified backfill/reconciliation task.

## Agent completion report

Every completed task report should state:

- outcome and TASK-ID;
- behavior/schema/API changed;
- files or modules changed;
- tests/checks run with results;
- unrun checks and why;
- migration/rollback or data implications;
- remaining known risks and the next unblocked TASK-ID.

## Final release rule

Only TF-1004 may call the product implementation complete. It requires the full Final Definition of Done, not merely a green subset of tests or working happy path.
