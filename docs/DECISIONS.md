# Accepted decisions

These decisions are binding for the current implementation. An agent may not silently replace them. A change requires explicit user approval and synchronized updates to the product brief, architecture, implementation plan, and task tracker.

## ADR-001 — Canonical base

The Ehmed implementation is the canonical starting code. Fidan and the roadmap-heavy third implementation may be consulted for isolated ideas/tests, but neither is merged wholesale.

## ADR-002 — Product boundary

The target is a single-organization, Kanban-first, minimal Jira-like issue tracker. It is not a full Jira clone and not a multi-tenant SaaS.

## ADR-003 — Responsibility model

A work item has one reporter and zero or one assignee. Multiple assignees are forbidden. Multiple interested users are modeled as project-member watchers.

## ADR-004 — Visibility model

Project membership grants browse access to the project's work items. Assignment controls responsibility and status authority, not basic visibility. Admins can browse all records.

## ADR-005 — Work classification

Work types are fixed to Task, Bug, Story, and one-level Subtask. Project-scoped labels provide flexible classification. Generic categories and Components are out of the current scope.

## ADR-006 — Planning model

The product uses a fixed Kanban workflow and ranked backlog. Sprints, epics, releases, custom workflows, dependencies, and recurring work are not current-scope features.

## ADR-007 — Project and issue identity

Every project has an immutable unique key after its first work item. Display numbers are project-local, such as `PAY-42`. The database may retain a stored display number for lookup, but allocation must be transactional and unique.

## ADR-008 — Project lifecycle

Projects use Draft, Active, Completed, and Archived states. Only Active projects accept work mutations. Completed projects are read-only and may be reopened to Active by an authorized manager. Archived projects are read-only and terminal in the current scope.

## ADR-009 — Direct module dependencies now

Direct module calls are accepted during implementation. Their locations are documented and tested. Cross-module contracts, adapters, and domain-event decoupling belong only to the roadmap after the full implementation is stable.

## ADR-010 — Central Media module

All uploaded files and images use the Media module. Media owns binary metadata, private storage, streaming, checksum, and cleanup. Consuming modules own record authorization and explicit association tables. A global polymorphic `mediable_type/id` table is not used in the initial design because explicit foreign keys are preferred.

## ADR-011 — Authentication and user lifecycle

Public registration is disabled. Administrators provision and suspend internal accounts. Suspension revokes tokens and prevents future session/API authentication. The last active administrator cannot be demoted or suspended.

## ADR-012 — API authentication

The API supports credential-to-token issuance, current-user inspection, and current-token revoke. Plaintext tokens are returned once and never logged or persisted in plaintext. Abilities narrow access and never replace policies.

## ADR-013 — Limited Livewire

Only QuickTaskCreate, TaskFilters, TaskStatusSelector, and TaskCommentForm use Livewire. The board uses progressive vanilla JavaScript and authoritative backend status/rank services.

## ADR-014 — Test architecture

Pest proves domain, repository, Web, API, security, and Livewire behavior. Playwright proves a small set of critical browser journeys. The custom `qa/` folder is removed after its useful regression cases are migrated.

## ADR-015 — Baseline versus roadmap security

Authorization, input validation, private media, token safety, rate limiting, XSS/CSRF protection, audit sanitization, and account suspension are implementation requirements, not roadmap work. Advanced controls such as 2FA, malware scanning, security-event operations, and periodic access review remain roadmap items.

## ADR-016 — Suspension and password lifecycle

Open assignments never block an urgent suspension. The suspension use case marks the account suspended, revokes all sessions and personal access tokens, unassigns its open work, removes watcher subscriptions, and records safe Activity while preserving historical reporter/assignee references. Administrators can reset an account password; an active authenticated user can change their own password after current-password confirmation. Admin reset invalidates all sessions/tokens. Self-change invalidates other sessions and all tokens, then regenerates the current session.

## ADR-017 — Priority and creation defaults

Priorities are fixed to Low, Medium, High, and Urgent with sort order `low < medium < high < urgent`. Every new work item starts in Backlog at a server-calculated position. No Web/API/Livewire client chooses initial status or a raw rank.

## ADR-018 — Reorder authority

Explicit backlog/column reorder is project-manager-only. An assignee may execute an allowed status transition; the server places that work item at the end of the target column. This does not grant the assignee authority to reorder other work.

## ADR-019 — Atomic multi-file media requests

A multi-file upload request is all-or-nothing. All files are validated before persistence. Any validation, storage, association, or Activity failure rejects the whole request and compensates all records/files created by that request. HTTP Range/206 streaming is not part of v1.

## ADR-020 — Notification and watched-work surface

Notifications are database/in-app and Web-only in API v1; there is no notification inbox API. Dashboard provides My Watched Work through Web and `/api/v1/dashboard/watched`. The general Task search/filter contract does not expose an arbitrary watcher filter.
