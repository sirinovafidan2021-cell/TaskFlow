# TaskFlow — Junior Developer-lər üçün Modular Task Management layihəsi

## 1. Layihənin məqsədi

TaskFlow şirkət daxilində project və task-ların idarə olunması üçün hazırlanacaq orta səviyyəli modular monolith layihəsidir.

Layihənin məqsədi yalnız CRUD yazmaq deyil. Bu layihə vasitəsilə aşağıdakı mövzular praktiki şəkildə öyrəniləcək:

- Laravel layihə strukturu
- `nwidart/laravel-modules`
- Modular monolith yanaşması
- Web və REST API development
- Blade və standart JavaScript
- Məhdud və məqsədli Livewire istifadəsi
- Service layer
- Repository pattern
- DTO və enum-lar
- Form Request validation
- Policy, role və permission
- Laravel Sanctum
- Spatie Activitylog
- Events və listeners
- Database transaction
- API Resource
- Pagination, filter və sort
- Pest ilə hədəfli testlər
- Codex agent ilə nəzarətli development

Layihə sadə “task əlavə et və siyahıda göstər” sistemi olmayacaq. Project üzvləri, task assignment, status keçidləri, comment, attachment, activity log, dashboard və API token sistemi olacaq.

---

# 2. Ümumi texniki stack

## Backend

- PHP 8.3 və ya 8.4
- Laravel 13
- `nwidart/laravel-modules`
- Laravel Sanctum
- Livewire
- Spatie Laravel Activitylog
- Spatie Laravel Permission
- Pest
- Laravel Pint

## Frontend

- Blade
- Tailwind CSS
- Standart JavaScript
- Vite
- Məhdud Livewire komponentləri

İstifadə edilməyəcək:

- React
- Vue
- Inertia
- SPA arxitekturası
- Ayrı frontend repository
- Bütün səhifələrin Livewire ilə yazılması

## Database

Project owner tərəfindən müəyyən edilmiş MySQL, MariaDB və ya SQLite istifadə edilə bilər.

Əsas qaydalar:

- Hər modul öz migration-larını saxlayacaq.
- Eyni database istifadə ediləcək.
- Cross-module foreign key-lərə icazə veriləcək.
- Foreign key və index-lər düzgün qurulacaq.
- Project və task-larda soft delete istifadə ediləcək.
- Migration-ları işlətmək üçün supervisor icazəsi alınacaq.

---

# 3. İstifadə ediləcək paketlər

Nəzərdə tutulan paketlər:

```bash
composer require nwidart/laravel-modules
php artisan install:api
composer require livewire/livewire
composer require spatie/laravel-activitylog
composer require spatie/laravel-permission
```

Bu command-lar junior tərəfindən icazəsiz işlədilməməlidir. Dependency və lockfile dəyişiklikləri supervisor təsdiqi ilə aparılmalıdır.

Paketlərin məqsədi:

| Paket | Məqsəd |
| --- | --- |
| Nwidart Modules | Business feature-ları modullara ayırmaq |
| Laravel Sanctum | API token authentication |
| Livewire | Seçilmiş interaktiv UI komponentləri |
| Spatie Activitylog | Model və business dəyişikliklərini loglamaq |
| Spatie Permission | Role və permission idarəetməsi |
| Pest | Feature və unit testlər |

---

# 4. Layihənin istifadəçi rolları

Sistemdə üç əsas rol olacaq:

## Admin

- Bütün project-ləri görə bilər.
- İstifadəçilərin rollarını idarə edə bilər.
- Bütün task-ları görə və dəyişə bilər.
- Activity log-a tam çıxışı var.
- API token-ləri idarə edə bilər.

## Project manager

- Project yarada bilər.
- Öz project-lərini dəyişə bilər.
- Project-ə member əlavə edə bilər.
- Task yarada və assign edə bilər.
- Task statuslarını dəyişə bilər.
- Project və task activity log-larını görə bilər.

## Member

- Üzv olduğu project-ləri görə bilər.
- Özünə assign edilmiş task-ları görə bilər.
- İcazə verilən status dəyişikliklərini edə bilər.
- Task-a comment əlavə edə bilər.
- Başqa project-lərin məlumatlarını görə bilməz.

Role idarəetməsi üçün `spatie/laravel-permission`, konkret record üzərində authorization üçün isə Laravel Policies istifadə ediləcək.

---

# 5. Modul strukturu

Layihədə aşağıdakı Nwidart modulları olacaq:

```text
Modules/
├── Projects/
├── Tasks/
├── Activity/
└── Dashboard/
```

Authentication və əsas `User` modeli Laravel host tətbiqində qala bilər.

Ayrıca `Api` və ya `Web` adlı modul yaradılmayacaq. Hər business modul öz web və API route-larına sahib olacaq.

Məsələn:

```text
Modules/Tasks/routes/web.php
Modules/Tasks/routes/api.php
```

---

# 6. Modulların bir-birini çağırması

Bu layihənin ilk development mərhələsində modullar bir-birini birbaşa çağıra biləcək.

Məsələn, `Tasks` modulu `Projects` modulunun modelindən istifadə edə bilər:

```php
use Modules\Projects\Models\Project;
```

Task modeli Project modelinə birbaşa relation yaza bilər:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Projects\Models\Project;

public function project(): BelongsTo
{
    return $this->belongsTo(Project::class);
}
```

Task service Project modelini birbaşa yoxlaya bilər:

```php
$project = Project::query()
    ->with('members')
    ->findOrFail($data->projectId);
```

Dashboard da ilk mərhələdə Project və Task modellərindən birbaşa istifadə edə bilər:

```php
$activeProjects = Project::query()
    ->where('status', ProjectStatus::Active)
    ->count();

$overdueTasks = Task::query()
    ->whereNotIn('status', [
        TaskStatus::Done,
        TaskStatus::Cancelled,
    ])
    ->where('due_at', '<', now())
    ->count();
```

Bu yanaşma tight coupling yaradır və ideal best practice deyil. Ancaq hazırkı tədris mərhələsində qəbul edilir.

Səbəblər:

- Modullar ayrıca package kimi publish edilməyəcək.
- Modullar ayrı server-lərdə deploy edilməyəcək.
- Modullar runtime-da silinməyəcək.
- `Projects` və `Tasks` modulları həmişə aktiv olacaq.
- Juniorlar əvvəlcə əsas Laravel və modul strukturunu öyrənəcəklər.
- Contract, adapter və decoupling mövzuları layihə bitdikdən sonra ayrıca öyrədiləcək.

Yenə də bir qayda qorunmalıdır:

> Başqa modulun datasını birbaşa dəyişmək əvəzinə mümkün qədər həmin modulun service class-ı çağırılmalıdır.

Məsələn, `Tasks` modulu Project member əlavə etməməlidir:

```php
// Yanlış
ProjectMember::create([...]);
```

Bunun əvəzinə Projects modulunun konkret service-i çağırıla bilər:

```php
use Modules\Projects\Services\ProjectMemberService;

$this->projectMemberService->addMember(
    project: $project,
    user: $user,
    actor: $actor,
);
```

Bu hələ də direct coupling-dir, çünki Tasks konkret `ProjectMemberService` class-ını tanıyır. Amma business məsuliyyəti düzgün modulda qalır.

---

# 7. Modul daxili folder strukturu

Məsələn, `Tasks` modulu:

```text
Modules/Tasks/
├── app/
│   ├── Data/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Web/
│   │   │   └── Api/V1/
│   │   ├── Requests/
│   │   │   ├── Web/
│   │   │   └── Api/V1/
│   │   └── Resources/
│   ├── Livewire/
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   ├── Repositories/
│   │   ├── Contracts/
│   │   └── Eloquent/
│   ├── Services/
│   └── Support/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── assets/js/
│   └── views/
├── routes/
│   ├── web.php
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
└── module.json
```

Bütün folder-lər əvvəlcədən boş yaradılmamalıdır. İlk real class lazım olduqda yaradılmalıdır.

---

# 8. Layer-lərin məsuliyyətləri

Layihədə aşağıdakı flow istifadə ediləcək:

```text
Route
  → Controller və ya Livewire component
  → Form Request / Validation
  → Policy
  → DTO
  → Service
  → Repository
  → Model
  → View / Resource / Redirect
```

## Controller

Controller HTTP request-i idarə edir.

Controller-in vəzifələri:

- Request qəbul etmək
- Authorization yoxlamaq
- Validated data almaq
- DTO yaratmaq
- Service çağırmaq
- View, redirect və ya API Resource qaytarmaq

Controller daxilində yazılmamalıdır:

- Uzun Eloquent query
- Transaction
- Status transition qaydaları
- Permission qərarları
- Activity log business məntiqi

## Service

Service konkret use-case-i idarə edir.

Məsələn:

- Project yaratmaq
- Project-ə member əlavə etmək
- Task yaratmaq
- Task assign etmək
- Task statusunu dəyişmək
- Comment əlavə etmək
- Attachment yükləmək

Service:

- Business rule-ları yoxlayır.
- Repository çağırır.
- Lazım olduqda başqa modulun model və service-lərini çağırır.
- Transaction idarə edir.
- Event dispatch edir.
- Activity log yaradır.

## Repository

Repository Eloquent və database əməliyyatlarını idarə edir.

Repository:

- Query yazır.
- Filter və sort tətbiq edir.
- Pagination edir.
- Eager loading tətbiq edir.
- Model yaradır.
- Model yeniləyir.
- Model silir.

Repository-də olmamalıdır:

- Authorization
- Role yoxlaması
- Status keçidi qərarı
- Business exception
- Activity log qərarı
- HTTP response

## DTO

DTO controller ilə service arasında strukturlaşdırılmış data daşıyır.

```php
final readonly class CreateTaskData
{
    public function __construct(
        public int $projectId,
        public string $title,
        public ?string $description,
        public ?int $assigneeId,
        public TaskPriority $priority,
        public ?CarbonImmutable $dueAt,
    ) {}
}
```

Associative array əvəzinə DTO istifadə edilməsi typo və qarışıq field problemlərini azaldır.

---

# 9. Repository pattern

Hər əsas aggregate üçün repository interface və Eloquent implementation yaradılacaq.

Məsələn:

```text
TaskRepositoryInterface
EloquentTaskRepository
```

## Interface

```php
interface TaskRepositoryInterface
{
    public function paginate(
        TaskFiltersData $filters,
        User $actor,
    ): LengthAwarePaginator;

    public function findOrFail(int $id): Task;

    public function create(array $attributes): Task;

    public function update(
        Task $task,
        array $attributes,
    ): Task;

    public function delete(Task $task): void;
}
```

## Eloquent implementation

```php
final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function paginate(
        TaskFiltersData $filters,
        User $actor,
    ): LengthAwarePaginator {
        return Task::query()
            ->with([
                'project',
                'assignee',
                'creator',
            ])
            ->when(
                $filters->search,
                fn ($query, $search) => $query
                    ->where('title', 'like', "%{$search}%"),
            )
            ->when(
                $filters->status,
                fn ($query, $status) => $query
                    ->where('status', $status),
            )
            ->latest()
            ->paginate($filters->perPage);
    }

    public function create(array $attributes): Task
    {
        return Task::query()->create($attributes);
    }
}
```

## Provider binding

```php
public function register(): void
{
    $this->app->bind(
        TaskRepositoryInterface::class,
        EloquentTaskRepository::class,
    );
}
```

Repository interface istifadə etmək cross-module decoupling dərsi deyil. Bu, eyni modul daxilində HTTP, business və persistence layer-lərinin ayrılması üçün istifadə olunur.

Cross-module contract mövzusu layihənin sonrakı refactor mərhələsində keçiriləcək.

---

# 10. Service nümunəsi

```php
final class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {}

    public function create(
        User $actor,
        CreateTaskData $data,
    ): Task {
        $project = Project::query()
            ->with('members')
            ->findOrFail($data->projectId);

        if ($project->status !== ProjectStatus::Active) {
            throw ValidationException::withMessages([
                'project_id' => 'Archived project üçün task yaradıla bilməz.',
            ]);
        }

        if (! $project->hasMember($actor)) {
            throw new AuthorizationException(
                'Bu project üçün task yaratmaq icazəniz yoxdur.',
            );
        }

        return DB::transaction(function () use ($actor, $data) {
            $task = $this->tasks->create([
                'project_id' => $data->projectId,
                'creator_id' => $actor->id,
                'assignee_id' => $data->assigneeId,
                'title' => $data->title,
                'description' => $data->description,
                'status' => TaskStatus::Todo,
                'priority' => $data->priority,
                'due_at' => $data->dueAt,
            ]);

            TaskCreated::dispatch(
                taskId: $task->id,
                actorId: $actor->id,
            );

            return $task;
        });
    }
}
```

Bu nümunədə `Tasks` modulu `Project` modelini birbaşa çağırır. Bu, layihənin ilk mərhələsi üçün qəbul edilir.

---

# 11. Projects modulu

Projects modulu project və project member məlumatlarına sahib olacaq.

## `projects` cədvəli

```text
id
name
slug
description
status
owner_id
starts_at
due_at
created_at
updated_at
deleted_at
```

Statuslar:

```text
draft
active
completed
archived
```

Index-lər:

```text
unique(slug)
index(owner_id, status)
index(status, due_at)
```

## `project_members` cədvəli

```text
id
project_id
user_id
member_role
joined_at
created_at
updated_at
```

Constraint-lər:

```text
foreign key project_id → projects.id
foreign key user_id → users.id
unique(project_id, user_id)
```

## Web feature-lar

- Project list
- Project detail
- Project create
- Project edit
- Project archive
- Project member list
- Member əlavə etmək
- Member çıxarmaq
- Project daxilində task-ları göstərmək

## API endpoint-ləri

```text
GET    /api/v1/projects
POST   /api/v1/projects
GET    /api/v1/projects/{project}
PUT    /api/v1/projects/{project}
DELETE /api/v1/projects/{project}

GET    /api/v1/projects/{project}/members
POST   /api/v1/projects/{project}/members
DELETE /api/v1/projects/{project}/members/{user}
```

## Service-lər

```text
ProjectService
ProjectMemberService
ProjectMetricsService
```

## Repository-lər

```text
ProjectRepositoryInterface
EloquentProjectRepository

ProjectMemberRepositoryInterface
EloquentProjectMemberRepository
```

---

# 12. Tasks modulu

## `tasks` cədvəli

```text
id
number
project_id
creator_id
assignee_id
title
description
status
priority
due_at
started_at
completed_at
created_at
updated_at
deleted_at
```

Task number:

```text
TSK-000001
```

Foreign key-lər:

```text
project_id  → projects.id
creator_id  → users.id
assignee_id → users.id
```

Index-lər:

```text
unique(number)
index(project_id, status)
index(assignee_id, status)
index(priority, due_at)
index(project_id, assignee_id, status)
```

## `task_comments` cədvəli

```text
id
task_id
user_id
body
created_at
updated_at
deleted_at
```

## `task_attachments` cədvəli

```text
id
task_id
uploaded_by
disk
path
original_name
mime_type
size
created_at
updated_at
```

## Task statusları

```text
todo
in_progress
review
done
cancelled
```

## Prioritetlər

```text
low
medium
high
urgent
```

Bunlar PHP enum kimi yazılmalıdır:

```php
enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';
    case Cancelled = 'cancelled';
}
```

## Status keçidləri

```text
todo        → in_progress, cancelled
in_progress → todo, review, cancelled
review      → in_progress, done
done        → yalnız manager tərəfindən yenidən açıla bilər
cancelled   → yalnız manager tərəfindən yenidən açıla bilər
```

Status keçidi controller-də yazılmamalıdır. `TaskStatusService` tərəfindən idarə edilməlidir.

## Service-lər

```text
TaskService
TaskAssignmentService
TaskStatusService
TaskCommentService
TaskAttachmentService
TaskMetricsService
```

## Repository-lər

```text
TaskRepositoryInterface
EloquentTaskRepository

TaskCommentRepositoryInterface
EloquentTaskCommentRepository

TaskAttachmentRepositoryInterface
EloquentTaskAttachmentRepository
```

---

# 13. API authentication

Bu layihədə Sanctum istifadə ediləcək.

Passport istifadə edilməyəcək, çünki:

- OAuth2 server lazım deyil.
- Üçüncü tərəf OAuth client-ləri yoxdur.
- Authorization Code flow lazım deyil.
- Refresh token sistemi tələb olunmur.
- Hazırda mobil tətbiq yoxdur.
- Personal access token kifayətdir.

## Authentication və authorization

Sanctum istifadəçinin kim olduğunu müəyyən edir.

Policy, permission və token ability isə həmin istifadəçinin nə edə biləcəyini müəyyən edir.

```text
Sanctum → istifadəçi kimdir?
Token ability → token nəyə icazə verir?
Spatie Permission → istifadəçinin ümumi rolu nədir?
Policy → konkret record üzərində əməliyyata icazə varmı?
```

## Token abilities

```text
projects:read
projects:write
tasks:read
tasks:write
comments:write
activity:read
dashboard:read
```

## Auth endpoint-ləri

```text
POST   /api/v1/auth/token
GET    /api/v1/me
DELETE /api/v1/auth/token
```

Token request:

```json
{
    "email": "junior@example.com",
    "password": "password",
    "device_name": "postman"
}
```

Token response:

```json
{
    "data": {
        "token": "plain-text-token",
        "abilities": [
            "projects:read",
            "tasks:read"
        ]
    }
}
```

Plain text token yalnız yaradıldığı anda göstərilməlidir.

---

# 14. API-lərin istifadə məqsədi

API aşağıdakı məqsədlərlə hazırlanacaq:

- Juniorların Postman ilə REST API praktika etməsi
- Gələcək daxili integration-lar
- Reporting script-ləri
- Avtomatlaşdırılmış task yaradılması
- Gələcək mobil tətbiqə hazırlıq
- Web və API arasında business logic paylaşımını öyrənmək

Blade səhifələri API-ni çağırmağa məcbur deyil.

Doğru flow:

```text
Web Controller ─────┐
                    ├── TaskService → TaskRepository
API Controller ─────┤
                    │
Livewire Component ─┘
```

Web və API controller-lərdə eyni business logic təkrar yazılmamalıdır.

---

# 15. Tasks API

```text
GET    /api/v1/tasks
POST   /api/v1/tasks
GET    /api/v1/tasks/{task}
PUT    /api/v1/tasks/{task}
DELETE /api/v1/tasks/{task}

PATCH  /api/v1/tasks/{task}/status
PATCH  /api/v1/tasks/{task}/assignee

GET    /api/v1/tasks/{task}/comments
POST   /api/v1/tasks/{task}/comments
DELETE /api/v1/tasks/{task}/comments/{comment}

GET    /api/v1/tasks/{task}/attachments
POST   /api/v1/tasks/{task}/attachments
DELETE /api/v1/tasks/{task}/attachments/{attachment}
```

---

# 16. API filter və pagination

Task API aşağıdakı parametrləri qəbul edəcək:

```text
GET /api/v1/tasks?
    search=api
    &status=in_progress
    &priority=high
    &project_id=10
    &assignee_id=5
    &due_before=2026-09-01
    &sort=-due_at
    &page=1
    &per_page=20
```

Filter data DTO ilə repository-yə ötürülməlidir:

```text
TaskFiltersData
```

`per_page` üçün maksimum limit olmalıdır:

```text
max: 100
```

List query-də eager loading istifadə edilməlidir:

```php
Task::query()->with([
    'project',
    'assignee',
    'creator',
]);
```

---

# 17. API response formatı

Single resource:

```json
{
    "data": {
        "id": 1,
        "number": "TSK-000001",
        "title": "Prepare API documentation",
        "status": "in_progress",
        "priority": "high"
    }
}
```

Pagination:

```json
{
    "data": [],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 100,
        "last_page": 5
    }
}
```

Validation error:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "title": [
            "The title field is required."
        ]
    }
}
```

API controller Eloquent modeli birbaşa qaytarmamalıdır.

İstifadə ediləcək resource-lar:

```text
ProjectResource
ProjectCollection
TaskResource
TaskCollection
TaskCommentResource
ActivityResource
DashboardSummaryResource
```

---

# 18. Activity log

Activity log üçün `spatie/laravel-activitylog` istifadə ediləcək.

Loglanacaq hadisələr:

```text
project.created
project.updated
project.archived
project.member_added
project.member_removed

task.created
task.updated
task.assigned
task.status_changed
task.deleted

comment.created
comment.deleted

attachment.uploaded
attachment.deleted
```

Activity məlumatında bunlar olmalıdır:

- Əməliyyatı edən istifadəçi
- Dəyişən entity
- Event adı
- Köhnə məlumat
- Yeni məlumat
- Project ID
- Task ID
- Tarix

Model dəyişiklikləri üçün `LogsActivity` trait istifadə edilə bilər:

```php
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly([
            'title',
            'status',
            'priority',
            'assignee_id',
            'due_at',
        ])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

Business mənalı hadisələr service daxilində loglana bilər:

```php
activity('tasks')
    ->causedBy($actor)
    ->performedOn($task)
    ->event('task.status_changed')
    ->withProperties([
        'old_status' => $oldStatus->value,
        'new_status' => $newStatus->value,
    ])
    ->log('Task status changed');
```

Password, token və secret məlumatlar loglanmamalıdır.

Activity modulu əsasən log-ların göstərilməsi və filter edilməsi üçün istifadə ediləcək:

```text
GET /activity
GET /tasks/{task}/activity

GET /api/v1/activity
GET /api/v1/tasks/{task}/activity
GET /api/v1/projects/{project}/activity
```

---

# 19. Dashboard

Dashboard ilk mərhələdə Project və Task modellərinə birbaşa query yaza bilər.

Bu project üçün qəbul edilir.

Dashboard göstəriciləri:

- Aktiv project sayı
- Arxiv project sayı
- Ümumi task sayı
- `todo` task sayı
- `in_progress` task sayı
- `review` task sayı
- Gecikmiş task sayı
- Bu gün tamamlanan task-lar
- Cari istifadəçinin task-ları
- Son fəaliyyətlər
- Project üzrə task paylanması

Query-lər controller-də yox, `DashboardService` və ya repository-də saxlanmalıdır.

```text
DashboardController
        ↓
DashboardService
        ↓
Project və Task modelləri/repository-ləri
```

API:

```text
GET /api/v1/dashboard/summary
GET /api/v1/dashboard/my-tasks
GET /api/v1/dashboard/overdue
```

---

# 20. Livewire istifadəsi

Sistem tam Livewire ilə yazılmayacaq.

Livewire aşağıdakı komponentlərdə istifadə ediləcək:

## QuickTaskCreate

Dashboard və project detail səhifəsində sürətli task yaratmaq.

## TaskFilters

Task siyahısında:

- Search
- Status
- Priority
- Project
- Assignee
- Due date

## TaskStatusSelector

Task detail səhifəsində statusu səhifəni tam refresh etmədən dəyişmək.

## TaskCommentForm

Task detail səhifəsində comment əlavə etmək.

Livewire qaydaları:

- Component business logic saxlamamalıdır.
- Component service çağırmalıdır.
- Authorization component daxilində yoxlanmalıdır.
- Repository component-dən birbaşa çağırılmamalıdır.
- Bütün CRUD Livewire-a keçirilməməlidir.

```text
Livewire Component
        ↓
Service
        ↓
Repository
```

---

# 21. Standart JavaScript

Standart JavaScript istifadə ediləcək:

- Delete confirmation
- Modal açmaq və bağlamaq
- Attachment preview
- Character counter
- Copy task number
- API token-i clipboard-a köçürmək
- Form field-lərini göstərmək/gizlətmək
- Project seçiminə görə assignee siyahısını dəyişmək

JavaScript daxilində business rule yazılmamalıdır.

Məsələn, JavaScript UI-də `done` statusunu gizlədə bilər, amma backend yenə status transition-u yoxlamalıdır.

---

# 22. Security

Juniorlar aşağıdakıları yoxlamalıdır:

- Form Request validation
- Policy authorization
- Role və permission
- Sanctum token abilities
- Rate limiting
- Mass assignment
- XSS
- CSRF
- File MIME və size validation
- Unauthorized attachment download
- Project membership
- Assignee-nin project member olması
- API response-da gizli field-lərin göstərilməməsi
- Activity log-da token və password saxlanmaması

---

# 23. Test strategiyası

TDD məcburi deyil.

Kritik flow-lar üçün test yazılmalıdır.

## Projects

- İstifadəçi üzv olmadığı project-i görə bilməz.
- Eyni user project-ə iki dəfə əlavə edilə bilməz.
- Yalnız manager project-i arxivləyə bilər.
- Archived project dəyişdirilə bilməz.

## Tasks

- Project member olmayan user task yarada bilməz.
- Task yalnız project member-ə assign edilə bilər.
- Status transition qaydaları işləyir.
- Archived project üçün task yaradıla bilməz.
- Unauthorized user task silə bilməz.
- Task yaradıldıqda activity log yaranır.

## API

- Token olmadıqda `401`
- Ability olmadıqda `403`
- Policy icazə vermədikdə `403`
- Validation səhvində `422`
- Create əməliyyatında `201`
- Pagination və filter işləyir

## Activity

- Status dəyişikliyi köhnə və yeni dəyərlərlə loglanır.
- Activity-də password və token yoxdur.
- Unauthorized user activity görə bilmir.

---

# 24. Development mərhələləri

## Milestone 1 — Infrastructure

- Nwidart
- Sanctum
- Livewire
- Spatie Permission
- Spatie Activitylog
- Authentication
- Shared Blade layout
- Role və permission seed-ləri
- Modul shell-ləri

## Milestone 2 — Projects

- Migration və models
- Repository pattern
- Services
- Policies
- Web CRUD
- API CRUD
- Member management
- Tests

## Milestone 3 — Tasks

- Migration və models
- Enum-lar
- Repositories
- Services
- Policies
- Web CRUD
- API CRUD
- Assignment
- Status transitions
- Filter, sort və pagination

## Milestone 4 — Comments və attachments

- Comment service/repository
- Comment web/API
- Livewire comment form
- Attachment upload
- Download authorization

## Milestone 5 — Activity

- Spatie Activitylog setup
- Model log options
- Business event log-ları
- Activity page
- Activity API

## Milestone 6 — Dashboard

- Dashboard service
- Direct Project və Task queries
- Blade dashboard
- Livewire components
- Dashboard API

## Milestone 7 — Stabilization

- Query review
- N+1 review
- Security review
- API consistency
- Focused tests
- Pint
- Manual browser checklist
- Documentation

---

# 25. Codex istifadə qaydası

Codex-ə hər dəfə bir konkret task verilməlidir.

```text
Use $nwidart-module-development.

Read:
- AGENTS.md
- docs/PROJECT_BRIEF.md
- docs/ARCHITECTURE.md
- docs/API_CONVENTIONS.md
- docs/TASKS.md

Implement TASK-023: Create TaskRepository and EloquentTaskRepository.

Requirements:
- Work only inside the Tasks module.
- Add TaskRepositoryInterface.
- Add EloquentTaskRepository.
- Support pagination, filters and eager loading.
- Do not add business logic to the repository.
- Do not change controllers.
- Do not run migrations.
- Do not access .env.
- Do not run Git commands.
- Do not use a real browser.
- Ask before adding dependencies.

At the end report:
- changed files
- checks run
- checks skipped
- remaining work
```

Junior Codex-in yazdığı kodu anlamadan qəbul etməməlidir.

Junior bunları izah edə bilməlidir:

- Bu class hansı layer-ə aiddir?
- Niyə həmin modulda yerləşir?
- Controller nə edir?
- Service nə edir?
- Repository nə edir?
- Authorization harada yoxlanılır?
- Web və API eyni service-i necə paylaşır?
- Activity log harada yaranır?

---

# 26. AGENTS.md qaydaları

```markdown
# Repository Rules

- This is a Laravel modular monolith using nwidart/laravel-modules.
- Blade and vanilla JavaScript are the default UI stack.
- Livewire is used only for approved interactive components.
- Do not introduce React, Vue, Inertia, or SPA architecture.
- Modules may directly use models and services from other modules during the first learning stage.
- Direct cross-module dependencies are an accepted temporary tradeoff.
- Do not move another module's business logic into the current module.
- Web, API, and Livewire must share the same service layer.
- Controllers must remain thin.
- Services own business rules, orchestration, and transactions.
- Repositories own Eloquent queries and persistence.
- Repositories must not contain authorization or business decisions.
- Use DTOs between HTTP and service layers.
- Use Form Requests for validation.
- Use Policies and Spatie Permission for authorization.
- Use Sanctum for API authentication.
- Token abilities do not replace Policies.
- Use API Resources for JSON responses.
- Use Spatie Activitylog for audit history.
- Never log passwords, secrets, or tokens.
- Do not access or edit .env.
- Do not run Git commands without supervisor approval.
- Do not install dependencies without approval.
- Do not run migrations or seeders without approval.
- Never run destructive database commands without approval.
- Do not use a real browser or browser automation without approval.
- Do not deploy or connect to production systems.
- Work on one task at a time.
- Do not modify files outside the assigned task.
- Report changed files, checks run, and checks skipped.
```

---

# 27. Definition of Done

Task tamamlanmış sayılır, əgər:

- Acceptance criteria yerinə yetirilib.
- Kod düzgün modul daxilindədir.
- Controller nazikdir.
- Business logic service-dədir.
- Query-lər repository-dədir.
- DTO istifadə olunub.
- Form Request əlavə edilib.
- Policy və permission yoxlaması var.
- API üçün Sanctum və ability yoxlanılıb.
- API Resource istifadə olunub.
- Lazım olan activity log əlavə edilib.
- Kritik test yazılıb.
- N+1 yoxlanılıb.
- Scope-dan kənar dəyişiklik edilməyib.
- Junior kodu izah edə bilir.
- Manual browser testi junior tərəfindən aparılıb.
- Codex-in işlətdiyi və işlətmədiyi yoxlamalar qeyd edilib.

---

# 28. Layihə bitdikdən sonra keçiriləcək mövzu: loose coupling və decoupling

Bu mövzu layihənin ilkin development mərhələsində tətbiq edilməyəcək.

Əvvəlcə layihə birbaşa module dependency-ləri ilə tamamlanacaq. Bundan sonra ayrıca refactor mərhələsində tight coupling və loose coupling fərqi öyrədiləcək.

## Mövcud tight coupling nümunəsi

```php
use Modules\Projects\Models\Project;

final class TaskService
{
    public function create(CreateTaskData $data): Task
    {
        $project = Project::findOrFail($data->projectId);

        // Task yaradılması
    }
}
```

Burada `Tasks` modulu Projects modulunun konkret Eloquent modelini tanıyır.

Bu yanaşmanın riskləri:

- Project modelinin namespace-i dəyişsə, Tasks qırılar.
- Projects modulu söndürülsə, Tasks işləməz.
- Projects ayrıca package olsa, dependency idarə etmək çətinləşər.
- Unit testdə Project modelini əvəz etmək çətin olar.
- Modul sərhədi zəifləyər.

Hazırkı layihədə bunlar qəbul edilir, çünki:

- Modullar söndürülməyəcək.
- Modullar silinməyəcək.
- Modullar ayrıca deploy edilməyəcək.
- Hamısı eyni tətbiq və database daxilindədir.

## Refactor mərhələsində nə ediləcək?

Project modeli ilə direct əlaqə contract ilə əvəz ediləcək:

```php
interface ProjectAccessInterface
{
    public function ensureCanCreateTask(
        int $projectId,
        int $userId,
    ): void;
}
```

Projects modulunda implementation:

```php
final class EloquentProjectAccess implements ProjectAccessInterface
{
    public function ensureCanCreateTask(
        int $projectId,
        int $userId,
    ): void {
        // Projects modulunun daxili query-si
    }
}
```

Tasks service:

```php
final class TaskService
{
    public function __construct(
        private ProjectAccessInterface $projects,
    ) {}

    public function create(
        User $actor,
        CreateTaskData $data,
    ): Task {
        $this->projects->ensureCanCreateTask(
            $data->projectId,
            $actor->id,
        );

        // Task yaradılması
    }
}
```

Bu mərhələdə juniorlara aşağıdakılar izah ediləcək:

- Tight coupling nədir?
- Loose coupling nədir?
- Dependency inversion nədir?
- Interface və contract nə vaxt lazımdır?
- Concrete service ilə contract arasında fərq nədir?
- DTO module boundary-də necə istifadə olunur?
- Direct model relation nə vaxt problemlidir?
- Event nə vaxt direct service call-dan daha uyğundur?
- Modul necə enable/disable edilə bilər?
- Modul ayrıca package-a necə çevrilə bilər?
- Dashboard direct query-dən metrics contract-a necə keçirilə bilər?
- Activity direct çağırışdan event listener-ə necə keçirilə bilər?

## Refactor taskları

Layihə tamamlandıqdan sonra ayrıca tasklar açılacaq:

```text
REFACTOR-001 — Tasks → Projects direct model istifadəsini tap
REFACTOR-002 — ProjectAccessInterface yarat
REFACTOR-003 — Task creation flow-u contract-a keçir
REFACTOR-004 — Dashboard direct query-lərini metrics contract-a keçir
REFACTOR-005 — Activity direct çağırışlarını event listener-lərə keçir
REFACTOR-006 — Cross-module DTO-lar yarat
REFACTOR-007 — Module dependency graph sənədləşdir
REFACTOR-008 — Bir modulu test zamanı fake implementation ilə əvəz et
REFACTOR-009 — Module disable ssenarisini araşdır
REFACTOR-010 — Əvvəlki və sonrakı arxitekturanı müqayisə et
```

Beləliklə juniorlar əvvəlcə işlək sistemi sadə və anlaşılan direct dependency-lərlə quracaq, sonra eyni kod üzərində best practice və decoupling refactor-u görəcəklər. Bu, abstract anlayışları əvvəlcədən əzbərləməkdənsə, yaranan real problemi görərək öyrənmələrinə imkan verəcək.