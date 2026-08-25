# TaskFlow product specification

## Product objective

TaskFlow is an internal, single-organization issue tracker for software and operational teams. It should feel like a small, coherent Jira/Kanban product without inheriting Jira's enterprise configuration complexity.

The completed implementation must support the full lifecycle from internal account provisioning to project setup, backlog planning, work execution, collaboration, private media, audit history, dashboards, and API access.

## Explicit non-goals

The current product does not include:

- workspaces or multi-tenancy;
- public registration;
- multiple assignees;
- sprints, epics, releases, or capacity planning;
- custom work types, fields, workflows, or permission schemes;
- generic categories or Components;
- task dependencies or recurring tasks;
- automation rules, webhooks, or third-party integrations;
- rich-text/WYSIWYG editing;
- public media URLs.

## Users and roles

Global roles:

| Role | Organization capability |
| --- | --- |
| `admin` | Provision/suspend users, manage global roles, access all projects/work items, and operate all product features |
| `project_manager` | Create projects; other authority is derived from project membership and policy |
| `member` | Participate in projects; cannot create a new project unless promoted |

Project roles:

| Role | Project capability |
| --- | --- |
| `manager` | Manage project details/lifecycle/members; manage all project work items |
| `member` | Browse project work, report work, comment, upload media, watch work, and progress assigned work |

Global and project roles are intentionally separate. A globally ordinary member may be a manager in a specific project. Broad Spatie permissions must therefore allow the policy to make the final project-context decision instead of blocking that role combination prematurely.

## Internal account lifecycle

- There is no public registration.
- An admin creates an account with exactly one global role.
- A user can be active or suspended; historical records are never deleted to deprovision a user.
- Suspension is not blocked by open assignments. It revokes all sessions/personal access tokens, blocks future Web/API authentication, unassigns open work, removes watcher subscriptions, and preserves historical reporter/assignee/audit references.
- The final active admin cannot be demoted or suspended.
- Email addresses are unique and normalized.
- Passwords are hashed and never displayed after creation.
- An admin can reset a user's password. An active authenticated user can change their own password after confirming the current password.
- Admin password reset invalidates every session and personal access token. Self-change invalidates other sessions and all tokens, then regenerates the current session.

## Projects

Each project has:

- name, description, unique slug, and unique uppercase key;
- owner and explicit memberships;
- Draft, Active, Completed, or Archived state;
- optional start and due dates;
- a project-local next issue sequence.

Lifecycle:

```text
Draft -> Active -> Completed
           ^          |
           +----------+  authorized reopen

Draft | Active | Completed -> Archived
Archived -> terminal in current scope
```

Only Active projects accept new or changed work, comments, watchers, labels, and media. Completed and Archived projects remain visible according to policy but are read-only. Project key becomes immutable after the first issue is allocated.

The owner is always a project manager and cannot be removed. Removing another member is blocked while that user owns open assignments; the manager must reassign or unassign those items first. Membership removal also removes watcher subscriptions for that project but preserves historical reporter/assignee/audit references.

## Work items

The code continues to use `Task` as the model/module term. Product UI and API documentation may call it a work item or issue.

Each work item has:

- project-local key such as `PAY-42`;
- fixed type: Task, Bug, Story, or Subtask;
- one reporter (`creator_id` in the inherited schema);
- zero or one assignee;
- title, plain-text description, fixed priority (`low`, `medium`, `high`, `urgent`), status, rank, and optional due date;
- zero or more project-scoped labels;
- zero or more project-member watchers;
- comments, media attachments, and audit history;
- optional parent for a one-level Subtask.

### Assignment and visibility

- All project members can browse all project work items.
- Only a project member can be assigned or added as a watcher.
- A member reporting work may leave it unassigned or assign it to themselves; assigning another user requires project-manager authority.
- Managers can assign/unassign any project member.
- Watchers gain notifications, not edit authority.

### Creation and editing

- Admins and project members can report work in an Active project.
- A project manager can edit any mutable work item detail.
- A reporter can edit the work item's title, description, type, priority, due date, and labels while it remains Backlog or Todo; assignment and manager-only fields remain protected.
- Only project managers can soft-delete work items.

### Fixed workflow

```text
backlog     -> todo, cancelled
todo        -> backlog, in_progress, cancelled
in_progress -> todo, review, cancelled
review      -> in_progress, done, cancelled
done        -> in_progress (manager only)
cancelled   -> backlog (manager only)
```

An assignee may use ordinary forward/back transitions. A manager may perform any allowed transition and manager-only reopen. A non-assigned member cannot transition a work item.

Dates:

- `started_at` is set the first time work enters In Progress and remains historical on backward moves.
- `completed_at` is set on Done and cleared when reopened.
- Cancelled does not count as completed.
- Overdue means due date before the application-local current date/time and status not Done/Cancelled.

### Backlog, board, and rank

- Every new work item starts in Backlog at a server-calculated position; clients cannot set initial status or raw rank.
- Backlog has an explicit project-local rank independent of priority.
- Moving a card between board columns calls the same status service as Web/API/Livewire actions.
- Explicit backlog/column reordering is project-manager-only, concurrency-aware, and never bypasses workflow rules.
- An assignee performing an allowed status transition moves only that work item to the end of the target column and gains no general reorder authority.
- Board filters include text/key, type, status, priority, assignee, reporter, label, due/overdue, and unassigned.

### Work types and subtasks

- Task, Bug, and Story are standard work types.
- Subtask must have a parent standard work item in the same project.
- Subtasks cannot contain children; cycles and cross-project parents are impossible.
- A parent cannot become Done while it has open subtasks.
- Parent and subtasks may have different assignees.

## Labels

- Labels belong to one project and have name, normalized slug, and display color.
- Label name/slug is unique within the project.
- Managers create/update/delete project labels.
- Project members can attach/detach existing labels on work items they may edit; managers can label all work.
- Labels cannot cross project boundaries.
- Deleting a label removes associations but never deletes work items.

## Watchers and notifications

- A project member may watch/unwatch visible work.
- A manager may add/remove project-member watchers.
- Reporter and assignee are automatically watchers unless they explicitly unwatch; assignment can re-add the new assignee for the assignment event.
- Initial notifications are database/in-app and Web-only; API v1 has no notification inbox endpoints.
- Notify on assignment, watched-item comment, and watched-item status change.
- The actor does not receive a notification for their own action.
- Notifications contain identifiers and safe summaries, never secrets or private storage paths.
- My Watched Work is a dedicated Dashboard queue; arbitrary watcher filtering is not part of general Task search.

## Comments

- Any project member who can view a work item can comment while the project is Active.
- Body is plain text, required, and limited to 5,000 characters.
- Authors can soft-delete their own comments; project managers can delete any comment.
- Deleted comments retain audit history without exposing deleted body content in new activity payloads.

## Central media

Files and images are private media. The Media module owns physical storage and metadata; Tasks owns the task/media association and task authorization.

- Initial per-file limit: 10 MB.
- Initial request limit: 5 files.
- Multi-file upload is all-or-nothing: all files are validated first and every created file/record is compensated if any item fails.
- Allowed: PDF, PNG, JPEG, WebP, plain text, DOC/DOCX, XLS/XLSX.
- SVG and executable formats are forbidden.
- Images and PDFs may have authorized inline preview; all media has authorized download.
- Uploader may delete their own media while the project is Active; managers may delete any task media.
- Internal disk/path and checksums are never exposed by public Resources.

Detailed rules are in `MEDIA.md`.

## Activity

Activity is canonical, scoped, and human-readable. It records project lifecycle/membership, work creation/update/assignment/status/rank, label changes, watcher changes, comments, media, and user administration where appropriate.

Update events store safe old/new values for approved fields. Activity properties must remove passwords, tokens, authorization headers, cookies, private paths, file content, and notification payload internals.

Project members can view activity for visible project work. Admins can view all activity. Filters may never disclose inaccessible record metadata.

## Dashboard

The dashboard reuses the same visibility rules as lists and APIs. It includes:

- Active/Completed/Archived project counts;
- total visible work and counts by workflow status/type;
- overdue and completed-today counts;
- My Assigned Work;
- Reported by Me;
- My Watched Work;
- recent visible activity;
- project status distribution;
- QuickTaskCreate.

## API

Web, API, and Livewire call the same use-case services. Sanctum abilities narrow API routes; permission and policy authorization always remain required. Resources never expose private model/storage/token internals.

The target endpoint and response contract is defined in `API_CONVENTIONS.md`.

## Definition of product completeness

TaskFlow is product-complete only when:

- every business rule above works through all applicable entry points;
- all inherited P0/P1 audit findings are closed;
- the central Media module replaces direct task storage ownership;
- Web/API/Livewire behavior is consistent;
- critical Pest and Playwright suites pass on clean setup;
- SQLite tests and a separately approved MySQL compatibility run pass;
- manual browser/API checklists are signed off;
- documentation describes the verified code, not planned or historical behavior.
