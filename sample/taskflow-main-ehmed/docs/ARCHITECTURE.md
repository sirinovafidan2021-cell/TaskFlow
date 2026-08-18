# Architecture

## Architectural Style

TaskFlow is a Laravel modular monolith built with `nwidart/laravel-modules`. Its planned business modules are `Projects`, `Tasks`, `Activity`, and `Dashboard`. Authentication and the main `User` model may remain in the host application. Modules share one database and may use cross-module foreign keys.

Each business module owns both `routes/web.php` and `routes/api.php`; no `Api` or `Web` module will be created.

## Module Structure

Create folders only when their first real class is needed. The intended module shape is:

```text
Modules/<Module>/
├── app/
│   ├── Data/ Enums/ Events/ Exceptions/
│   ├── Http/Controllers/{Web,Api/V1}/
│   ├── Http/Requests/{Web,Api/V1}/
│   ├── Http/Resources/ Livewire/ Models/ Policies/ Providers/
│   ├── Repositories/{Contracts,Eloquent}/
│   ├── Services/ Support/
├── config/
├── database/{factories,migrations,seeders}/
├── resources/{assets/js,views}/
├── routes/{web.php,api.php}
├── tests/{Feature,Unit}/
├── composer.json
└── module.json
```

## Request Flow and Layer Responsibilities

```text
Route → Controller or Livewire component → Form Request / validation → Policy
      → DTO → Service → Repository → Model → View / API Resource / Redirect
```

| Layer | Responsibility |
| --- | --- |
| Controllers | Handle HTTP requests, authorize, obtain validated data, create DTOs, call services, and return views, redirects, or API Resources. They must not contain long Eloquent queries, transactions, status-transition rules, permission decisions, or activity-log business logic. |
| Form Requests | Validate web and API inputs. |
| DTOs | Carry structured data between HTTP and service layers, avoiding unstructured associative arrays. Filter input is represented by DTOs such as `TaskFiltersData`. |
| Services | Own use cases and business rules; call repositories; coordinate allowed cross-module access; manage transactions; dispatch events; and create activity history. Examples include `ProjectService`, `ProjectMemberService`, `TaskService`, `TaskAssignmentService`, `TaskStatusService`, `TaskCommentService`, `TaskAttachmentService`, and `DashboardService`. |
| Repositories | Own Eloquent/database queries, filters, sorting, pagination, eager loading, and persistence. Each main aggregate has an interface and Eloquent implementation. Repositories must not make authorization, role, status-transition, business-exception, activity-log, or HTTP-response decisions. |
| Models | Represent persisted data and relationships. |
| Policies and Spatie Permission | Policies authorize record-specific actions; Spatie Permission supplies general role/permission management. |
| API Resources | Shape JSON output. Controllers do not return Eloquent models directly. |
| Livewire | Provides only approved interactive components; components authorize, call services, and never call repositories directly or own business rules. |

Web controllers, API controllers, and Livewire components share services rather than duplicating business logic or requiring Blade pages to call the API.

## Transactions, Events, and Activity

Services own transaction boundaries for operations that require them. They dispatch events such as task creation and create business-level activity entries. Spatie Activitylog records audit history; `LogsActivity` may be used for model changes, while meaningful business events are logged from services. Passwords, tokens, and secrets must never be logged.

The Activity module primarily displays and filters activity. Logged events include project creation, updates, archiving, and membership changes; task creation, updates, assignment, status changes, and deletion; comment creation/deletion; and attachment upload/deletion.

## Initial Cross-Module Dependencies

During the first learning stage, direct dependencies are deliberately allowed:

- `Tasks` may directly use the `Projects` model, relations, and concrete services.
- `TaskService` may query a project and its members to apply task-creation rules.
- `Dashboard` may directly query `Project` and `Task` models for metrics.
- Modules should call the owning module's service rather than directly modifying its data—for example, use `ProjectMemberService` rather than creating a `ProjectMember` from another module.

This temporary tight coupling is accepted because modules are not independently published, deployed, disabled, or removed; `Projects` and `Tasks` remain active in the same application and database.

## Later Decoupling and Refactoring

Decoupling is explicitly deferred until after the initial project is complete. The refactoring stage replaces direct cross-module model/service knowledge with contracts, implementations, cross-module DTOs, and, where appropriate, events/listeners.

The intended progression includes replacing direct project access in `Tasks` with a `ProjectAccessInterface` implemented by Projects; moving dashboard direct queries to metrics contracts; moving activity direct calls toward event listeners; documenting the dependency graph; using fake implementations in tests; assessing module-disable scenarios; and comparing the initial and refactored architectures.
