# TaskFlow Project Brief

## Purpose

TaskFlow is a medium-complexity modular-monolith application for managing company projects and tasks. It is deliberately more than a basic CRUD system: it includes project membership, task assignment and status transitions, comments, attachments, activity history, a dashboard, and personal API tokens.

## Learning Goals

The project is a practical learning vehicle for Laravel structure; `nwidart/laravel-modules`; modular-monolith design; web and REST API development; Blade, vanilla JavaScript, and limited Livewire; services, repositories, DTOs, enums, Form Requests, Policies, roles and permissions; Sanctum; activity logging; events/listeners; database transactions; API Resources; pagination, filtering, sorting; focused Pest tests; and supervised Codex-assisted development.

## Stack and Packages

| Area | Planned technology |
| --- | --- |
| Runtime | PHP 8.3 or 8.4 |
| Framework | Laravel 13 |
| Modules | `nwidart/laravel-modules` |
| UI | Blade, Tailwind CSS, vanilla JavaScript, Vite |
| Interactive UI | Limited Livewire components only |
| API authentication | Laravel Sanctum personal access tokens |
| Authorization | Spatie Laravel Permission and Laravel Policies |
| Audit history | Spatie Laravel Activitylog |
| Testing and style | Pest and Laravel Pint |

React, Vue, Inertia, SPA architecture, a separate frontend repository, and a fully Livewire-based UI are out of scope.

## Database

The project owner will select MySQL, MariaDB, or SQLite. All modules share one database, while each module owns its migrations. Cross-module foreign keys are allowed; foreign keys and indexes must be defined correctly. Projects and tasks use soft deletes.

Planned project data includes `projects` and `project_members`; planned task data includes `tasks`, `task_comments`, and `task_attachments`. Task identifiers use the `TSK-000001` format. Project statuses are `draft`, `active`, `completed`, and `archived`; task statuses are `todo`, `in_progress`, `review`, `done`, and `cancelled`; task priorities are `low`, `medium`, `high`, and `urgent`.

## Roles and Authorization

| Role | Capabilities |
| --- | --- |
| Admin | View all projects; manage user roles; view and change all tasks; fully access activity history; manage API tokens. |
| Project manager | Create and edit own projects; manage project members; create and assign tasks; change task statuses; view project and task activity. |
| Member | View projects they belong to; view assigned tasks; make permitted status changes; add task comments; cannot view other projects' data. |

Spatie Permission manages general roles and permissions. Laravel Policies authorize actions on specific records. Sanctum identifies the API user, and token abilities further scope a token; neither replaces Policy authorization.

## Planned Modules and Capabilities

- **Projects:** project CRUD, archiving, membership management, project task display, metrics.
- **Tasks:** task CRUD, assignment, controlled status transitions, filtering, sorting, pagination, comments, attachments, and metrics.
- **Activity:** filtered viewing of audit history for projects and tasks.
- **Dashboard:** project/task metrics, current-user tasks, recent activity, overdue work, and per-project task distribution.

Authentication and the main `User` model may remain in the Laravel host application. Each business module owns its web and API routes; no separate `Api` or `Web` module is planned.

## Authentication

The API uses Sanctum rather than Passport because the project does not require an OAuth2 server, third-party OAuth clients, authorization-code flow, refresh tokens, or a current mobile application. Tokens are issued with a device name and abilities, and their plaintext value is shown only when created.
