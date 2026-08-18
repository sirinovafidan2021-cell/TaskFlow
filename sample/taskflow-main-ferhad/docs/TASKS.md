# TaskFlow roadmap

## Milestone 0 — Baseline

```text
TASK-000 Inspect clean Laravel baseline — completed
TASK-001 Create repository instruction/context docs — current task
```

## Milestone 1 — Infrastructure

```text
TASK-010 Install nwidart/laravel-modules
TASK-011 Create Projects, Tasks, Activity, Dashboard module shells
TASK-012 Install/configure Laravel API + Sanctum
TASK-013 Install Livewire
TASK-014 Install spatie/laravel-permission
TASK-015 Configure Permission package and User role support
TASK-016 Define global roles and permissions
TASK-017 Install/configure spatie/laravel-activitylog
TASK-018 Install/configure Pest
TASK-019 Implement minimal Blade session authentication
TASK-020 Create shared authenticated Blade layout
```

## Milestone 2 — Projects

```text
TASK-030 ProjectStatus enum
TASK-031 projects migration
TASK-032 project_members migration
TASK-033 Project model
TASK-034 ProjectMember model
TASK-035 Project DTOs
TASK-036 ProjectRepositoryInterface
TASK-037 EloquentProjectRepository
TASK-038 ProjectMemberRepositoryInterface
TASK-039 EloquentProjectMemberRepository
TASK-040 ProjectService
TASK-041 ProjectMemberService
TASK-042 ProjectPolicy
TASK-043 Web project list/detail
TASK-044 Web create/edit/archive
TASK-045 Web member management
TASK-046 Projects API
TASK-047 Project members API
TASK-048 Critical Projects tests
```

## Milestone 3 — Tasks

```text
TASK-050 TaskStatus enum
TASK-051 TaskPriority enum
TASK-052 tasks migration
TASK-053 Task model
TASK-054 Task number generation strategy
TASK-055 Task DTOs
TASK-056 TaskRepositoryInterface
TASK-057 EloquentTaskRepository
TASK-058 TaskService
TASK-059 TaskAssignmentService
TASK-060 TaskStatusService
TASK-061 TaskPolicy
TASK-062 Web list
TASK-063 Task filters
TASK-064 TaskFilters Livewire
TASK-065 Web create/edit/detail
TASK-066 Task assignment
TASK-067 Task status update
TASK-068 TaskStatusSelector Livewire
TASK-069 QuickTaskCreate Livewire
TASK-070 Tasks API CRUD
TASK-071 Task status API
TASK-072 Task assignee API
TASK-073 Critical Tasks tests
```

## Milestone 4 — Comments and Attachments

```text
TASK-080 task_comments migration/model
TASK-081 Comment repository/service
TASK-082 Comment Web endpoints
TASK-083 TaskCommentForm Livewire
TASK-084 Comments API
TASK-085 task_attachments migration/model
TASK-086 Attachment repository/service
TASK-087 Web upload/delete/download
TASK-088 Attachments API
TASK-089 Attachment security tests
```

## Milestone 5 — Activity

```text
TASK-090 Activity convention
TASK-091 Project activity logging
TASK-092 Task activity logging
TASK-093 Service-level meaningful logs
TASK-094 Activity query/service
TASK-095 Activity Web
TASK-096 Activity API
TASK-097 Activity security tests
```

Canonical events: `project.created`, `project.updated`, `project.archived`, `project.member_added`, `project.member_removed`, `task.created`, `task.updated`, `task.assigned`, `task.status_changed`, `task.deleted`, `comment.created`, `comment.deleted`, `attachment.uploaded`, `attachment.deleted`. Never log passwords, plain-text tokens, secrets, or credentials.

## Milestone 6 — Dashboard

```text
TASK-100 DashboardService
TASK-101 Role-aware dashboard scope
TASK-102 Blade dashboard
TASK-103 Dashboard summary API
TASK-104 My-tasks API
TASK-105 Overdue API
TASK-106 Livewire integration review
TASK-107 Dashboard tests
```

## Milestone 7 — Stabilization

```text
TASK-110 N+1/query review
TASK-111 Security review
TASK-112 API consistency review
TASK-113 Test-gap review
TASK-114 Pint/code-style pass
TASK-115 Manual browser checklist
TASK-116 Postman/API checklist
TASK-117 Final architecture docs review
TASK-118 Definition of Done audit
```

## Refactor phase

```text
REFACTOR-001 Find Tasks -> Projects direct model usage
REFACTOR-002 Create ProjectAccessInterface
REFACTOR-003 Move task creation project access behind contract
REFACTOR-004 Replace Dashboard direct queries with metrics contracts
REFACTOR-005 Move suitable Activity logging to event listeners
REFACTOR-006 Introduce cross-module DTOs
REFACTOR-007 Document dependency graph
REFACTOR-008 Test with fake module-facing implementation
REFACTOR-009 Investigate module disable scenario
REFACTOR-010 Compare before/after architecture
```
