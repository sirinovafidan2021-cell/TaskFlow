# TaskFlow requirement-to-code traceability matrix

## Purpose and status

This is the TF-002 traceability source for approved implementation work. It maps target requirements to the implementation-plan task that owns delivery, the intended layer/file area, and the test evidence required before that task can become verified. It is not a second implementation plan and does not change the product specification.

Current code is not completion evidence. A row remains pending until its owner task has recorded verification in TASKS.md. TF-002 verifies this matrix's ownership and dependency baseline only; it does not verify later implementation work.

## Product rules

| ID | Approved requirement | Owner task(s) | Target layer / file area | Required evidence | Status |
| --- | --- | --- | --- | --- | --- |
| PR-01 | One internal organization; no workspace or multi-tenancy. | TF-200, TF-1000 | host configuration, migrations, architecture guards | migration/architecture tests | pending |
| PR-02 | No public registration; internal account, role, password, and suspension lifecycle. | TF-300–TF-303 | app HTTP/services, User policies, auth tests | Auth/Admin security datasets | pending |
| PR-03 | Global roles remain distinct from project manager/member roles. | TF-102, TF-205, TF-300 | role seeders/enums, Project policies | role × project-role matrix | pending |
| PR-04 | Project name, slug, uppercase key, owner, memberships, dates, lifecycle, local sequence. | TF-400–TF-402 | Modules/Projects models, migrations, DTOs, services, Resources | Project unit/feature/API tests | pending |
| PR-05 | Draft → Active → Completed → Active and archive lifecycle; only Active mutates and archive is terminal. | TF-400, TF-205 | lifecycle service/policy, mutability invariant | lifecycle/state parity datasets | pending |
| PR-06 | Owner remains manager; removal blocks open assignments and removes project watchers. | TF-401 | membership service/repository; Tasks query | transaction/integrity tests | pending |
| PR-07 | Project key is immutable after allocation; issue number is transactional project-local KEY-N. | TF-400, TF-600 | sequence migration/repository, Task allocation service | allocation/concurrency/backfill tests | pending |
| PR-08 | Work has one project/reporter, zero-or-one assignee, fixed priority/status/rank/due date. | TF-600, TF-602–TF-604 | Modules/Tasks model, migrations, DTOs, services | domain and feature datasets | pending |
| PR-09 | Project membership grants browse access; assignment controls responsibility/transition, not visibility. | TF-205, TF-602 | Task visibility query and policies | member/outsider visibility tests | pending |
| PR-10 | Members report Active-project work; reporters only early-edit; managers edit/delete all mutable work. | TF-602 | Task requests/controllers/services/policies | Web/API/service state matrix | pending |
| PR-11 | Types are Task, Bug, Story, Subtask; one-level same-project standard parent; open children block Done. | TF-601, TF-604 | type/parent migration, service, query/Resource | parent/cycle/cross-project tests | pending |
| PR-12 | Exact Backlog/Todo/In Progress/Review/Done/Cancelled workflow and date semantics. | TF-604 | TaskStatusService, status DTO/request | transition/timestamp/conflict tests | pending |
| PR-13 | Server-owned initial backlog/rank; manager-only reorder; assignee move ends target column. | TF-607, TF-608 | rank repository/service, backlog/board UI | rank/authority/stale-write tests | pending |
| PR-14 | Project labels are normalized, unique, scoped, manager-owned, and cross-project safe. | TF-605 | label/pivot migrations, services, Resources | CRUD/sync/filter tests | pending |
| PR-15 | Project-member watchers are notification-only; auto-watch and removal cleanup rules apply. | TF-606 | watcher pivot/service, host notifications | watcher/recipient/de-dup tests | pending |
| PR-16 | Comments are Active-project, plain text (5,000 max), author/manager deletable, safely audited. | TF-610 | comment request/service/repository/Resource | authorization/XSS/nested tests | pending |
| PR-17 | Private Media owns file metadata/storage; Tasks owns explicit task/media association/authorization. | TF-500–TF-503 | Modules/Media and Tasks association services | storage/IDOR/association tests | pending |
| PR-18 | Media allowlist, size/count/dimension limits, safe previews/downloads, and atomic multi-file compensation. | TF-501, TF-503 | Media validator/storage/stream services | MIME/limit/header/failure tests | pending |
| PR-19 | Canonical scoped Activity with safe approved old/new payloads. | TF-800 | Modules/Activity recorder/query/Resource | event-count/scope/secret tests | pending |
| PR-20 | Dashboard reuses visibility and exposes metrics, assigned/reported/watched/overdue queues and QuickTaskCreate. | TF-704, TF-802 | Dashboard queries/views/Resources, Livewire | parity/aggregate/query-budget tests | pending |
| PR-21 | Web/API/Livewire reuse services; only four approved Livewire components; progressive JS. | TF-103, TF-700–TF-705, TF-900 | guards, Blade/components, Livewire, JS | architecture/component/parity/E2E tests | pending |
| PR-22 | Non-goals remain excluded: sprints, epics, custom fields/workflows, categories/components, dependencies, recurrence, automations, webhooks, integrations, rich text, public media. | TF-1000, TF-1004 | architecture/product-boundary review | architecture/release review | pending |

## API families and contract controls

| ID | API family / contract | Owner task(s) | Target layer / file area | Required evidence | Status |
| --- | --- | --- | --- | --- | --- |
| API-01 | Credential token, current user, current-token revoke; no public registration/bootstrap alias. | TF-302, TF-303, TF-900 | host API routes, auth request/DTO/service/controller/Resource | 201/401/422/429/204 and secret tests | pending |
| API-02 | Scoped projects, lifecycle, project members and member-role routes. | TF-201, TF-400–TF-402, TF-900 | Projects API routes/controllers/requests/Resources | route/ability/policy/status tests | pending |
| API-03 | Tasks, assignment, status, rank, labels, validated filters/sorts/pagination. | TF-201, TF-600–TF-609, TF-900 | Tasks API routes/controllers/query/Resources | 401/403/404/409/422 tests | pending |
| API-04 | Scoped backlog and board read models. | TF-607–TF-609, TF-900 | Tasks API/query Resources | visibility/filter/board tests | pending |
| API-05 | Nested project labels and task watchers with task abilities and safe 404. | TF-605, TF-606, TF-900 | Tasks API controllers/Resources | policy/nested/same-project tests | pending |
| API-06 | Nested plain-text comments with author/manager deletion. | TF-610, TF-900 | Tasks comment API | validation/authorization/nested tests | pending |
| API-07 | Task media list/upload/preview/download/delete through private Media. | TF-500–TF-503, TF-900 | Media + Tasks API routes/controllers/Resources | MIME/IDOR/atomicity/header tests | pending |
| API-08 | Global/project/task Activity scope and safe filters. | TF-201, TF-800, TF-900 | Activity API routes/query/Resources | filter-leak/secret tests | pending |
| API-09 | Dashboard summary, assigned, reported, watched, overdue endpoints. | TF-802, TF-900 | Dashboard API/query/Resources | list/aggregate parity tests | pending |
| API-10 | Module ownership, api.v1 names, abilities, Resources/envelopes/errors, no obsolete aliases. | TF-201, TF-204, TF-900, TF-901 | providers/routes/exception mapping/API docs | static/runtime contract matrix | pending |

## Security, quality, and release gates

| ID | Requirement | Owner task(s) | Target layer / file area | Required evidence | Status |
| --- | --- | --- | --- | --- | --- |
| SQ-01 | Validated input, DTO boundaries, allowlisted filters/sorts, bounded pagination, escaping, safe errors. | TF-202–TF-204, TF-609, TF-610 | requests/DTOs/controllers/components/Resources | validation/XSS/error/guard tests | pending |
| SQ-02 | Session/token/password/suspension safety, rate limits, revocation, no secret persistence. | TF-300–TF-303 | host account/auth/token services | security datasets/secret scans | pending |
| SQ-03 | Policies, abilities, membership scope, nested 404, read-only project parity. | TF-205, TF-401, TF-503, TF-602–TF-610, TF-900 | policies/services/scoped repositories | role/ability/state/IDOR matrices | pending |
| SQ-04 | Reversible SQLite/MySQL migrations, integrity constraints, sequence/rank locking. | TF-100, TF-200, TF-400, TF-600, TF-607 | migrations/repositories | SQLite and approved MySQL checks | pending |
| SQ-05 | Portable Pest, decomposed regression, factories/seeders, architecture guards. | TF-100–TF-103 | phpunit.xml, tests, module tests, factories/seeders | nonzero portable Pest/guard suite | pending |
| SQ-06 | Focused approved browser journeys only after UI stability. | TF-1001 | tests/e2e and Playwright config | approved deterministic E2E run | pending |
| SQ-07 | Final quality, security, manual, documentation, and handoff gates. | TF-1000–TF-1004 | test reports, checklists, canonical docs | recorded gate/manual evidence | pending |

## Current State Audit P0/P1 register

The audit calls its list “Critical correctness and security findings” but does not assign P0/P1 codes. For tracking only, direct authentication, authorization, media, and data-integrity failures are P0; portability, architecture, UI, and test/documentation blockers are P1. This classification does not alter source authority or task order.

| Audit ID | Priority | Finding | Closure owner task(s) | Verification evidence | Status |
| --- | --- | --- | --- | --- | --- |
| AUD-01 | P0 | Authenticated tokens cannot bootstrap; token/me/revoke routes missing. | TF-302 | API auth contract tests | pending |
| AUD-02 | P0 | Password reset and self-change lifecycle absent. | TF-300–TF-303 | account/session/PAT tests | pending |
| AUD-03 | P1 | Token creation is controller-owned. | TF-202, TF-302 | architecture/feature tests | pending |
| AUD-04 | P1 | Business APIs are centralized/root and mostly unnamed. | TF-201, TF-900 | route inventory tests | pending |
| AUD-05 | P0 | Generic LogicException maps unexpected failures to 409. | TF-204 | API/Web error tests | pending |
| AUD-06 | P0 | Context manager is prematurely denied by global permission. | TF-205 | role/project-role matrix | pending |
| AUD-07 | P0 | Task visibility is assigned-only. | TF-205, TF-602 | scoped query/policy tests | pending |
| AUD-08 | P0 | Members cannot report work. | TF-205, TF-602 | reporting tests | pending |
| AUD-09 | P0 | Read-only project rules are inconsistent across Task/comment/media. | TF-205, TF-503, TF-602–TF-610 | state parity tests | pending |
| AUD-10 | P0 | Member removal leaves assignments/watchers unresolved. | TF-401, TF-606 | membership transaction tests | pending |
| AUD-11 | P1 | Policies use boolean bypass parameters. | TF-205 | invariant tests | pending |
| AUD-12 | P0 | Completed project lifecycle incomplete. | TF-400 | lifecycle tests | pending |
| AUD-13 | P0 | Seeder lacks canonical roles and usable admin. | TF-102 | seeder tests | pending |
| AUD-14 | P1 | Project-member split schema weakens portability. | TF-200 | reversible migration tests | pending |
| AUD-15 | P1 | Activity migration lacks down method. | TF-200 | migration-down test | pending |
| AUD-16 | P0 | Nullable global task number is not local/atomic. | TF-600 | allocation/concurrency tests | pending |
| AUD-17 | P1 | Required factories are missing. | TF-102 | factory tests | pending |
| AUD-18 | P1 | Schema lacks Jira-domain/media/notification structures. | TF-500, TF-600–TF-607 | migration/constraint tests | pending |
| AUD-19 | P1 | Membership mutations lack self-contained transactions/activity. | TF-401 | transaction/activity tests | pending |
| AUD-20 | P1 | Update activity lacks safe old/new values. | TF-400, TF-602, TF-800 | payload tests | pending |
| AUD-21 | P1 | Task lookups bypass approved query/repository boundaries. | TF-202 | architecture/controller tests | pending |
| AUD-22 | P0 | Web filters use raw request all input. | TF-202, TF-609 | request/filter tests | pending |
| AUD-23 | P0 | Attachment deletion risks file/database inconsistency. | TF-501–TF-503 | storage failure tests | pending |
| AUD-24 | P0 | Tasks owns physical attachments. | TF-500–TF-503 | Media boundary tests | pending |
| AUD-25 | P1 | Activity JSON query is MySQL-specific. | TF-800 | SQLite/MySQL portability tests | pending |
| AUD-26 | P1 | Dashboard/Activity direct reads need declared temporary coupling. | TF-002, TF-800, TF-802 | TF-002 baseline check; TF-800/TF-802 dependency guards | pending — TF-002 registration complete; runtime closure awaits TF-800/TF-802 |
| AUD-27 | P0 | Default tests require local MySQL. | TF-100 | portable bootstrap tests | pending |
| AUD-28 | P1 | Approved Livewire components are absent. | TF-701–TF-704 | component tests | pending |
| AUD-29 | P1 | Backlog/board/Jira media UI is absent. | TF-503, TF-607–TF-705 | feature/component/E2E tests | pending |
| AUD-30 | P1 | Browser automation is absent. | TF-1001 | Playwright suite | pending |
| AUD-31 | P1 | Regression suite is a nonstandard monolith. | TF-101 | decomposed Pest suite | pending |
| AUD-32 | P1 | Standard tests/source discovery are incomplete/examples remain. | TF-100, TF-103 | discovery/guard tests | pending |
| AUD-33 | P1 | Legacy README/API/test claims are stale. | TF-000, TF-901, TF-1004 | documentation audit | TF-000 verified; remainder pending |

## Accepted direct module dependencies during implementation

| Caller | Direct dependency | Allowed purpose / expected call area | Owner verification | Status |
| --- | --- | --- | --- | --- |
| Projects | Activity | Project lifecycle and membership activity. | TF-401, TF-800 | pending |
| Projects | Tasks | Open-assignment query before membership removal. | TF-401 | pending |
| Tasks | Projects | Project lifecycle, membership, and project context. | TF-205, TF-602 | pending |
| Tasks | Media | Storage/stream/delete only after Task authorization. | TF-500–TF-503 | pending |
| Tasks | Activity | Task/comment/label/watcher/status/rank/media activity. | TF-602–TF-610, TF-800 | pending |
| Activity | Projects, Tasks | Activity visibility mirrors Project/Task visibility. | TF-800 | pending |
| Dashboard | Projects, Tasks, Activity | Visible aggregates and queues only; no mutations. | TF-802 | pending |
| Host account lifecycle | Projects, Tasks, Activity | Suspension coordination and sanitized history. | TF-300, TF-800 | pending |

These are the ADR-009 implementation baseline. Contracts, adapters, event-bus decoupling, and generated dependency-graph refactoring are roadmap-only; they are deliberately excluded from implementation work.

### TF-002 dependency-baseline assessment

The register above was checked against the Architecture dependency graph, the module-responsibility rules, ADR-009, and the implementation-plan owners. All eight registered dependencies are approved implementation-phase dependencies; no additional module, adapter, event bus, or roadmap coupling is introduced by this matrix.

The current temporary coupling is deliberately read-oriented at the presentation/scope boundary: Activity reads Projects and Tasks only to mirror their canonical visibility; Dashboard reads Projects, Tasks, and Activity only for actor-visible aggregates, queues, and recent activity. Dashboard performs no mutations and neither Activity nor Dashboard becomes an independent authorization source. Projects may make the narrowly scoped Tasks query required before membership removal, and the host lifecycle may coordinate Activity when suspending an account. TF-800 and TF-802 own runtime scope/parity tests and dependency guards for AUD-26; TF-002 records and reconciles the baseline without treating those future checks as complete.

## Verification checklist

- Every Product Brief functional rule, explicit non-goal, API family, security control, audit finding, and release gate has an owner.
- Every Current State Audit finding is registered.
- Every direct dependency matches the architecture graph or explicit host lifecycle coordination.
- This matrix never substitutes for per-task evidence in TASKS.md.
