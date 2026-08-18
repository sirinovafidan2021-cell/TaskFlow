# Migration Verification Checklist

This checklist is the verification record for Task 1.2. It must be completed
only with explicit approval before migrations are run against any database.

## SQLite test baseline

- [ ] Configure the approved test connection to SQLite `:memory:`.
- [ ] Run the complete migration set once.
- [ ] Confirm `projects`, `project_members`, `tasks`, `task_comments`,
  `task_attachments`, and `activity_log` exist.
- [ ] Confirm project and task soft-delete columns, foreign keys, unique keys,
  and composite indexes match their module migrations.
- [ ] Roll back the complete migration set and confirm all created tables are
  removed cleanly.

## Approved production database verification

- [ ] Confirm the approved MySQL or MariaDB connection before any command.
- [ ] Run the complete migration set once in the approved environment.
- [ ] Confirm the same tables, foreign keys, unique keys, and composite indexes
  as the SQLite baseline.
- [ ] Confirm the Activitylog table uses the configured connection and table
  name, including `event` and `batch_uuid` columns.
- [ ] Roll back only in an explicitly approved disposable environment.
