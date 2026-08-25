# TaskFlow documentation index

This directory contains the target specification and the execution plan for rebuilding the inherited Ehmed implementation. No document may claim the application is complete until the implementation gates have passed.

Everything an AI agent needs to plan and implement the approved scope is contained in this repository. External `docs-rewrite` folders, previous chats, other TaskFlow copies, and legacy handoffs are not sources of truth.

## Sources of truth

| Document | Purpose | When to read |
| --- | --- | --- |
| `PROJECT_BRIEF.md` | Final minimal-Jira product scope and business rules | Every product/domain task |
| `DECISIONS.md` | Accepted decisions that agents must not reopen silently | Every task |
| `ARCHITECTURE.md` | Target modules, layers, flows, and dependency rules | Every code task |
| `MEDIA.md` | Central Media module and secure file/image lifecycle | Any media task |
| `API_CONVENTIONS.md` | Target `/api/v1` contract, abilities, resources, and errors | Any API task |
| `SECURITY.md` | Baseline threat model and mandatory controls | Every auth/data/file task |
| `TEST_STRATEGY.md` | Pest, Livewire, Playwright, database, and quality strategy | Every implementation task |
| `IMPLEMENTATION_PLAN.md` | Ordered tasks, dependencies, actions, and acceptance criteria | Active implementation work |
| `TASKS.md` | Mutable execution tracker and evidence register | Start/end of every task |

## Supporting documents

| Document | Purpose |
| --- | --- |
| `CURRENT_STATE_AUDIT.md` | Read-only diagnosis of the inherited Ehmed code; not target behavior |
| `CODEX_WORKFLOW.md` | AI/developer task protocol |
| `MANUAL_BROWSER_CHECKLIST.md` | Final human browser acceptance |
| `POSTMAN_API_CHECKLIST.md` | Final manual API acceptance |
| `ROADMAP.md` | Deferred loose-coupling and advanced-security work only |

## Document lifecycle

- Product and architecture decisions belong in the named source-of-truth document, not in ad hoc reports.
- Implementation progress belongs only in `TASKS.md`.
- Test output and manual evidence must include a date/runtime and must never be copied forward as if still current.
- `CURRENT_STATE_AUDIT.md` is updated only when inherited-state findings are corrected or disproved.
- A release handoff is created only after the final implementation task; there is intentionally no current `V1_HANDOFF.md`.

## Agent entry point

A newly connected Codex agent must start at the root `AGENTS.md`, then follow its reading order and `CODEX_WORKFLOW.md`. Work selection always comes from the current `TASKS.md` state plus dependencies in `IMPLEMENTATION_PLAN.md`; the agent must not invent a parallel plan or begin `ROADMAP.md` work.

## Removed legacy documents

- The former `V1_HANDOFF.md` was invalid because it declared an incomplete system complete.
- The former `LEARNING_GUIDE.md` described stale implementation details and duplicated architecture. Learning explanations now belong in task reports and authoritative architecture documents.
