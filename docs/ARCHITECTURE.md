# Target architecture

## System shape

TaskFlow is a Laravel 13 modular monolith with one application, one database, one deployment unit, and explicit module ownership.

```text
Host application
├── session authentication
├── internal user administration and account lifecycle
├── Sanctum token authentication
├── database notifications
├── shared layouts/components
└── global middleware, gates, rate limits, and exception rendering

Modules
├── Projects   project identity, lifecycle, membership, project labels ownership context
├── Tasks      work items, workflow, assignment, labels, watchers, backlog/board, comments
├── Media      private binary metadata, storage, streaming, and cleanup
├── Activity   canonical audit recording and scoped queries
└── Dashboard  role-aware aggregate/read views
```

There are no separate Web, API, Auth, Users, Board, Labels, Notifications, Core, or Shared modules.

## Target request flow

```text
Web Controller ───────┐
API Controller ───────┼─> validated input -> Policy/Gate -> DTO -> Service
Livewire Component ───┘                                      |
                                                             v
                                              Repository Interface
                                                             |
                                                             v
                                              Eloquent Implementation
                                                             |
                                                             v
                                                Model / persistence
```

Queries use a query service or repository rather than mutation DTOs when no structured mutation exists. Presentation adapters receive fully scoped/eager-loaded data and never perform database queries.

## Host responsibilities

The host application owns `User`, session login/logout, internal account creation/suspension, global roles, token issuance/current-user/revoke, database notifications, application layouts, and application-wide middleware.

The host must not absorb project/task/media business rules. The host-owned suspension use case coordinates account state/session/token revocation with direct Tasks/Projects calls that unassign open work, remove watcher subscriptions, and record sanitized history. Admin password reset and authenticated self-service password change are also host-owned account lifecycle operations.

## Module responsibilities

### Projects

Owns:

- Project and ProjectMember models;
- unique project keys and issue-sequence state;
- lifecycle transitions;
- membership roles and membership-removal invariants;
- project visibility/manageability queries;
- project Web/API controllers and Resources.

Projects does not create or mutate Tasks directly. Where membership removal needs assignment information, it may use an explicit Tasks query/service during the direct-dependency phase; that dependency must be documented.

### Tasks

Owns:

- Task model and project-local display number;
- work type, parent/subtask, priority, fixed workflow, dates, and rank;
- assignment, watchers, labels, comments, task/media association;
- backlog, board, filters, and task metrics;
- four approved Livewire components;
- task Web/API controllers and Resources.

Tasks calls Projects for membership/lifecycle rules, Media for binary operations, and Activity for audit recording. These direct calls are accepted in the current implementation.

### Media

Owns:

- Media model and migration;
- randomized private storage paths;
- server-detected metadata and checksums;
- authorized-stream building blocks;
- image/PDF preview response safety;
- physical deletion and orphan reconciliation.

Media does not decide whether a user may see a Task. Tasks authorizes the parent record and verifies the association before asking Media to stream/delete a file.

### Activity

Owns canonical event names, safe payload normalization, recording, display labels, and scoped history queries. During the direct-dependency phase, Activity may query Project/Task visibility data. It must not become a second source of authorization rules; its scope must mirror the owning modules and be protected by parity tests.

### Dashboard

Owns read-oriented aggregation and presentation only, including assigned, reported, watched, overdue, recent-work, and summary views. It may directly query Project/Task data during the implementation phase. It must reuse canonical visibility scopes and must not implement mutation rules.

## Route ownership

- Host Web routes: `routes/web.php`
- Host auth API routes: `routes/api.php`
- Module Web routes: `Modules/<Module>/routes/web.php`
- Module API routes: `Modules/<Module>/routes/api.php`

Each module provider registers its own Web routes, API routes under `/api/v1`, views, migrations, policies, repository bindings, and approved Livewire components. API route names use `api.v1.*` and must be unique.

## Controllers

Controllers may:

- receive a typed Form Request;
- authorize the application/record action;
- construct a DTO from validated data;
- resolve route-bound parent/child records through scoped services/bindings;
- call one use-case service or query service;
- return a View, redirect, Resource, collection, download, or no-content response.

Controllers may not call `Model::query()`, `DB`, or `Storage`, contain transactions, or decide domain transitions. Architecture tests enforce these restrictions.

## Services and transaction boundaries

Each mutation service defines the business transaction:

- Project create includes project, owner membership, and activity.
- Membership add/update/remove includes invariants, membership mutation, watcher cleanup where relevant, and activity.
- Work-item create includes locked project sequence allocation, task save, optional label/watcher association, and activity.
- Assignment includes membership validation, old/new assignment, auto-watch, notification, and activity.
- Status change includes transition validation, timestamp changes, parent/subtask rules, notification, and activity.
- Multi-file Media attach validates the full request first, then coordinates stored Media plus Tasks associations with compensation of the entire request if any storage/association/Activity step fails.
- Media detach preserves database/file consistency and records activity.

External/physical storage cannot participate in a database transaction. Services therefore use explicit compensation or after-commit deletion and tests for both failure directions.

## Repositories and query services

Repository structure:

```text
Repositories/
├── Contracts/
│   └── TaskRepositoryInterface.php
└── Eloquent/
    └── EloquentTaskRepository.php
```

Repositories own:

- persistence;
- row locking and sequence/rank queries;
- actor/project visibility scope;
- eager loading;
- validated filters and whitelisted sorting;
- pagination and filter-option queries.

Query services compose multiple repositories for page/read-model needs. They do not authorize mutations.

## DTO and date conventions

Mutation DTOs are readonly and purpose-specific: CreateProjectData, UpdateProjectData, ChangeProjectStatusData, CreateTaskData, UpdateTaskData, AssignTaskData, ChangeTaskStatusData, ReorderTaskData, CreateLabelData, SyncTaskLabelsData, and MediaUploadData where appropriate.

Validated dates become immutable date objects before entering services. Filter DTOs may retain normalized scalar query values where database comparison is their only role.

## Authorization model

Authorization has four layers:

1. Authentication establishes the actor.
2. Sanctum ability limits API route families.
3. Spatie permission allows the broad application capability.
4. Policy/Gate evaluates the specific Project/Task/Comment/Media/User context.

Services additionally enforce state invariants that must remain true even when called outside HTTP, such as active-project mutability, membership, one assignee, same-project label/parent, and sequence uniqueness.

Visibility is centralized:

- Admin: all projects/work items.
- Project member: project and every work item in that project.
- Non-member: no project/work-item metadata.

Assignment affects My Work and transition authority, not browse visibility.

## Error architecture

Use purpose-specific domain exceptions, for example:

- `ProjectReadOnly`
- `InvalidProjectTransition`
- `DuplicateProjectMember`
- `MemberHasOpenAssignments`
- `InvalidAssignee`
- `InvalidTaskStatusTransition`
- `ParentTaskInvalid`
- `LabelOutsideProject`
- `MediaStorageFailed`

Unexpected `LogicException`, database, filesystem, and programming failures remain 500-level failures and are not rewritten as user-safe conflicts by a catch-all handler.

## Data ownership and associations

Cross-module database foreign keys are allowed in the monolith.

- Projects owns `projects`, `project_members`.
- Tasks owns `tasks`, `task_comments`, `task_labels`, `task_label`, `task_watchers`, `task_attachments` association.
- Media owns `media`; `task_attachments.media_id` references it explicitly.
- Activitylog tables remain host/package migrations but are presented through Activity.
- Laravel notification tables remain host migrations.

Avoid a generic polymorphic media relation in the initial schema. Each consumer owns an explicit association table with real foreign keys.

## Migration strategy

All migrations are reversible and exercised on SQLite and MySQL. Existing installations use additive migration/backfill/constraint steps. Squashing inherited migrations is allowed only if the user confirms there is no persistent environment/data to preserve.

Never make a nullable issue number permanent. The final schema enforces project sequence/display-number uniqueness after a safe backfill.

## Current direct dependency graph

The expected implementation-phase graph is:

```text
Projects  -> Activity
Tasks     -> Projects, Media, Activity
Media     -> host User/Storage only
Activity  -> Projects, Tasks (scoped reads)
Dashboard -> Projects, Tasks, Activity
Host      -> Projects/Tasks only for user-lifecycle coordination
```

This graph contains deliberate direct/cyclic knowledge around Activity. It is documented, tested, and deferred to `ROADMAP.md`; it must not be refactored during feature completion.

## Presentation architecture

- Blade provides server-rendered primary flows.
- Livewire is limited to TaskFilters, TaskStatusSelector, TaskCommentForm, and QuickTaskCreate.
- Vanilla JavaScript provides progressive board drag/drop, modal, media preview, counters, and copy behavior.
- API Resources are explicit and stable; internal model fields are not serialized accidentally.

No React, Vue, Inertia, SPA, or separate frontend application is part of the target.
