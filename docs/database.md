# Verilənlər bazası

Bu sənəd migration-ları mənbə kimi istifadə edir. Modules eyni verilənlər bazasını paylaşır, lakin öz migration-larına sahibdir.

| Cədvəl | Məqsəd və vacib sahələr |
| --- | --- |
| users | Primary key id; name, unique email, nullable email_verified_at, password, remember_token və timestamps. |
| password_reset_tokens | Email primary key, token və created_at. |
| sessions | Session ID primary key, optional user_id, IP, user agent, payload, last_activity. |
| projects | id, name, unique slug, nullable description, status, owner_id FK users, starts_at, due_at, timestamps, deleted_at. owner/status və status/due indeksləri var. |
| project_members | id, project_id FK projects cascade delete, user_id FK users restrict delete, member_role, joined_at; project_id+user_id unikaldır. |
| tasks | id, nullable unique number, project_id, creator_id, optional assignee_id FK-ləri, title, description, status, priority, due_at, started_at, completed_at, soft delete və iş yükü indeksləri. |
| task_comments | id, task_id və user_id FK, body, timestamps, soft delete. |
| task_attachments | id, task_id və uploaded_by FK, disk, unique path, original_name, mime_type, size, timestamps. |
| activity_log | Spatie: id, log_name, description, polymorphic subject/causer, event, JSON properties, batch_uuid, timestamps. |
| personal_access_tokens | Sanctum: id, polymorphic tokenable, name, unique token, abilities, last_used_at, expires_at. |
| permissions, roles və pivot-lar | Spatie Permission rol və icazə əlaqələri. |
| cache, cache_locks | Cache məlumatları. |
| jobs, job_batches, failed_jobs | Database queue infrastrukturu. |

Əlaqələr: User owner kimi Projects ilə, Project project_members vasitəsilə Users ilə, Project Tasks ilə, Task creator və optional assignee kimi Users ilə, Task isə comments və attachments ilə əlaqəlidir. Project və Task soft delete istifadə edir; attachment soft delete deyil.
