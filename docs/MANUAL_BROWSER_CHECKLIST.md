# Manual browser acceptance checklist

## Status

This describes the **target product**, not inherited completion evidence. Execute and record it in TF-1003 after automated gates pass. Never use placeholder users, production secrets, or real sensitive uploads.

Test at desktop and narrow/mobile widths. Use at least: active admin, project manager, project member, non-member, suspended user, and a second project for isolation checks.

## Authentication and accounts

- [ ] Public registration is unavailable.
- [ ] Valid login regenerates the session; invalid login is throttled and does not reveal whether an account exists.
- [ ] Logout invalidates the session and rotates the CSRF token.
- [ ] Suspended users cannot establish a session and existing access is revoked as designed.
- [ ] Admin can create/edit/suspend/reactivate users and reset passwords; final active admin cannot be suspended/demoted.
- [ ] An active user changes their own password only after current-password confirmation; session/token invalidation follows the Product Brief.
- [ ] Suspension is not blocked by open assignments: access is revoked, open work becomes unassigned, watchers are removed, and safe history remains.
- [ ] Unauthorized users receive the correct 401/403/404 behavior without cross-project data leakage.

## Project lifecycle and membership

- [ ] Admin creates a Draft project with a valid unique key; invalid/duplicate keys show useful errors.
- [ ] Project key becomes immutable after its first issue exists.
- [ ] Draft → Active, Active → Completed, Completed → Active, and non-archived → Archived transitions follow the decision matrix.
- [ ] Completed and Archived projects are readable but reject every domain mutation; Archived is terminal.
- [ ] Manager adds/removes members and changes project role within allowed rules.
- [ ] Removing a member with open responsibility is blocked or reconciled exactly as specified; no orphan assignee/watcher remains.
- [ ] A global member with project-manager context receives the intended project permissions consistently.
- [ ] Non-members cannot discover the project or its issues through list, direct URL, count, activity, dashboard, or Media URL.
- [ ] Project siyahısında key üzrə axtarış, status filter-i, pagination və boş nəticə vəziyyəti həm desktop cədvəlində, həm də narrow/mobile kart görünüşündə başa düşüləndir.
- [ ] Project detail səhifəsi key, lifecycle statusu, owner, member/task sayları və yalnız səlahiyyətli istifadəçiyə latest activity/actions göstərir; create/edit/member form-larında 422 validation error-ları daxil edilmiş təhlükəsiz dəyərləri saxlayır.
- [ ] `/api/v1/projects` və nested member endpoint-ləri named route, Sanctum ability, policy və Resource envelope contract-larını saxlayır; köhnə `/activate` və `/archive` API alias-ları yoxdur.

## Work-item core

- [ ] A project member can report Task, Bug, and Story in an Active project.
- [ ] Priority values are exactly Low, Medium, High, and Urgent with consistent validation/display ordering.
- [ ] A Subtask requires a same-project non-subtask parent and no deeper nesting is possible.
- [ ] Generated key is project-local and collision-free (for example `PAY-42`); concurrent creation does not duplicate it.
- [ ] Reporter is preserved; assignee is empty or exactly one current project member.
- [ ] All project members can browse all project work regardless of assignment.
- [ ] Reporter/authorized role edit/delete behavior matches policy; destructive actions show confirmation and correct conflicts.
- [ ] Completed/Archived projects block create/edit/delete/assign/status/comment/label/watch/upload mutations.
- [ ] Validation errors preserve safe entered values and do not expose internals.

## Workflow, backlog, and board

- [ ] Statuses are exactly Backlog, To Do, In Progress, Review, Done, Cancelled.
- [ ] Only approved transitions are offered and forged invalid transitions fail.
- [ ] `started_at` and `completed_at` semantics remain correct across forward/reopen/cancel flows.
- [ ] Backlog ordering is stable, scoped to one project, and persists after refresh; only a project manager can explicitly reorder.
- [ ] Reordering rejects stale/foreign/cross-project issue IDs and handles concurrent updates safely.
- [ ] Kanban columns match statuses; an assignee's allowed status move places their item at the target column end without permitting general reorder, and rejected moves roll back visually.
- [ ] Board remains usable by keyboard and on narrow screens; large project behavior is acceptable.

## Labels, watchers, notifications, and comments

- [ ] Manager creates/edits/deletes project-scoped labels with valid unique names/colors.
- [ ] Labels cannot cross projects; deletion/detachment behavior is clear and audited.
- [ ] Users watch/unwatch an issue; multiple watchers are supported without becoming assignees.
- [ ] Reporter and assignee auto-watch rules behave as specified without duplicate watches.
- [ ] Relevant changes create deduplicated in-app notifications; actor does not receive noisy self-notifications unless explicitly required.
- [ ] Notification list, unread count, read/read-all, and destination authorization work.
- [ ] Project members add/edit/delete comments according to ownership/role rules.
- [ ] Comment content is escaped/sanitized and private project comments never leak.

## Central Media

- [ ] All task files/images use the Media module; no controller writes directly to a disk.
- [ ] Allowed file succeeds and displays safe metadata; disallowed extension/MIME, mismatch, empty, oversized, or malformed filename fails.
- [ ] Storage uses generated opaque names and private paths.
- [ ] Preview/download checks current project authorization every time and sets safe response headers.
- [ ] Guessing another project's Media ID/path returns no data.
- [ ] If one item in a multi-file request fails, no item from that request remains stored or associated.
- [ ] Deleting a task association cleans its Media record/binary after commit, and failure does not leave a broken DB/file state.
- [ ] Images are not embedded from a public storage URL.

## Search, filters, Activity, and Dashboard

- [ ] Search/filter URL state is signed/validated and survives refresh/share without arbitrary query injection.
- [ ] Filters cover status, type, assignee/unassigned, reporter, label, date/due-state, and text; there is no arbitrary watcher filter.
- [ ] Pagination/sort remain project-scoped and stable; empty/no-result states are clear.
- [ ] Activity shows authorized, meaningful, sanitized changes with safe old/new values and no hidden-project metadata.
- [ ] Dashboard counts match accessible data and provide assigned, reported, My Watched Work, overdue, and recent work queues.
- [ ] Manager summaries do not reveal unrelated projects.

## UI, resilience, accessibility

- [ ] Shared navigation, breadcrumbs, flash/errors, forms, tables/cards, empty states, and confirmation patterns are consistent.
- [ ] The four approved Livewire components work and expose accessible loading/error feedback.
- [ ] Core navigation/detail/forms remain understandable with JavaScript disabled; only explicitly enhanced interactions degrade.
- [ ] Tab order, focus visibility/restoration, labels, error associations, status announcements, contrast, and keyboard actions are usable.
- [ ] No internal exception, SQL, path, token, stack trace, or secret is rendered in failure pages.
- [ ] 404/403/409/422/429/500 behavior is distinguishable and helpful without leaking data.

## Evidence record

Record browser/OS, viewport, database profile, build identifier, test users/roles (not passwords/tokens), date, result, screenshots for failures, and linked defect/TASK-ID. Do not mark TF-1003 verified while any required box is unchecked.
