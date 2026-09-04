# TaskFlow release handoff — 2026-09-04

## Verified product

TaskFlow is a single-organization Laravel issue tracker. Projects own members and project-local issue keys; Tasks own one reporter, zero/one assignee, fixed type/priority/workflow, labels, watchers, comments, rank/backlog/board, and scoped filters. Media is private and owned by the Media module. Activity and notifications are sanitized and visibility-scoped. Web uses sessions, API uses Sanctum abilities, and policies plus services enforce project membership and read-only lifecycle rules.

## Architecture and authorization

Routes/controllers validate and authorize, DTOs carry validated input, services own invariants/transactions, repositories own persistence, and API Resources/Blade own presentation. The only Livewire components are TaskFilters, TaskStatusSelector, TaskCommentForm, and QuickTaskCreate. Accepted direct module dependencies remain documented in `TRACEABILITY_MATRIX.md`.

## Verification evidence

- Pest SQLite: 158 tests, 989 assertions passing.
- Playwright desktop/mobile: 8 critical journeys passing via `npm run e2e`.
- Composer validation and Vite production build passing.
- Manual Web/API local acceptance used disposable SQLite fixtures; no production data, tokens, or media paths were retained.

## Limits and next gate

MySQL compatibility remains an explicitly approved optional environment gate. TF-103 deferred verification remains recorded. Roadmap items, adapters/events, and excluded product features remain deferred; do not implement them without a new approved task.
