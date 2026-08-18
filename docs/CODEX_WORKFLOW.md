# Codex workflow

```text
1. Select one TASK-ID.
2. Codex reads repository context.
3. Codex implements only that task.
4. Codex reports changed files.
5. Developer reviews implementation.
6. Developer learns/explains the code.
7. Relevant checks are run.
8. Only then move to the next TASK-ID.
```

For normal implementation tasks, read `AGENTS.md`, `docs/PROJECT_BRIEF.md`, `docs/ARCHITECTURE.md`, `docs/API_CONVENTIONS.md`, and `docs/TASKS.md` first.

Never request “build the entire TaskFlow project”. Implement one TASK-ID at a time.

Unless the active task explicitly allows an operation, forbid: `.env` access/edit, Git commands, dependency installation, migration execution, seeder execution, destructive database commands, real browser automation, production deployment, and unrelated changes.
