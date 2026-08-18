# TaskFlow Compliance Checklist

## Purpose and status legend

This checklist maps the source-of-truth requirements in `sample/TaskFlow.md` to the implementation plan. It is the completion record for implementation-plan work; it is not a roadmap.

- `missing` — not implemented or not yet verified in this target project.
- `in_progress` — implementation is underway but acceptance evidence is incomplete.
- `verified` — implemented and verified by the relevant task report/checks.

The target keeps the initial, documented direct dependencies `Tasks -> Projects`, `Dashboard -> Projects/Tasks`, and business-service calls to Activity. Contract-based decoupling, CQRS, command buses, and event-sourcing are explicitly deferred and are not part of this Definition of Done.

## Architecture and platform

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Laravel 13, PHP 8.3+, modular monolith using Nwidart | Nwidart prerequisite, 0.1, 1.1 | verified |
| Only Projects, Tasks, Activity, and Dashboard business modules | 0.1, 1.1 | verified |
| Host app retains authentication and `User`; no Api/Web/Auth/Users/Core/Shared module | 0.1, 3.1, 3.2 | missing |
| Blade, Tailwind, vanilla JS, Vite; no React/Vue/Inertia/SPA | 0.1, 7.1, 7.6, 9.4 | missing |
| Livewire limited to the four approved components | 7.2–7.5, 9.4 | missing |
| Route -> validation -> policy -> DTO -> service -> repository -> model flow | 2.1–2.4, 3.3–8.2, 9.4 | missing |
| Thin controllers; services own rules/transactions; repositories own queries/persistence | 2.1–2.4, 4.1–8.2, 9.4 | missing |
| Web, API, and Livewire share service-layer use cases | 3.2, 4.1–8.2, 9.3 | missing |
| Repository interface plus Eloquent implementation for each main aggregate | 2.1, 2.2, 9.2 | missing |

## Infrastructure, schema, roles, and authorization

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Package baseline: Sanctum, Livewire, Spatie Permission, Activitylog, Pest, Pint | Nwidart prerequisite, 0.1, 9.1 | verified |
| Module-owned migrations, providers, Web/API routes, views, and tests | 0.1, 1.1, 1.2, 9.1 | missing |
| Projects/tasks soft deletes; explicit foreign keys, unique constraints, and indexes | 1.2 | missing |
| Portable MySQL/MariaDB/SQLite schema and reversible migrations | 1.2, 9.1 | missing |
| Project/member/task/comment/attachment factories | 1.3 | missing |
| `admin`, `project_manager`, `member` roles and permission seed mapping | 1.3, 3.3 | missing |
| Broad Spatie permissions plus record-level policies | 3.3, 9.2 | missing |
| Member cannot access unrelated project/task/activity information | 2.1, 2.2, 3.3, 9.2, 9.3 | missing |
| Token ability never bypasses policy | 3.3, 6.1–6.4, 9.3 | missing |

## Projects domain and Web surface

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Projects schema: status enum, owner, dates, slug, required indexes | 1.2, 4.1 | missing |
| Project-member schema: ID, role, joined time, foreign keys, uniqueness | 1.2, 4.1 | missing |
| Project list, detail, create, edit, archive, member management | 4.1, 4.2 | missing |
| Detail shows authorized project tasks and recent/member information | 4.2 | missing |
| Create/update/archive/member use cases are transactional and activity logged | 4.1, 8.1 | missing |
| Archived project cannot be updated or receive tasks | 2.4, 3.3, 4.1, 5.1, 9.2 | missing |
| Project metrics/task distribution only exists as a real query use case | 4.1, 8.2 | missing |

## Tasks, comments, and attachments

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Task schema, `TSK-000001` numbering, enums, foreign keys, and indexes | 1.2, 5.1 | missing |
| Task list/detail/create/edit/delete/assignment Web flows | 5.1, 5.2 | missing |
| Actor visibility: admin all, manager managed projects, member assigned tasks | 2.2, 5.1, 5.2, 9.2 | missing |
| Filter/sort/pagination with eager-loaded project, assignee, and creator | 2.2, 2.3, 5.2, 6.1 | missing |
| Status transition map and manager-only reopening | 5.1, 7.3, 9.2 | missing |
| Task creation validates active project, authority, and assignee membership | 2.4, 5.1, 9.2 | missing |
| Comments: scoped routes, policy, service/repository, activity | 5.3, 6.2, 7.4 | missing |
| Attachments: MIME/size validation, private authorized download, safe storage handling | 5.3, 6.2, 7.6, 9.4 | missing |

## API inventory

| Endpoint family | Required surface | Plan task(s) | Status |
| --- | --- | --- | --- |
| Authentication | `POST /api/v1/auth/token`; `GET /api/v1/me`; `DELETE /api/v1/auth/token` | 3.2, 9.3 | missing |
| Projects | list/create/show/update/delete | 4.3, 9.3 | missing |
| Project members | list/create/delete nested under project | 4.3, 9.3 | missing |
| Tasks | list/create/show/update/delete | 6.1, 9.3 | missing |
| Task status/assignee | patch status; patch assignee | 6.1, 9.3 | missing |
| Task comments | list/create/delete nested under task | 6.2, 9.3 | missing |
| Task attachments | list/create/delete nested under task | 6.2, 9.3 | missing |
| Activity | global, task-scoped, project-scoped lists | 6.3, 8.1, 9.3 | missing |
| Dashboard | summary, my tasks, overdue | 6.4, 8.2, 9.3 | missing |

## API conventions and safety

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Sanctum abilities: projects/tasks read-write, comments/activity/dashboard read-write as specified | 3.2, 4.3, 6.1–6.4 | missing |
| Token issue response is `201`; plaintext token appears only once | 3.2, 9.3 | missing |
| API Form Requests, validated DTO mapping, API Resources/Collections only | 2.3, 3.2, 4.3, 6.1–6.4 | missing |
| Required `201`, `204`, `401`, `403`, and `422` response behavior | 2.4, 3.2, 4.3, 6.1–6.4, 9.3 | missing |
| Task query: search/status/priority/project/assignee/due date/signed sort/page/per-page | 2.2, 2.3, 6.1, 9.3 | missing |
| Per-page cap 1–100, sort whitelist, query-string persistence, pagination meta | 2.2, 2.3, 6.1, 9.3 | missing |
| Resources conceal secrets and internal attachment storage paths | 3.2, 4.3, 6.1–6.4, 9.3 | missing |

## Activity, dashboard, interactive UI, and JavaScript

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Canonical project/task/comment/attachment event names | 4.1, 5.1, 5.3, 8.1 | missing |
| Activity stores actor, subject, event, safe old/new values, project/task context, time | 4.1, 5.1, 5.3, 8.1, 9.3 | missing |
| Passwords, tokens, and secrets excluded from logs and API output | 1.3, 3.2, 6.3, 8.1, 9.3 | missing |
| Activity pages and filterable, portable role-scoped queries | 6.3, 8.1 | missing |
| Dashboard: 11 required counts/lists/distribution metrics with role scope | 6.4, 8.2, 9.2 | missing |
| Guest/app layouts, permission-aware navigation, reusable components/partials | 3.1, 4.2, 5.2, 7.1 | missing |
| `TaskFilters` Livewire component | 7.2 | missing |
| `TaskStatusSelector` Livewire component | 7.3 | missing |
| `TaskCommentForm` Livewire component | 7.4 | missing |
| `QuickTaskCreate` Livewire component | 7.5 | missing |
| Delete confirmation, modal, preview, counter, copy behaviors; no JS-only rule enforcement | 7.6, 9.4 | missing |

## Test and quality matrix

| Requirement | Plan task(s) | Status |
| --- | --- | --- |
| Portable Pest discovery, SQLite memory baseline, test app key, fake services/storage | 9.1 | missing |
| Project critical flows: scope, duplicate member, archive authority, archived update | 9.2 | missing |
| Task critical flows: authority, assignee membership, transition table, archive guard, delete, activity | 9.2 | missing |
| API matrix: auth, ability, policy, validation, success codes, filters/pagination | 9.3 | missing |
| Activity old/new, secrecy, and authorization tests | 9.3 | missing |
| Pint, full Pest, frontend build, route review, N+1, mass-assignment, CSRF/XSS/rate/file audit | 9.4 | missing |
| Junior manual browser checklist and architecture explanation | 7.1, 9.4 | missing |

## Verification record

### Task 0.2 — Traceability checklist

Verified on 2026-08-17. This checklist tracks every source-of-truth requirement
across the architecture, infrastructure/schema, Projects, Tasks, Comments,
Attachments, API, Activity, Dashboard, Livewire, JavaScript, and test surfaces.
It also records the accepted initial direct module dependencies separately from
the deferred refactor work.

Update a row to `verified` only when its acceptance criteria and supporting checks are reported. If an item changes scope, document the owner-approved decision here; do not move it to `sample/roadmap.md` and do not perform roadmap work without explicit permission.
