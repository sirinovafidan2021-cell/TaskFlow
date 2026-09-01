# TaskFlow

TaskFlow is an internal, Jira-inspired issue tracker built as a Laravel modular monolith. The selected starting point is the existing Ehmed implementation, but that implementation is **not** accepted as complete. The repository is currently in a planned stabilization and product-completion phase.

The target product is deliberately smaller than Jira. It provides projects, project membership, typed work items, a fixed Kanban workflow, backlog ranking, one assignee per work item, watchers, labels, comments, centralized private media, activity history, dashboards, a REST API, and a limited set of Livewire interactions.

## Technology

- PHP 8.3+
- Laravel 13
- `nwidart/laravel-modules`
- Blade, Tailwind CSS, Vite, and vanilla JavaScript
- Livewire only for approved interactive components
- Laravel Sanctum
- Spatie Permission and Activitylog
- Pest for unit, feature, API, security, and Livewire tests
- Playwright for a small set of critical browser journeys

## Reproducible local setup baseline

The baseline is intentionally lock-file driven. Do not use `composer update`,
`npm install`, or the Composer `setup` script to make a clean checkout work:
the latter also runs a migration and therefore requires an identified database
and separate authorization.

Prerequisites are PHP 8.3 or newer, Composer 2, and Node.js
`^20.19.0 || >=22.12.0` (the range locked by Vite 8), with `node`, `npm`, and
`npx` available on the shell `PATH`. A clean checkout uses the existing locks
only:

```bash
composer install --no-interaction --prefer-dist
npm ci --ignore-scripts
npm run build
```

Create the local environment file from `.env.example` only when it does not
already exist, then generate an application key. Database migration and seed
commands are deliberately separate from setup and must be run only against an
identified local/test database with the relevant task approval. The portable
test database configuration is introduced by TF-100; before then, do not treat
the inherited MySQL-specific test configuration as a clean-checkout test gate.

## Modules

The target modular monolith contains:

- `Projects`: project lifecycle, keys, ownership, and membership
- `Tasks`: work items, workflow, assignment, labels, watchers, backlog, board, comments, and task-to-media links
- `Media`: centralized private file/image metadata, storage, streaming, and lifecycle
- `Activity`: canonical audit recording and scoped history
- `Dashboard`: role-aware summaries and work queues

Authentication, internal user administration, personal access tokens, and database notifications remain in the host application.

Direct module calls are accepted for the implementation phase. Do not introduce cross-module contracts, adapters, or domain-event decoupling until the implementation plan is complete and the roadmap explicitly starts that work.

## Product boundaries

The completed implementation is Kanban-first. It does not include workspaces, multi-tenancy, sprints, epics, custom workflows, custom fields, multiple assignees, recurring tasks, webhooks, or an automation engine.

Important product rules:

- A work item belongs to exactly one project.
- A work item has one reporter and zero or one assignee.
- Multiple interested users participate as watchers, not co-assignees.
- Project members can browse the project's work items; record actions remain policy-controlled.
- Media is private and centrally managed by the Media module.
- Archived projects are read-only across Web, API, Livewire, and services.

## Documentation

Start with [docs/README.md](docs/README.md). The authoritative execution order is:

1. `AGENTS.md`
2. `docs/PROJECT_BRIEF.md`
3. `docs/DECISIONS.md`
4. `docs/ARCHITECTURE.md`
5. `docs/SECURITY.md`
6. `docs/TEST_STRATEGY.md`
7. `docs/IMPLEMENTATION_PLAN.md`
8. `docs/TASKS.md`

`docs/CURRENT_STATE_AUDIT.md` describes the inherited code and must not be mistaken for target architecture. `docs/ROADMAP.md` is deferred work and is not part of the current implementation scope.

## Starting work with Codex

Open this repository root in Codex; no external plan or previous conversation is required. The root `AGENTS.md` is the mandatory agent instruction file. It directs Codex to read the canonical documents, inspect `docs/TASKS.md`, select only the next dependency-ready TASK-ID, implement its complete acceptance criteria, and record verification evidence.

At this packaged baseline TF-000 is verified and TF-001 is the first unblocked task. After implementation starts, always trust the live status in `docs/TASKS.md`, not this handoff sentence.

Do not give Codex the deferred roadmap as the current task. The core implementation must reach its Final Definition of Done before roadmap work begins.

## Current status

The inherited application contains useful Projects, Tasks, Activity, Dashboard, Web, API, policy, service, repository, and regression-test code. It also contains known architecture, security, product-rule, test-structure, schema, and documentation gaps.

No release-complete claim is valid until every implementation-plan gate is verified and `docs/TASKS.md` records the evidence.
