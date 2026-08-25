# TaskFlow execution tracker

## Purpose

This is the only mutable implementation status and evidence tracker. Product scope lives in `PROJECT_BRIEF.md`; decisions live in `DECISIONS.md`; task definitions and acceptance criteria live in `IMPLEMENTATION_PLAN.md`.

The inherited application's old “v1 complete” status is invalid. Code work below remains `pending` until its own acceptance evidence is recorded.

## Status vocabulary

- `pending` — not started.
- `in_progress` — the single active task.
- `blocked` — cannot continue; the blocker and safe checks are recorded.
- `verified` — all acceptance criteria passed with evidence.

Rules:

- At most one task may be `in_progress`.
- Do not mark a phase verified from task count or code presence.
- A manual inspection can support, but cannot replace, an executable check when one exists.
- Dependency installation, migration/seed execution, Git operations, and Playwright runs require the active task's explicit authorization.
- Roadmap work cannot be added here before TF-1004 is verified.

## Active task

`none` — the canonical documentation baseline is verified; no code implementation task has started.

## Implementation status

| Phase | Tasks | Status | Gate/result |
|---|---|---|---|
| 0. Baseline and traceability | TF-000–TF-002 | pending | TF-000 verified; runtime baseline and traceability still required |
| 1. Test/factory/seed foundation | TF-100–TF-103 | pending | Portable Pest, decomposed regression suite, factories/seeders, architecture guards |
| 2. Architecture/correctness repairs | TF-200–TF-205 | pending | Reversible schema, module routes, boundaries, exceptions, authorization matrix |
| 3. Accounts and authentication | TF-300–TF-303 | pending | Internal accounts, session auth, token bootstrap/lifecycle, security audit |
| 4. Projects | TF-400–TF-402 | pending | Project keys/lifecycle, membership integrity, Web/API completion |
| 5. Central Media | TF-500–TF-503 | pending | Central binary ownership and migrated task associations |
| 6. Minimal Jira domain | TF-600–TF-610 | pending | Issue keys/types/subtasks, labels, watchers, backlog, board, comments |
| 7. UI/Livewire/JS | TF-700–TF-705 | pending | Shared UI and four approved Livewire components |
| 8. Activity/notifications/Dashboard | TF-800–TF-802 | pending | Canonical audit, inbox, useful work queues/metrics |
| 9. API contract | TF-900–TF-901 | pending | Complete named/versioned contract and reference collection |
| 10. Stabilization/release | TF-1000–TF-1004 | pending | Automated/manual gates and truthful release handoff |

## Task ledger

### Phase 0

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-000 | verified | none | 2026-08-24: 2 root + 15 `docs` Markdown files verified in the target repository; 51 plan IDs have dependency/action/acceptance and matching tracker dependencies; broken links and legacy files absent; product/API/checklist/decision consistency audit passed |
| TF-001 | pending | TF-000 | — |
| TF-002 | pending | TF-000 | — |

### Phase 1

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-100 | pending | TF-001 | — |
| TF-101 | pending | TF-100 | — |
| TF-102 | pending | TF-100 | — |
| TF-103 | pending | TF-100 | — |

### Phase 2

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-200 | pending | TF-100, TF-102 | — |
| TF-201 | pending | TF-103 | — |
| TF-202 | pending | TF-103 | — |
| TF-203 | pending | TF-202 | — |
| TF-204 | pending | TF-201 | — |
| TF-205 | pending | TF-202, TF-204 | — |

### Phase 3

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-300 | pending | TF-102, TF-204, TF-205 | — |
| TF-301 | pending | TF-300 | — |
| TF-302 | pending | TF-300, TF-301 | — |
| TF-303 | pending | TF-300, TF-302 | — |

### Phase 4

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-400 | pending | TF-200, TF-203, TF-204, TF-205 | — |
| TF-401 | pending | TF-205, TF-300, TF-400 | — |
| TF-402 | pending | TF-201, TF-203, TF-400, TF-401 | — |

### Phase 5

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-500 | pending | TF-200, TF-201, TF-202 | — |
| TF-501 | pending | TF-203, TF-204, TF-500 | — |
| TF-502 | pending | TF-501 | — |
| TF-503 | pending | TF-502, TF-205 | — |

### Phase 6

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-600 | pending | TF-400, TF-202 | — |
| TF-601 | pending | TF-600, TF-203 | — |
| TF-602 | pending | TF-205, TF-401, TF-600, TF-601 | — |
| TF-603 | pending | TF-602, TF-401 | — |
| TF-604 | pending | TF-602, TF-603 | — |
| TF-605 | pending | TF-400, TF-602 | — |
| TF-606 | pending | TF-300, TF-401, TF-602, TF-603 | — |
| TF-607 | pending | TF-604 | — |
| TF-608 | pending | TF-604, TF-607 | — |
| TF-609 | pending | TF-601, TF-605, TF-606, TF-607 | — |
| TF-610 | pending | TF-602, TF-606 | — |

### Phase 7

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-700 | pending | TF-402, TF-503, TF-608, TF-609, TF-610 | — |
| TF-701 | pending | TF-609, TF-700 | — |
| TF-702 | pending | TF-604, TF-700 | — |
| TF-703 | pending | TF-610, TF-700 | — |
| TF-704 | pending | TF-601, TF-605, TF-602, TF-700 | — |
| TF-705 | pending | TF-503, TF-608, TF-700 | — |

### Phase 8

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-800 | pending | TF-400 through TF-610 | — |
| TF-801 | pending | TF-606, TF-700 | — |
| TF-802 | pending | TF-609, TF-800, TF-704 | — |

### Phase 9

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-900 | pending | TF-302, TF-402, TF-503, TF-600 through TF-610, TF-800, TF-802 | — |
| TF-901 | pending | TF-900 | — |

### Phase 10

| ID | Status | Depends on | Evidence |
|---|---|---|---|
| TF-1000 | pending | all implementation tasks through TF-900 | — |
| TF-1001 | pending | TF-700 through TF-705, TF-1000 | — |
| TF-1002 | pending | TF-1000, TF-1001 | — |
| TF-1003 | pending | TF-901, TF-1002 | — |
| TF-1004 | pending | TF-1003 | — |

## Evidence log template

Append one entry per verification attempt:

```text
Date/time:
Task ID:
Commit/worktree state (if Git is authorized):
Commands/checks:
Result:
Files/areas reviewed:
Remaining risk or blocker:
Reviewer:
```

## Known pre-implementation unknowns

- Delivered source has lock files but no installed `vendor`/`node_modules`; TF-001 must verify the runtime rather than assuming it.
- Existing test configuration is MySQL-specific; TF-100 decides and verifies the portable default.
- Existing application data and migration history are unknown; TF-200 must define a preservation/rollback route before changing schema.
- Playwright is not currently established in the delivered tree; TF-1001 owns dependency/configuration approval and implementation.
- Deployment proxy, queue, object-storage, mail, and production database constraints are not yet documented; relevant tasks must record them before relying on those services.

## Documentation evidence

Do not mark TF-000 verified until the target repository contains the full canonical set in `docs/README.md`, links resolve, terminology checks pass, and these obsolete documents are absent:

- `docs/V1_HANDOFF.md`
- `docs/LEARNING_GUIDE.md`
