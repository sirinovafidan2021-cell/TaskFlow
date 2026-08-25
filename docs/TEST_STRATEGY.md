# Test and quality strategy

## Principle

Tests prove behavior at the cheapest reliable layer. File existence, route counts, and old console output are not completion evidence.

Pest is the primary test framework. Playwright is added for a small number of real-browser journeys after the related UI is stable. Playwright does not replace domain/API/authorization tests.

## Target test layout

```text
tests/
├── Architecture/
├── Feature/
│   ├── Auth/
│   ├── Admin/
│   └── CrossModule/
├── Unit/
└── e2e/                    # Playwright TypeScript specs

Modules/Projects/tests/{Unit,Feature}/
Modules/Tasks/tests/{Unit,Feature}/
Modules/Media/tests/{Unit,Feature}/
Modules/Activity/tests/{Unit,Feature}/
Modules/Dashboard/tests/{Unit,Feature}/
```

The inherited `qa/Regression/TaskFlowRegression.pest` is decomposed into these directories and then removed. Useful assertions are preserved; obsolete implementation assertions are rewritten against the product specification.

## Test database

Default automated tests use:

- SQLite `:memory:`;
- array cache/session/mail;
- sync/fake queues as appropriate;
- fake private storage;
- a deterministic test APP_KEY;
- module migrations discovered by providers/test bootstrap.

MySQL compatibility is a separate, explicitly approved gate for migrations, JSON/activity queries, locking-sensitive issue numbering, and rank operations. Tests must never silently point at a developer or production database.

## Unit tests

Unit/deterministic service tests cover:

- project lifecycle transition table;
- work-item workflow transition table and timestamp semantics;
- manager versus assignee transition authority;
- project-key and issue-number formatting/allocation rules;
- fixed priority validation and ordering;
- label normalization/color rules;
- parent/subtask rules and open-subtask completion guard;
- backlog rank calculations/rebalancing;
- manager-only explicit reorder and assignee target-column-end placement;
- notification recipient de-duplication;
- activity payload sanitization;
- media MIME pair/name/header helpers.

## Repository/integration tests

Repository tests use the database and prove:

- admin/project-member/non-member visibility;
- filter options never leak inaccessible IDs/names;
- Project/Task filters, whitelisted sort, pagination, and eager loading;
- project-local issue number uniqueness and concurrent-safe allocation strategy;
- label/watcher/parent/media foreign-project rejection;
- membership removal/open-assignment behavior;
- constraints, soft deletes, and migration reversibility;
- Activity SQLite/MySQL portability.

## Feature tests

Every Web/API/Livewire mutation needs positive, validation, authorization, state-conflict, and audit assertions where relevant.

Critical matrices:

- global role x project role x reporter/assignee/watcher/outsider;
- Sanctum ability x permission x record policy;
- Draft/Active/Completed/Archived project x mutation channel;
- Task/Bug/Story/Subtask x parent rules;
- comment/media author/uploader x manager x unrelated user.

Account coverage includes admin password reset, self-change current-password confirmation, session/PAT invalidation, suspension with open-work unassignment/watcher cleanup, and last-active-admin concurrency.

Multi-file Media coverage proves request-level all-or-nothing compensation across validation, storage, association, and Activity failures. Dashboard coverage includes My Watched Work while Task filter tests prove arbitrary watcher filtering is absent.

API families cover 401, ability 403, policy 403, nested 404, conflict 409, validation 422, creation 201, and deletion 204.

## Livewire tests

The four approved components require:

- authorized happy path;
- validation/tampering failure;
- service reuse and no direct repository/Eloquent injection;
- loading/double-submit-safe action behavior where testable;
- TaskFilters URL/filter/pagination reset and no option leakage;
- TaskStatusSelector transition parity;
- TaskCommentForm single activity/notification effect;
- QuickTaskCreate project/assignee/type/label rules.

## Architecture tests

Automated source guards detect:

- Eloquent/DB/Storage calls in controllers;
- repositories or Eloquent queries in Livewire components;
- raw business models returned by API controllers;
- unexpected module dependencies outside the documented graph;
- direct task file storage outside Media;
- unapproved Livewire components;
- `request()->all()` in domain/query flow;
- catch-all LogicException-to-409 rendering;
- routes not owned/named by the expected host/module.

## Playwright decision

Playwright is better than a manual-only browser strategy for regressions involving JavaScript, navigation, real cookies/CSRF, responsive layouts, and drag/drop. It is not better than Pest for exhaustive backend rules.

Add `@playwright/test` as an explicitly approved dev dependency and configure a deterministic local test server/database. Do not install browsers or run Playwright until its implementation task authorizes that operation.

Initial browser journeys:

1. Login/logout and unauthorized redirect.
2. Admin creates/suspends a user and the suspended user cannot sign in.
3. Manager creates/activates a project and manages membership.
4. Member reports a Bug, manager assigns it, assignee progresses it through Livewire.
5. Backlog reorder and Kanban drag/drop persist and respect workflow.
6. Label/filter URL state and mobile/desktop task list behavior.
7. Watch/comment notification flow.
8. Multiple media upload, image/PDF preview, private download, and delete.
9. Completed/Archived project UI is read-only.
10. Mobile navigation, keyboard focus, Escape/modal, and JS-disabled core-form smoke.

Keep the E2E suite small and stable. Backend edge cases remain in Pest.

## Quality commands and evidence

The implementation plan will introduce canonical scripts for:

- full Pest suite;
- focused module suite;
- Pint check;
- frontend build;
- Playwright E2E;
- static route/architecture verification;
- optional approved MySQL compatibility.

Commands are evidence only when recorded with result, runtime, and date in `TASKS.md`. Skipped checks require reasons.

## Definition of test completeness

- Clean dependency/setup can discover nonzero tests without MySQL.
- Every Critical/High business/security rule maps to an explicit test name.
- Web/API/Livewire parity exists for shared mutations.
- Playwright covers only the agreed critical journeys.
- No flaky or order-dependent test is accepted.
- No test depends on production credentials, `.env`, real external services, or persistent developer data.
