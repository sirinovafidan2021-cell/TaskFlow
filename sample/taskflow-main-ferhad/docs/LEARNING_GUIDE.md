# Learning guide

## Layer responsibilities

- **Controller:** HTTP adapter; it receives an HTTP request and returns a response.
- **Form Request:** validates HTTP input shape and basic constraints.
- **Policy:** decides record-level authorization for the acting user.
- **DTO:** carries structured, typed input into a business use case.
- **Service:** performs a business use case and owns its business rules.
- **Repository:** owns persistence and Eloquent query details.
- **Model:** Eloquent representation of a database record and relationships.
- **API Resource:** JSON presentation layer that controls exposed API fields.

## One service layer

Web controllers, API controllers, and approved Livewire components call the same services so a task-status rule is implemented once. The user interface changes, but the business use case stays identical and testable.

## Validation and business rules

Validation checks input form: a title is required, an ID is an integer, or a file has a permitted MIME type. A business rule checks whether an action is allowed in the domain: an archived project cannot receive a task, or an assignee must belong to its project. Put validation in Form Requests and business rules in services and policies.

## Permissions, policies, and abilities

Spatie Permission provides broad roles and capabilities such as whether a user can generally create tasks. A Policy applies that ability to one record or project. Sanctum abilities scope an API token, for example `tasks:write`; they do not replace Policies because token scope alone cannot decide whether the caller may change this particular task.

## Learning-phase module coupling

Version 1 temporarily accepts a direct dependency such as Tasks using the Projects model. This keeps the first working implementation understandable. Later refactoring will introduce looser coupling only after concrete problems appear, so the benefits of contracts and adapters are learned from real code rather than abstract ceremony.
