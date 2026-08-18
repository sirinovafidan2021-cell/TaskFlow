# TaskFlow — vahid layihəyə keçid üçün implementation plan

> Bu sənəd product roadmap deyil. Məqsəd Project A (`taskflow-main-ehmed`) və Project B (`taskflow-main-ferhad`) implementasiyalarını `TaskFlow.md` tələblərinə uyğun vahid işlək layihəyə çevirməkdir.

## 1. Qərar xülasəsi

**Base:** Project B.

**Merge prinsipi:** B-nin real domen və UI implementasiyasını saxla; A-nın Sanctum/API, repository contract və test ideyalarını port et; hər iki layihədə olmayan hissələri hədəfli şəkildə əlavə et.

İlk versiyada direct cross-module dependency qəbul edilir. Bu plan çərçivəsində `Tasks -> Projects`, `Dashboard -> Projects/Tasks` və business service-lərdən `ActivityRecorder` çağırışı contract/event arxitekturasına məcburi keçirilməyəcək. Bu refactor `roadmap.md`-də sonrakı mərhələdir.

## 2. Current State

### Project A

- Laravel/Sanctum/Livewire/Nwidart/Spatie dependency-ləri var.
- Real business module yalnız `Projects`-dir.
- Projects Web/API, token auth və bir neçə Projects test ssenarisi mövcuddur.
- Authenticated layout comment daxilində olduğuna görə module view-ları düzgün render olunmur.
- Tasks, Activity, Dashboard, approved Livewire component-ləri və API-lərin böyük hissəsi yoxdur.
- Role casing, API path casing, response status, migration registration və test expectation problemləri var.

### Project B

- Dörd tələb olunan module, Projects/Tasks Web flows, comments, attachments, activity və dashboard var.
- Enum, service, transaction və activity implementasiyası A-dan daha güclüdür.
- Funksional Blade/Tailwind app layout və rate-limited session login var.
- API və Sanctum token flow yoxdur; Livewire component-ləri yoxdur; business tests faktiki yoxdur.
- Projects və Tasks list-ləri actor-a görə scope edilmədiyi üçün ciddi authorization/data exposure problemi var.

## 3. What We Keep From Project A

| Mənbə | Qərar | Target istifadəsi |
| --- | --- | --- |
| `app/Data/CreatePersonalAccessTokenData.php` | KEEP + minor refactor | Token input DTO |
| `app/Services/AuthenticationService.php` | KEEP + refactor | Token create/revoke və ability list |
| `app/Http/Controllers/Api/V1/AuthenticationController.php` | KEEP + refactor | `/api/v1/auth/token`, `/me`, revoke |
| `app/Http/Requests/Api/V1/CreatePersonalAccessTokenRequest.php` | KEEP + strengthen | Credential/device validation |
| `app/Http/Resources/AuthenticatedUserResource.php` | KEEP | Safe current-user JSON |
| `Modules/Projects/routes/api.php` | KEEP as route inventory | B Projects module API route-u kimi yenidən yerləşdir |
| Projects API controller/requests/resources | KEEP + REFACTOR | Casing, status code, collection, `{user}` binding düzəldiləcək |
| `Repositories/Contracts` və `Repositories/Eloquent` ayrımı | KEEP as standard | B-də bütün main repositories bu struktura keçiriləcək |
| Projects feature test ssenariləri | PORT + REWRITE | Pest syntax və vahid role enum-ları ilə |
| SQLite `:memory:` phpunit config | KEEP as approach | Portable test environment |
| Role seeder çağıran `DatabaseSeeder` | KEEP as behavior | B enum əsaslı seeder ilə birləşdir |

## 4. What We Keep From Project B

| Mənbə | Qərar | Target istifadəsi |
| --- | --- | --- |
| `Modules/Projects`, `Tasks`, `Activity`, `Dashboard` | KEEP | Base module set |
| Project/Task/Member enum-ları | KEEP | DB/model/request/service boyunca vahid typed values |
| Project/Task model relation və casts | KEEP + refine | Target Eloquent models |
| Project/Task/Assignment/Status services | KEEP + REFACTOR | Business rules və transactions |
| Comment/Attachment services | KEEP + harden | Web/API/Livewire shared use cases |
| Activity recorder/query/display | KEEP + REFACTOR | Canonical audit log |
| Dashboard service/UI | KEEP + complete | Role-aware metrics + distribution/API |
| Projects/Tasks repository query logic | KEEP + secure | Search/filter/sort/eager load qorunacaq, actor scope əlavə ediləcək |
| `LoginRequest` və session controller | KEEP | Rate-limited Web login |
| Authenticated layout və business Blade-ları | KEEP + modularize | Target Web UI |
| `PermissionName`, `UserRole`, role-permission map | KEEP | Raw permission strings əvəzlənəcək |
| Pest dependencies | KEEP | Target test runner |
| Module provider-lərin `loadMigrationsFrom` davranışı | KEEP | App və tests module migration-ları görəcək |

## 5. What We Refactor

1. B repository-lərini `Contracts/*RepositoryInterface` və `Eloquent/Eloquent*Repository` strukturuna keçiririk.
2. B Projects/Tasks list query-lərinə actor visibility scope əlavə edirik; filter option-lar da eyni scope-u paylaşır.
3. Flat `Http/Controllers` və `Http/Requests` qovluqlarını `Web` və `Api/V1` olaraq ayırırıq.
4. `ProjectData`-nı `CreateProjectData` və `UpdateProjectData`; task DTO date-lərini typed immutable dates kimi standartlaşdırırıq.
5. B business exception-larını user-safe validation/domain exception-lara və API render mapping-ə keçiririk.
6. Archived project update qaydasını həm policy, həm service səviyyəsində qoruyuruq.
7. Activity payload-da changed field adları ilə kifayətlənməyib safe old/new values saxlayırıq.
8. Activity filter options üçün MySQL-specific JSON extraction-i DB-portable query-lərlə əvəz edirik.
9. App layout-u guest/app və reusable partial/component-lara ayırırıq.
10. A API portunda `APi` qovluq casing-i, create response statusları, pagination cap və nested binding düzəldilir.
11. Project-member və attachment use-case-lərində transaction/compensation sərhədləri aydınlaşdırılır.

## 6. What We Remove

- Project A-dakı commented `resources/views/layouts/app.blade.php`.
- Project A-dakı debug xarakterli `resources/views/dashboard.blade.php`.
- Project A public registration flow-u; user provisioning ayrıca requirement gələnədək session login kifayətdir.
- Project A boş `ProjectMetricsService` və olmayan modulların ghost status təsviri.
- Project A duplicated create/edit form markup-ı.
- Project B `tests/PersistenceProbeTest.php` və `storage/test-file-diagnostics/*`.
- Default example/probe tests.
- Project B `2026_08_14_110000_align_project_members_table.php`; final schema ilkin create migration-a squash ediləcək.
- Eyni məqsədli ikinci Project controller/service/repository implementasiyası.
- Raw role/status/permission magic string-ləri, enum/constant mövcud olduqda.

## 7. What We Need to Implement From Scratch

- Tasks, comments, attachments, activity və dashboard API-ləri.
- `TaskResource`, `TaskCollection`, `TaskCommentResource`, `TaskAttachmentResource`, `ActivityResource`, `DashboardSummaryResource`.
- `QuickTaskCreate`, `TaskFilters`, `TaskStatusSelector`, `TaskCommentForm` Livewire component-ləri.
- `ProjectFiltersData`, API task filter request-i və Web/API üçün validated query DTO mapping.
- Project/Task/Comment/Attachment factories.
- Project metrics və project üzrə task distribution.
- Delete confirmation, modal, preview, counters və copy behaviors üçün vanilla JS.
- Tam Pest unit/feature/API/security/activity test suite.
- Consistent Web/API domain exception handling və API rate limit-ləri.

## 8. Target Architecture

```text
app/
├── Data/
│   └── CreatePersonalAccessTokenData.php
├── Enums/
│   ├── PermissionName.php
│   └── UserRole.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthenticatedSessionController.php
│   │   └── Api/V1/AuthenticationController.php
│   ├── Requests/
│   │   ├── Auth/LoginRequest.php
│   │   └── Api/V1/CreatePersonalAccessTokenRequest.php
│   └── Resources/AuthenticatedUserResource.php
├── Models/User.php
└── Services/AuthenticationService.php

Modules/<BusinessModule>/
├── app/
│   ├── Data/
│   ├── Enums/
│   ├── Events/                 # yalnız real consumer yarandıqda
│   ├── Listeners/              # initial direct coupling üçün əvvəlcədən boş yaradılmır
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
├── database/
│   ├── factories/
│   └── migrations/
├── resources/views/
├── routes/
│   ├── web.php
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
└── module.json

resources/views/
├── layouts/
│   ├── app.blade.php
│   └── guest.blade.php
├── components/
│   ├── flash.blade.php
│   ├── form-error.blade.php
│   ├── status-badge.blade.php
│   └── confirm-button.blade.php
└── partials/
    ├── navigation.blade.php
    └── header.blade.php
```

Folder yalnız ilk real class yarananda yaradılacaq. Initial merge-də ayrıca cross-module contract package, CQRS, command bus və event sourcing əlavə edilməyəcək.

## 9. Standard request flows

```text
Web Controller ───────┐
API Controller ───────┼─→ DTO → Service → Repository Interface → Eloquent → Model
Livewire Component ───┘
```

- Authorization HTTP/Livewire entry point-də Policy ilə yoxlanır.
- Service policy-ni əvəz etmir, amma state/membership kimi business invariant-ları qoruyur.
- Repository yalnız query/persistence edir və actor scope-u authorization qərarı kimi yox, service-in verdiyi visibility kontekstinə əsasən tətbiq edir.
- API controller Eloquent model qaytarmır.

## 10. Merge Order

### Phase 0 — Base və təhlükəsiz baseline

#### Task 0.1 — Project B-ni canonical base et

**Source:** Project B

**Affected files:** bütün Project B tree-si; root dependency/config files.

**Actions:**

- Vahid iş qovluğunu B məzmunundan qur.
- A-dan faylları bütün qovluqla kor-koranə copy etmə; aşağıdakı task-larda seçilmiş class-ları port et.
- `composer.lock` üçün B-ni ilkin canonical lock seç; Activitylog v5 və Pest stack-i saxla.
- `modules_statuses.json` yalnız mövcud dörd modulu enabled göstərsin.
- Port zamanı Linux-compatible `Api`, `Web`, namespace və PSR-4 casing tətbiq et.

**Acceptance criteria:**

- Yalnız bir Laravel app baseline-i qalır.
- Dörd module provider vasitəsilə qeydiyyata alınır.
- Duplicate A/B class-ları paralel saxlanmır.
- Dependency versiyaları composer.json və lock arasında uyğundur.

#### Task 0.2 — Traceability checklist yarat

**Source:** `TaskFlow.md`

**Affected files:** `docs/TASKFLOW_COMPLIANCE.md`.

**Actions:**

- Hər Web/API/Livewire/DB/test requirement üçün status sütunları yarat: `missing`, `in_progress`, `verified`.
- Endpoint və test matrix-i bu planın task ID-lərinə bağla.
- Direct module dependencies-ni ilkin mərhələ üçün qəbul edilmiş kimi qeyd et.

**Acceptance criteria:**

- Heç bir source-of-truth endpoint və critical test untracked qalmır.
- Loose coupling item-ləri initial Definition of Done-a qarışdırılmır.

### Phase 1 — Infrastructure, schema və permission baseline

#### Task 1.1 — Module provider və route registration-ı standartlaşdır

**Source:** B provider lifecycle + A module API route ideyası

**Affected files:**

- `Modules/*/app/Providers/*ServiceProvider.php`
- lazım olduqda `Modules/*/app/Providers/RouteServiceProvider.php`
- `Modules/*/routes/web.php`
- `Modules/*/routes/api.php`
- `Modules/*/module.json`

**Actions:**

- Hər module view və migration path-ini load et.
- Web route-ları `web,auth`; API route-ları `api,auth:sanctum` və `/api/v1` prefix ilə qeydiyyata al.
- API route name prefix-i `api.v1.` olsun.
- Duplicate middleware/prefix registration yaratma.

**Acceptance criteria:**

- Static route inventory-də bütün source-of-truth endpoint-ləri üçün unikal method/path/name var.
- Web və API routes module-owned qalır.
- Linux-da namespace/path casing eynidir.

#### Task 1.2 — Migrations-ı clean baseline-a çevir

**Source:** B schemas; A project-members non-null `joined_at` və SQLite test yanaşması

**Affected files:** `database/migrations/*`, `Modules/*/database/migrations/*`.

**Actions:**

- Project member `id`, `member_role`, `joined_at` field-lərini initial create migration-a daxil et; align migration-u sil.
- Owner/user/task/project foreign key delete behavior-larını explicit et.
- Projects/tasks soft delete-lərini saxla.
- Sənəddəki bütün unique və composite index-ləri audit et.
- Activitylog v5 schema-sını package tələbinə uyğun və reversible et.
- MySQL/MariaDB/SQLite portable column tiplərinə üstünlük ver; status üçün PHP enum cast + string column saxla.

**Acceptance criteria:**

- Fresh migration bir dəfə işlədikdə tələb olunan bütün cədvəl/constraint/index-lər yaranır.
- Hər migration `down()` ilə geri alına bilir.
- SQLite test migrations və seçilmiş production DB schema davranışı üçün ayrıca verification checklist var.

#### Task 1.3 — Factories və seeders-i tamamla

**Source:** A DatabaseSeeder behavior + B role/permission enums

**Affected files:**

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/RolePermissionSeeder.php`
- `Modules/Projects/database/factories/*`
- `Modules/Tasks/database/factories/*`

**Actions:**

- `RolePermissionSeeder`-i `DatabaseSeeder`-dən çağır.
- `admin`, `project_manager`, `member` üçün enum əsaslı demo users yarat və rol ver.
- Project, membership, task, comment və attachment factory-ləri yaz.
- Plaintext credential/token activity-yə düşməsin.

**Acceptance criteria:**

- Seed olunmuş hər user gözlənən navigation/policy imkanlarına sahibdir.
- Tests raw `Model::create` blokları yerinə factory state-lərdən istifadə edə bilir.
- Role adı yalnız `UserRole` enum-dan gəlir.

### Phase 2 — Repository, DTO və exception standardı

#### Task 2.1 — Projects repositories-ni birləşdir

**Source:** A folder/contracts + B search/eager-loading + A visibility intent

**Affected files:**

- `Modules/Projects/app/Repositories/Contracts/ProjectRepositoryInterface.php`
- `Modules/Projects/app/Repositories/Contracts/ProjectMemberRepositoryInterface.php`
- `Modules/Projects/app/Repositories/Eloquent/EloquentProjectRepository.php`
- `Modules/Projects/app/Repositories/Eloquent/EloquentProjectMemberRepository.php`
- `Modules/Projects/app/Providers/ProjectsServiceProvider.php`

**Actions:**

- `paginateVisibleTo(User $actor, ProjectFiltersData $filters)` contract-ı yarat.
- Admin üçün bütün; manager üçün owned/manager membership; member üçün membership scope tətbiq et.
- Search/status/pagination/eager-load məntiqini repository-də saxla.
- Member list/create/delete/exists/available-user query-lərini member repository-yə köçür.
- `Admin` magic string bug-ını enum ilə aradan qaldır.

**Acceptance criteria:**

- Member list response-da unrelated project yoxdur.
- Admin bütün project-ləri görür.
- Query-lər controller/service daxilində təkrarlanmır.
- Provider hər interface-i yalnız bir implementation-a bind edir.

#### Task 2.2 — Tasks repositories-ni standartlaşdır və scope et

**Source:** B Task repositories

**Affected files:** `Modules/Tasks/app/Repositories/{Contracts,Eloquent}/*`.

**Actions:**

- Task/comment/attachment interface adlarını `*RepositoryInterface` et.
- `paginate(TaskFiltersData $filters, User $actor)` visibility qaydasını tətbiq et.
- Admin: all; project manager: idarə etdiyi project-lər; member: özünə assign edilmiş tasks.
- Project və assignee filter options yalnız actor-un görə bildiyi scope-dan gəlsin.
- Sort whitelist və `project,assignee,creator` eager loading saxlanılsın.

**Acceptance criteria:**

- Task list və filter option-lar heç bir unrelated ID/name expose etmir.
- `sort` request-i raw `orderBy` column-a keçmir.
- Pagination query string saxlanır və per-page cap service/request tərəfindən təmin olunur.

#### Task 2.3 — Purposeful DTO və validated filter mapping

**Source:** B DTO-ları + TaskFlow typed DTO tələbi

**Affected files:** `Modules/Projects/app/Data/*`, `Modules/Tasks/app/Data/*`, Web/API Request-lər.

**Actions:**

- `CreateProjectData`, `UpdateProjectData`, `ProjectFiltersData` ayır.
- `CreateTaskData`, `UpdateTaskData`, `AssignTaskData`, `ChangeTaskStatusData`, `TaskFiltersData` saxla və date-ləri `CarbonImmutable`/nullable typed value et.
- API `search` və signed `sort=-due_at`; Web UI üçün uyğun URL state map et.
- DTO yalnız validated data-dan yaransın; `request()->all()` istifadəsini sil.

**Acceptance criteria:**

- Web/API/Livewire eyni service metodlarına eyni DTO tipləri verir.
- Unknown filter/sort validation-da rədd edilir və ya təhlükəsiz default-a çevrilir.
- `per_page` 1–100 aralığındadır.

#### Task 2.4 — Domain exception contract-ı yarat

**Source:** B service rules

**Affected files:** `Modules/*/app/Exceptions/*`, `bootstrap/app.php`.

**Actions:**

- Duplicate member, archived/inactive project, invalid transition və invalid assignee üçün məqsədli exception/validation errors seç.
- API üçün `422` field errors və ya documented `409`; Web üçün session errors qaytar.
- Authorization problemlərini `403` saxla; business conflict-i `500` etmə.

**Acceptance criteria:**

- Gözlənilən domain violation-ların heç biri 500 vermir.
- Web və API eyni service exception-ından uyğun presentation response yaradır.

### Phase 3 — Authentication, Sanctum və authorization

#### Task 3.1 — Web auth-u B implementation-ı ilə sabitlə

**Source:** Project B

**Affected files:** session auth controller/request/routes/login view.

**Actions:**

- `LoginRequest` throttle, remember və session regeneration saxla.
- Logout-da session invalidate + CSRF token regenerate saxla.
- Public registration route/controller/view-u target baseline-a port etmə.
- Login view-u `layouts.guest`-ə keçir.

**Acceptance criteria:**

- Guest protected route-dan login-ə yönəlir.
- Uğurlu login session ID-ni regenerate edir.
- Beş failed attempt-dən sonra throttle behavior testlə sübut olunur.
- Logout authenticated session-u etibarsız edir.

#### Task 3.2 — Sanctum token API-ni A-dan port et

**Source:** Project A

**Affected files:**

- `app/Data/CreatePersonalAccessTokenData.php`
- `app/Services/AuthenticationService.php`
- `app/Http/Controllers/Api/V1/AuthenticationController.php`
- `app/Http/Requests/Api/V1/CreatePersonalAccessTokenRequest.php`
- `app/Http/Resources/AuthenticatedUserResource.php`
- `routes/api.php`

**Actions:**

- Token create, `me` və current-token revoke endpoint-lərini əlavə et.
- Ability list-i source-of-truth-dakı yeddi ability ilə vahidləşdir.
- Token endpoint-ə rate limit tətbiq et.
- Plaintext token-i yalnız create response-da göstər; log/DB/API resource-a salma.

**Acceptance criteria:**

- Valid credentials `201` və `{data: {token, abilities}}` verir.
- Invalid credentials `422`; unauthenticated `me/revoke` `401` verir.
- Revoke-dan sonra eyni token protected endpoint-ə daxil ola bilmir.
- Activity/log/assertions plaintext token-i saxlamır.

#### Task 3.3 — Permission + Policy matrix-i düzəlt

**Source:** B enums/policies + TaskFlow role matrix + A archived rule

**Affected files:** role/permission enums/seeder və bütün policies.

**Actions:**

- Broad permissions seeder map-ini role matrix ilə yoxla.
- Project `viewAny/view/create/update/archive/manageMembers` və Task actions üçün explicit methods saxla.
- Archived project update-i policy-də blokla; service invariant əlavə et.
- Token ability heç vaxt policy-ni bypass etməsin.
- Nested user/comment/attachment binding-i parent relation ilə scope et.

**Acceptance criteria:**

- Admin/manager/member matrix üçün data-driven policy tests keçir.
- Ability var, policy yoxdursa API `403` verir.
- Member unrelated project/task/activity metadata-sı görmür.

### Phase 4 — Projects feature-ni tamamla

#### Task 4.1 — Project services-i B üzərində standartlaşdır

**Source:** B `ProjectService`, `ProjectMemberService`; A repository boundaries

**Affected files:** `Modules/Projects/app/Services/*`.

**Actions:**

- Create: draft project + unique slug + owner manager membership + activity bir transaction-da.
- Update: archived guard, safe changed values, activity old/new.
- Archive: idempotent state behavior və canonical activity.
- Member add/remove: repository, duplicate/owner guard, transaction və activity.
- `ProjectMetricsService` yalnız real project task counts/distribution query-ləri ilə yaradılır.

**Acceptance criteria:**

- Partial failure project/member/activity məlumatını yarımçıq qoymur.
- Owner membership-dən silinə bilmir.
- Duplicate membership user-safe validation error verir.
- Archived project Web/API/Livewire vasitəsilə update edilmir.

#### Task 4.2 — Projects Web controller və views-i B UI ilə tamamla

**Source:** B UI

**Affected files:** `Modules/Projects/app/Http/Controllers/Web/*`, `Requests/Web/*`, `resources/views/*`, `routes/web.php`.

**Actions:**

- Controller-ləri `Web` namespace-ə köçür və list üçün service çağır.
- Project create/edit reusable form-u saxla.
- Detail səhifəsində member və latest/project tasks məlumatını göstər.
- Policy-aware action button-ları, empty/error/flash states əlavə et.
- Member input-u raw ID əvəzinə authorized available-user select saxla.

**Acceptance criteria:**

- Bütün Web actions Form Request + Policy + DTO + Service axınından keçir.
- Project detail authorized task subset-i göstərir.
- Unauthorized action link-i görünmür və direct request yenə `403` verir.

#### Task 4.3 — Projects API-ni A-dan tamamlayaraq port et

**Source:** A Projects API + B services/policies

**Affected files:** `Modules/Projects/app/Http/{Controllers/Api/V1,Requests/Api/V1,Resources}/*`, `routes/api.php`.

**Actions:**

- Source-of-truth-dakı 8 Projects/member endpoint-i exact method/path ilə əlavə et.
- `{project}/members/{user}` semantics və scoped binding istifadə et.
- Read/write abilities + policy çağır.
- `ProjectResource`, `ProjectCollection`, `ProjectMemberResource` ilə response formatını standartlaşdır.

**Acceptance criteria:**

- Create/member create `201`; delete/member delete `204` və body-sizdir.
- Lists required `data/meta` formatını verir.
- Token yoxdursa `401`; ability/policy yoxdursa `403`; validation `422`.
- Web və API eyni service metodlarını çağırır.

### Phase 5 — Tasks, comments və attachments hardening

#### Task 5.1 — Task core services və visibility-ni tamamla

**Source:** B Tasks implementation

**Affected files:** Task services, policies, repositories və DTO-lar.

**Actions:**

- B status transition map və reopen rule-u saxla.
- Task create üçün active project, actor management və assignee membership invariant-larını saxla.
- Task number-i transaction daxilində `TSK-%06d` formatında finalize et; committed null number qalmasın.
- Update/delete/assign/status activity old/new payload-larını standartlaşdır.
- `TaskMetricsService` yalnız dashboard/project metrics üçün real metodlarla əlavə et.

**Acceptance criteria:**

- Bütün documented transitions üçün allow/deny unit table testi var.
- Non-member create, foreign assignee və archived project create rədd edilir.
- Concurrent/rollback ssenarisində duplicate və ya committed null task number olmur.

#### Task 5.2 — Tasks Web axınını service sərhədləri ilə saxla

**Source:** B Tasks Web/UI

**Affected files:** `Controllers/Web`, `Requests/Web`, views və `routes/web.php`.

**Actions:**

- CRUD/assign/delete traditional controller flow olaraq saxla.
- List-i `TaskFilters` Livewire task-ı tamamlanana qədər secure service query ilə işlət.
- Show page eager-loaded relation-ları istifadə et; Blade-də query işlətmə.
- Edit create form-u reuse et və error messages-i bütün field-lər üçün göstər.

**Acceptance criteria:**

- Member yalnız assigned tasks görür.
- Manager yalnız idarə etdiyi project task-larını görür.
- N+1 query review-də list/show üçün gözlənilməz relation query-si yoxdur.

#### Task 5.3 — Comments və attachments-i təhlükəsizləşdir

**Source:** B services/controllers

**Affected files:** comment/attachment repositories, services, requests, controllers və policies.

**Actions:**

- Nested binding child ownership-u enforce et.
- Comment create/delete transaction + canonical activity saxla.
- MIME/size whitelist və authorized private download saxla.
- Attachment delete-də DB və storage inconsistency-ni compensation və ya after-commit cleanup ilə həll et.
- Storage path/original filename-i response-da təhlükəsiz shape et.

**Acceptance criteria:**

- Başqa task-a aid comment/attachment nested route-da `404` verir.
- Unauthorized download `403`; path bilmək download imkanı vermir.
- Invalid MIME/oversize `422` verir.
- DB failure yeni upload orphan file saxlamır; delete failure metadata/file consistency-ni qoruyur.

### Phase 6 — Tasks, Activity və Dashboard API

#### Task 6.1 — Tasks CRUD/status/assignee API-ni yaz

**Source:** B services; A API presentation pattern

**Affected files:** Task `Controllers/Api/V1`, `Requests/Api/V1`, `Resources`, `routes/api.php`.

**Actions:**

- Beş Task CRUD, status və assignee endpoint-lərini əlavə et.
- Query request `search,status,priority,project_id,assignee_id,due_before,sort,page,per_page` validate et.
- `sort=-due_at` parse və whitelist et.
- `TaskResource`/`TaskCollection` istifadə et.

**Acceptance criteria:**

- Create `201`, delete `204`, validation `422`.
- `per_page > 100` qəbul edilmir.
- Collection exact `data` və required `meta` fields verir.
- Ability + policy ikisi də hər endpoint-də test olunur.

#### Task 6.2 — Comments və attachments API-ni yaz

**Source:** B shared services

**Affected files:** Task API controllers/requests/resources/routes.

**Actions:**

- Documented GET/POST/DELETE collection endpoints-lərini əlavə et.
- Comments write üçün `comments:write`; read üçün `tasks:read`; attachment mutation üçün `tasks:write` istifadə et.
- Private attachment download üçün ayrıca authorized route varsa bunu API docs-da qeyd et.

**Acceptance criteria:**

- Eyni service-lər Web, API və comment Livewire tərəfindən paylaşılır.
- Nested ownership və policy bypass edilmir.
- Resource private `disk/path` məlumatını səbəbsiz expose etmir.

#### Task 6.3 — Activity API-ni yaz

**Source:** B Activity module

**Affected files:** Activity API controller/request/resource/routes və query service/repository.

**Actions:**

- Global, task-scoped və project-scoped activity endpoint-lərini əlavə et.
- Event/project/task/actor/date filters validate et.
- Role-aware scope-u Web və API arasında paylaş.
- MySQL-specific JSON select-ləri portable implementation-la əvəz et.

**Acceptance criteria:**

- Member unrelated activity-ni filter ID manipulyasiyası ilə görə bilmir.
- Status change resource old/new values verir.
- Password/token/secret heç bir resource və properties-də yoxdur.

#### Task 6.4 — Dashboard API-ni yaz

**Source:** B Dashboard service

**Affected files:** Dashboard API controller/resource/routes və service.

**Actions:**

- `summary`, `my-tasks`, `overdue` endpoint-lərini əlavə et.
- `dashboard:read` ability + dashboard permission tətbiq et.
- Project üzrə task distribution-u service və summary resource-a əlavə et.

**Acceptance criteria:**

- Web və API eyni metrics service result-larını istifadə edir.
- Role scope bütün counts/list-lərdə eynidir.
- Summary documented metrics və distribution verir.

### Phase 7 — Layout, reusable UI, Livewire və JavaScript

#### Task 7.1 — Layout sistemini ayır

**Source:** B app/login UI

**Affected files:** `resources/views/layouts/*`, `components/*`, `partials/*`, auth/business views.

**Actions:**

- `guest.blade.php` və authenticated `app.blade.php` yarat.
- Sidebar navigation, header/user menu, flash/error summary partial-larını ayır.
- Reusable form error/status badge/confirmation button component-ləri yarat.
- `@vite` və Livewire assets lifecycle-i hər layout-da düzgün saxla.

**Acceptance criteria:**

- Login guest layout istifadə edir; business pages yalnız app layout istifadə edir.
- Navigation permission-aware və active route-aware-dir.
- Project/Task create/edit markup təkrarı minimumdur.
- Mobile və desktop strukturunda content/sidebar overflow problemi yoxdur (manual browser checklist).

#### Task 7.2 — `TaskFilters` Livewire component-i

**Source:** B Task list/filter query + TaskFlow approved use-case

**Affected files:** `Modules/Tasks/app/Livewire/TaskFilters.php`, component view, task index view.

**Actions:**

- Search/status/priority/project/assignee/due filters, sort və pagination URL state saxla.
- Actor-u authorize et və service çağır; repository-ni birbaşa inject etmə.
- Filter dəyişəndə page reset et; debounce yalnız search üçün.
- Loading, empty və validation states göstər.

**Acceptance criteria:**

- Full page refresh olmadan filters/pagination işləyir.
- URL paylaşanda eyni filter state bərpa olunur.
- Unrelated project/user option görünmür.
- Component test service result/scope və authorization-u sübut edir.

#### Task 7.3 — `TaskStatusSelector` Livewire component-i

**Source:** B `TaskStatusService`

**Affected files:** component class/view və task show.

**Actions:**

- `changeStatus` policy-ni component action-da yoxla.
- Available statuses-i service-dən al və change üçün `ChangeTaskStatusData` ver.
- Loading/disabled/error/success state göstər; status badge-i yenilə.

**Acceptance criteria:**

- Business transition component-də təkrarlanmır.
- Invalid/tampered status backend service tərəfindən rədd edilir.
- Manager-only reopen component testlə yoxlanır.

#### Task 7.4 — `TaskCommentForm` Livewire component-i

**Source:** B comment service

**Affected files:** component class/view və task show.

**Actions:**

- Body validation, `comment` policy və service call tətbiq et.
- Success-dən sonra input/error state reset et və comment list-i refresh et.
- Double submit-i loading state ilə blokla.

**Acceptance criteria:**

- Comment refresh-siz görünür.
- Unauthorized user action edə bilmir.
- Activity event bir dəfə yaranır.

#### Task 7.5 — `QuickTaskCreate` Livewire component-i

**Source:** TaskFlow approved use-case + B task create service

**Affected files:** component class/view, Dashboard və Project detail views.

**Actions:**

- Dashboard-da authorized project seçimi, project detail-də fixed project context istifadə et.
- Project dəyişdikdə assignee option-larını Projects service vasitəsilə yenilə.
- `create` policy, DTO və `TaskService` çağır.
- Modal/open-close state və loading/success/error feedback əlavə et.

**Acceptance criteria:**

- Archived/inactive project və foreign assignee tampered request ilə də rədd edilir.
- Task yaradılması normal create controller ilə eyni service/activity davranışını verir.
- Component repository və Eloquent query-ni birbaşa çağırmır.

#### Task 7.6 — Məqsədli vanilla JavaScript əlavə et

**Source:** TaskFlow JS use-case-ləri

**Affected files:** `resources/js/app.js`, Blade data attributes/components.

**Actions:**

- Delete confirmations, attachment preview, character counter, task number/API token copy əlavə et.
- API token plaintext DOM-dan success ekranı bağlandıqdan sonra saxlanmasın.
- Business validation/status rules JS-ə köçürülməsin.

**Acceptance criteria:**

- JS disabled olduqda core forms backend validation ilə işləyir.
- Copy/preview/confirmation behavior keyboard-accessible-dir.
- Secret token console/log/local storage-a yazılmır.

### Phase 8 — Activity və Dashboard completeness

#### Task 8.1 — Audit payload və Web activity routes-u tamamla

**Source:** B Activity implementation

**Affected files:** Activity recorder/query/display/controller/routes/views.

**Actions:**

- Canonical event list-i enum/constant ilə vahidləşdir.
- Project/task updates və status/assignment üçün safe old/new values saxla.
- `/activity` ilə yanaşı task-scoped activity Web route-u əlavə et; project detail embedded history-ni saxla.
- Event/project/task/actor/date filter validation əlavə et.

**Acceptance criteria:**

- Hər tələb olunan business event üçün actor, subject, project/task context və time var.
- Old/new audit testləri keçir.
- Sensitive keys explicit denylist ilə filter olunur.

#### Task 8.2 — Dashboard-u tamamla

**Source:** B Dashboard service/UI

**Affected files:** Dashboard service/controller/view.

**Actions:**

- Project task distribution əlavə et.
- Counts və list-lərdə eyni actor visibility scope-u reuse et.
- Overdue comparison və timezone/date semantics-i testlə sənədləşdir.
- QuickTaskCreate-ni dashboard-a yerləşdir.

**Acceptance criteria:**

- Source-of-truth-dakı bütün 11 metric/UI bölməsi mövcuddur.
- Member/manager/admin üçün saylar ayrıca feature testlə təsdiqlənir.

### Phase 9 — Pest tests və stabilization

#### Task 9.1 — Test infrastructure-ı düzəlt

**Source:** B Pest + A SQLite config

**Affected files:** `composer.json/lock` (dependency dəyişmədən), `phpunit.xml`, `tests/Pest.php`, module test folders.

**Actions:**

- SQLite `:memory:`, test `APP_KEY`, array cache/session/mail və fake storage baseline qur.
- Module test directories-i Pest discovery-yə daxil et.
- Probe/example tests və non-existent suites-i sil.

**Acceptance criteria:**

- Clean checkout-da ayrıca MySQL server olmadan test suite başlaya bilir.
- Bütün module migrations tests tərəfindən discover olunur.
- Zero-test success mümkün deyil; suite business tests tapır.

#### Task 9.2 — Projects və Tasks critical tests

**Source:** TaskFlow test strategy + rewritten A scenarios

**Affected files:** `Modules/Projects/tests/*`, `Modules/Tasks/tests/*`.

**Actions:**

- Projects: non-member visibility, duplicate member, manager archive, archived update.
- Tasks: non-member create, assignee membership, transition table, archived project, unauthorized delete, creation activity.
- Repository visibility scope-u Web və service səviyyəsində test et.

**Acceptance criteria:**

- Hər source-of-truth critical bullet üçün ən azı bir explicit test var.
- Role adları yalnız enum/factory states-dən gəlir.

#### Task 9.3 — API/Auth/Activity tests

**Source:** TaskFlow API/activity test strategy

**Affected files:** host və module Feature tests.

**Actions:**

- Hər endpoint family üçün 401/ability 403/policy 403/422/201/204 testləri.
- Task filters, signed sort, pagination meta və per-page cap.
- Current token revoke və plaintext token non-persistence.
- Activity old/new, sensitive data absence və unauthorized scope.

**Acceptance criteria:**

- Endpoint matrix-də boş auth/status/format hüceyrəsi qalmır.
- Web və API eyni business rule üçün eyni nəticəni verir.

#### Task 9.4 — Quality və security pass

**Source:** TaskFlow Milestone 7 və Definition of Done

**Affected files:** bütün dəyişən source files və docs.

**Actions:**

- Pint, full Pest, frontend build və static route review.
- N+1/query count review, mass assignment, CSRF/XSS, rate limiting və file security audit.
- API response consistency və OpenAPI/Postman checklist.
- Junior manual browser checklist: auth, nav, CRUD, modal/loading/error/mobile, unauthorized URLs.

**Acceptance criteria:**

- Automated checks yaşıl və skipped checks səbəbi ilə report edilir.
- Critical/High issue register bağlanıb.
- Compliance checklist `verified` vəziyyətindədir.
- Junior hər layer və shared Web/API/Livewire flow-u izah edə bilir.

## 11. Endpoint acceptance matrix

| Family | Auth | Ability | Policy | Request/DTO | Resource | Success |
| --- | --- | --- | --- | --- | --- | --- |
| Auth token | credentials | n/a | own token | token DTO | JSON/user resource | `201/200/204` |
| Projects read | Sanctum | `projects:read` | view/viewAny | filters | Project Resource/Collection | `200` |
| Projects write | Sanctum | `projects:write` | create/update/archive/delete/manage | create/update/member DTO | Project/Member Resource | `201/200/204` |
| Tasks read | Sanctum | `tasks:read` | view/viewAny | TaskFiltersData | Task Resource/Collection | `200` |
| Tasks write | Sanctum | `tasks:write` | create/update/delete/assign/status | purposeful DTO | Task Resource | `201/200/204` |
| Comments write | Sanctum | `comments:write` | comment/deleteComment | comment input | Comment Resource | `201/204` |
| Attachments | Sanctum | `tasks:read/write` | view/upload/delete | file request | Attachment Resource | `200/201/204` |
| Activity | Sanctum | `activity:read` | viewAny + scoped query | filters | Activity Resource/Collection | `200` |
| Dashboard | Sanctum | `dashboard:read` | dashboard permission | filters if any | Summary Resource | `200` |

## 12. Minimum Definition of Done for merged project

- Dörd module və source-of-truth-dakı Web/API surface mövcuddur.
- List/detail/actions actor scope və policy ilə qorunur.
- Web/API/Livewire service business logic-ni paylaşır.
- Repository interfaces və Eloquent implementations vahid strukturdadır.
- DTO/Form Request/Resource/Policy/ability qaydaları tətbiq edilib.
- Dörd approved Livewire component real interaktiv behavior verir.
- Activity old/new context saxlayır, secrets saxlamır.
- Pest critical suite və security/status/format matrix-i keçir.
- Pint/build/N+1/security/manual-browser checks report edilir.
- Initial direct cross-module dependencies sənədləşdirilib və bilərəkdən saxlanılıb.
