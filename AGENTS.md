# TaskFlow repository instructions

## Project identity

- **Project:** TaskFlow
- **Framework:** Laravel 13
- **Architecture:** Modular Monolith
- **Module system:** `nwidart/laravel-modules`
- **Backend:** PHP 8.3+
- **Frontend:** Blade + Tailwind CSS + vanilla JavaScript
- **Interactive UI:** limited Livewire
- **API:** REST API at `/api/v1`
- **Authentication:** Laravel session authentication for Web; Laravel Sanctum personal access tokens for API.
- **Planned supporting packages:** Laravel Sanctum, `spatie/laravel-permission`, and `spatie/laravel-activitylog`; Pest is planned but not installed yet.

Planned business modules:

```text
Modules/
├── Projects/
├── Tasks/
├── Activity/
└── Dashboard/
```

Authentication and the base `App\Models\User` remain in the host Laravel application. Do not create `Api`, `Web`, `Auth`, `Users`, `Core`, or `Shared` modules unless a later explicit task requires one.

## Application flow

```text
Route
  -> Controller or approved Livewire component
  -> Form Request / Validation
  -> Policy / Authorization
  -> DTO
  -> Service
  -> Repository
  -> Eloquent Model
  -> View / API Resource / Redirect
```

## Controllers, services, repositories, and DTOs

Controllers stay thin. They may accept a request, authorize, read validated data, create a DTO, call a service, and return a Blade view, redirect, or API Resource. They must not contain long Eloquent queries, transactions, task-transition rules, complex permission decisions, activity-log business logic, or duplicated Web/API business logic.

Services own business rules, use-case orchestration, transactions, repository coordination, approved cross-module coordination, event dispatching, and meaningful business activity logging. Examples include `ProjectService`, `ProjectMemberService`, `ProjectMetricsService`, `TaskService`, `TaskAssignmentService`, `TaskStatusService`, `TaskCommentService`, `TaskAttachmentService`, `TaskMetricsService`, and `DashboardService`.

Repositories own Eloquent queries, persistence, filters, sorting, pagination, eager loading, and create/update/delete operations. They must not own authorization, role decisions, task-status rules, HTTP responses, business activity-log decisions, or business orchestration. Use an interface plus Eloquent implementation, for example `TaskRepositoryInterface` + `EloquentTaskRepository` and `ProjectRepositoryInterface` + `EloquentProjectRepository`.

DTOs pass structured business input from HTTP/Livewire to services. Use purposeful DTOs such as `CreateProjectData`, `UpdateProjectData`, `CreateTaskData`, `UpdateTaskData`, `TaskFiltersData`, `AssignTaskData`, and `ChangeTaskStatusData`; do not pre-create speculative DTOs.

## Validation and authorization

Use Laravel Form Requests for HTTP validation: required fields, string length, email format, enum values, dates, integer IDs, file MIME types, and file size. Do not hide business rules in Form Requests. Rules such as archived projects not accepting tasks, project-member assignees, allowed status transitions, and reopening a Done task belong in services and/or policies.

Use Laravel Policies plus `spatie/laravel-permission`. Spatie Permission answers broad capability questions (for example, whether a user may generally create tasks); policies answer record-level questions (whether that user may create a task in this project).

## API rules

Use Laravel Sanctum. API routes use `/api/v1`; planned abilities are `projects:read`, `projects:write`, `tasks:read`, `tasks:write`, `comments:write`, `activity:read`, and `dashboard:read`. A token ability never replaces a policy. API controllers must return API Resources, never Eloquent models directly.

Web/API/Livewire use one service layer:

```text
Web Controller ─────────┐
                        │
API Controller ─────────┼── Service
                        │     ↓
Livewire Component ─────┘ Repository
                              ↓
                            Model
```

Do not duplicate business logic.

## Frontend and JavaScript

Default frontend: Blade, Tailwind CSS, vanilla JavaScript, and Vite. Do not introduce React, Vue, Inertia, an SPA architecture, or a separate frontend repository.

Livewire is restricted to approved components: `QuickTaskCreate`, `TaskFilters`, `TaskStatusSelector`, and `TaskCommentForm`. Do not convert the full application to Livewire.

Vanilla JavaScript may implement UI behavior such as delete confirmations, modals, attachment previews, character counters, copying task numbers or API tokens, conditional fields, and assignee UI updates. It must never be the sole enforcement point for a business rule; the backend is authoritative.

## Modules and database

For the first learning implementation, direct module dependencies are allowed; for example, Tasks may import `Modules\Projects\Models\Project`. Do not move another module's business logic into the current module. When changing another module's data, prefer that module's service: Tasks should call `ProjectMemberService`, not create a `ProjectMember` directly. Contracts/adapters and looser coupling come only after the first working version.

All modules share one database, but each module owns its migrations. Cross-module foreign keys, appropriate indexes, and soft deletes for projects and tasks are expected. Never run migrations without explicit supervisor approval. Never run `migrate:fresh`, `migrate:reset`, `migrate:refresh`, or `db:wipe` without explicit approval.

## Dependencies, environment, Git, and browser

Never install, update, or remove dependencies without an explicitly approved task, including `composer require`, `composer update`, `composer remove`, `npm install`, and `npm update`. Do not change `composer.lock` or package lock files outside approved dependency work.

Never access, display, or edit `.env` unless a future task explicitly permits a specific environment operation. Never expose secrets. Do not run Git commands or real-browser/browser-automation commands unless explicitly approved; developers normally perform manual browser verification.

## Testing and security

The final project will use Pest for focused tests; Pest is not installed yet. TDD is not mandatory, but critical flows require tests.

Consider, whenever relevant: Form Request validation, policies, roles/permissions, Sanctum abilities, rate limiting, mass assignment, XSS, CSRF, file MIME and size validation, authorized attachment downloads, project and assignee membership, hidden API fields, activity-log secret filtering, N+1 queries, and safe sorting.

## Agent workflow and final report

Work on one TASK-ID at a time. Do not silently expand scope, change unrelated files, or pre-create large empty directory trees; create a folder only when its first real file is needed.

Every implementation-task report must state:

```text
Changed files:
- ...

Checks run:
- ...

Checks skipped:
- ...

Remaining work:
- ...

Architecture explanation:
- Which layer was changed?
- Why does this code belong there?
- Where are validation and authorization handled?
- Which service/repository boundary is involved?
```
