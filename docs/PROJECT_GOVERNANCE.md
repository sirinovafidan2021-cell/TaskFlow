# TaskFlow Project Governance

## Authoritative documents

- `sample/TaskFlow.md` defines the product and architectural requirements.
- `sample/project-analysis.md` records the comparative analysis of the two sample implementations.
- `sample/implementation-plan.md` is the mandatory implementation execution plan.
- `sample/roadmap.md` is explicitly out of scope until the implementation plan is fully complete and the project owner grants written permission to begin it.

## Execution boundary

1. Complete every task in `sample/implementation-plan.md` before considering any roadmap work.
2. Do not read, modify, or execute `sample/roadmap.md` during implementation-plan work.
3. After the implementation plan is complete, provide a completion report and request explicit permission before any roadmap task starts.
4. Work on one implementation-plan task at a time and divide it into the smallest practical logical units when needed.

## Mandatory task workflow

Before a task begins, report its identifier, intended changes, purpose, and expected result. After it completes, report created and modified files, exact work performed, commands run, verification performed, skipped checks, and the next task. If a decision, credential, manual action, additional file, or other owner input is needed, pause and request it explicitly.

## Canonical merge decision

`sample/taskflow-main-ferhad` (Project B) is the canonical implementation base. Preserve its working domain/UI baseline where it meets the requirements; selectively port the planned Sanctum/API, repository-contract, test, and configuration improvements from Project A. Do not copy either sample project wholesale.

## Architecture guardrails

- Use Laravel 13, PHP 8.3+, Blade, Tailwind, vanilla JavaScript, limited approved Livewire components, Laravel Sanctum, and `nwidart/laravel-modules`.
- Business modules are limited to `Projects`, `Tasks`, `Activity`, and `Dashboard`. Authentication and `App\\Models\\User` remain in the host application.
- Preserve the flow: route -> controller or approved Livewire component -> Form Request/validation -> policy -> DTO -> service -> repository -> model -> view/API resource/redirect.
- Controllers stay thin; services own business rules and transactions; repository interfaces and Eloquent implementations own queries and persistence.
- Web, API, and approved Livewire components share the same service layer.
- Initial direct module dependencies are accepted only as documented in the implementation plan. Do not introduce speculative contracts, CQRS, command buses, or event sourcing.

## Immediate traceability

| Planned task | Status | Notes |
| --- | --- | --- |
| Task 0.0 — Governance baseline | verified | This document and the identical root `AGENTS.md` preserve the delivery rules. |
| Nwidart setup prerequisite | verified | Nwidart v13 is installed, Laravel package discovery is active, and module Composer files are included for autoload merging. |
| Task 0.1 — Canonical Project B base | verified | Project B is the single baseline; its four enabled modules and supported host application files are installed. |
| Task 1.1 — Module provider and route registration | verified | All four module providers load their module-owned routes, views, and migrations. Web routes use `web,auth`; deferred API route groups consistently use `api,auth:sanctum`, `/api/v1`, and `api.v1.`. |

## PHP 8.3 dependency compatibility decision

Project B's original lockfile resolved Activitylog v5 and Pest v5, both of which require PHP 8.4. To honor the target PHP 8.3+ support requirement, the dependency manifest uses `spatie/laravel-activitylog ^4.12`, `pestphp/pest ^4.7`, and `pestphp/pest-plugin-laravel ^4.0`. All other declared Project B package constraints remain unchanged; this is a dependency-resolution compatibility adjustment, not an implementation-plan change.
