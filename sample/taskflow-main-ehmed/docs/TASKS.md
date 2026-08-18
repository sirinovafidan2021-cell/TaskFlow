# Development Plan

This plan follows the seven milestones defined for TaskFlow. Detailed `TASK-xxx` items are intentionally not created yet.

## Milestone 1 — Infrastructure

- Add Nwidart Modules, Sanctum, Livewire, Spatie Permission, and Spatie Activitylog.
- Implement authentication.
- Create the shared Blade layout.
- Add role and permission seed data.
- Create module shells.

## Milestone 2 — Projects

- Create project and project-member migrations and models.
- Add repositories and services.
- Add Policies.
- Build web CRUD and API CRUD.
- Implement member management.
- Add focused tests.

## Milestone 3 — Tasks

- Create task migrations and models.
- Add PHP enums.
- Add repositories, services, and Policies.
- Build web CRUD and API CRUD.
- Implement assignment and status transitions.
- Implement filtering, sorting, and pagination.

## Milestone 4 — Comments and Attachments

- Add comment service/repository and web/API features.
- Add the Livewire comment form.
- Implement attachment upload.
- Authorize attachment downloads.

## Milestone 5 — Activity

- Configure Spatie Activitylog.
- Define model log options.
- Add business-event logging.
- Build the activity page and API.

## Milestone 6 — Dashboard

- Add the dashboard service.
- Add direct Project and Task queries for initial metrics.
- Build the Blade dashboard and approved Livewire components.
- Add the dashboard API.

## Milestone 7 — Stabilization

- Review queries and N+1 risks.
- Conduct a security review.
- Check API consistency.
- Add focused tests.
- Run Pint.
- Complete the manual browser checklist.
- Complete documentation.

## Testing Requirements

TDD is not mandatory, but critical flows require tests.

- **Projects:** prevent access to projects where the user is not a member; prevent duplicate membership; allow only a manager to archive a project; prevent changes to archived projects.
- **Tasks:** prevent non-members from creating tasks; assign tasks only to project members; enforce status transitions; prevent task creation in archived projects; prevent unauthorized deletion; create activity history on task creation.
- **API:** verify `401` without a token, `403` without required ability, `403` for denied Policy authorization, `422` validation errors, `201` creates, and working pagination/filtering.
- **Activity:** record old and new status values; exclude passwords and tokens; prevent unauthorized activity access.

## Security Requirements

Verify Form Request validation, Policy authorization, roles and permissions, Sanctum token abilities, rate limiting, mass-assignment protection, XSS and CSRF protection, attachment MIME/size validation, attachment-download authorization, project membership, assignee membership, exclusion of secret API fields, and exclusion of passwords/tokens from activity history.

## Definition of Done

A task is done when its acceptance criteria are met; code is in the correct module; controllers stay thin; business logic is in services; queries are in repositories; DTOs and Form Requests are used; Policy/permission checks are present; Sanctum and abilities are checked for API work; API Resources are used; necessary activity history is added; critical tests are written; N+1 has been checked; no out-of-scope changes were made; the junior can explain the code; the junior completed manual browser testing; and the checks run and skipped by Codex are recorded.

## Codex Development Rules

- Use $nwidart-module-development.
- Ask before adding dependencies.
- Assign Codex one concrete task at a time.
- Before implementation, read `AGENTS.md`, `docs/PROJECT_BRIEF.md`, `docs/ARCHITECTURE.md`, `docs/API_CONVENTIONS.md`, and `docs/TASKS.md`.
- Keep work within the assigned module and task scope.
- Do not run migrations, access `.env`, run Git commands, use a real browser, or add dependencies without the required approval.
- At task completion, report changed files, checks run, checks skipped, and remaining work.
- A junior must not accept Codex-produced code without understanding it, including its layer, module placement, controller/service/repository responsibilities, authorization, web/API service sharing, and activity-log location.
