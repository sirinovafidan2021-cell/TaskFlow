# TaskFlow — müqayisəli layihə analizi

## 1. Scope və identifikasiya

Bu report üçün aşağıdakı adlandırma istifadə olunur:

- **Project A:** `taskflow-main-ehmed`
- **Project B:** `taskflow-main-ferhad`
- **Source of truth:** `C:\Users\ziya\Desktop\TaskFlow.md`

Analiz yalnız fayl adlarına əsaslanmır. Composer lock-ları, bootstrap və provider-lər, route-lar, controller-lər, Form Request-lər, DTO-lar, service/repository qatları, policy-lər, model relation/cast-ları, migration-lar, Blade view-lar, Livewire istifadəsi, Sanctum axını, activity/dashboard məntiqi və testlər oxunub.

## 2. Texniki inventar

| Sahə | Project A | Project B |
| --- | --- | --- |
| PHP tələbi | `^8.3` | `^8.3` |
| Laravel lock | `v13.25.0` | `v13.25.0` |
| Nwidart Modules | `v13.0.0` | `v13.0.0` |
| Sanctum | `v4.3.3` | `v4.3.3` |
| Livewire | `v4.4.0`; component yoxdur | `v4.4.0`; component yoxdur |
| Spatie Permission | `8.3.0` | `8.3.0` |
| Spatie Activitylog | `4.12.3` | `5.0.0` |
| Test runner | PHPUnit `12.5.x` | Pest `5.1.0` + Pest Laravel `5.0.1` |
| Frontend | Blade, Tailwind 4, vanilla JS, Vite 8 | Blade, Tailwind 4, vanilla JS, Vite 8 |
| Real modullar | Yalnız `Projects` | `Projects`, `Tasks`, `Activity`, `Dashboard` |
| Web auth | Login + public registration | Login, remember-me və rate limit |
| API auth | Token yaratma, `me`, current-token revoke | Yoxdur |
| Business API | Projects və project members | Yoxdur |
| Test məzmunu | 6 Projects feature testi + iki example | Faktiki business testi yoxdur |

Hər iki layihədə `vendor/` və `node_modules/` yoxdur, PHP CLI isə PATH-də mövcud deyil. Buna görə runtime route discovery, PHP lint, Laravel test suite və frontend build icra edilməyib; runtime nəticəsi kimi göstərilən heç bir iddia yoxdur. Aşağıdakı fail proqnozları statik kod faktları ilə əsaslandırılıb.

## 3. `TaskFlow.md` tələblərinin strukturlaşdırılması

### 3.1 Arxitektura

- Laravel 13, PHP 8.3/8.4 və Nwidart modular monolith.
- `Projects`, `Tasks`, `Activity`, `Dashboard` business modulları.
- Host app-də authentication və `User`.
- İlk mərhələdə direct cross-module model/service istifadəsinə icazə.
- HTTP/Livewire → validation → policy → DTO → service → repository → model axını.
- Controller nazik, business qaydaları və transaction service-də, query/persistence repository-də.
- Hər əsas aggregate üçün interface + Eloquent implementation.
- Web, API və Livewire eyni service qatını istifadə etməlidir.

### 3.2 Domen və database

- Projects CRUD/archive/members/metrics və project daxilində task görünüşü.
- Tasks CRUD, assignment, status machine, filter/sort/pagination.
- Comments, attachments, audit activity və dashboard.
- Project və task soft delete.
- Sənəddə göstərilən foreign key-lər, unique constraint-lər və composite index-lər.
- Project/task status və task priority üçün PHP enum-lar.
- Task number formatı: `TSK-000001`.

### 3.3 Web/frontend

- Blade + Tailwind + vanilla JS + Vite.
- Shared authenticated layout və uyğun guest layout.
- Reusable form/UI hissələri, authorization-aware UI, flash/error states.
- Məqsədli vanilla JS: confirmation, modal, preview, counters, copy və dependent fields.
- Bütün tətbiq Livewire deyil; yalnız `QuickTaskCreate`, `TaskFilters`, `TaskStatusSelector`, `TaskCommentForm`.

### 3.4 API və Sanctum

- `/api/v1` versiyalama.
- Personal access token yaratma, `me`, current token revoke.
- Projects, members, tasks, status, assignee, comments, attachments, activity və dashboard endpoint-ləri.
- Sanctum authentication + token ability + Spatie permission + record policy birlikdə.
- Form Request, API Resource/Collection, düzgün `201/204/401/403/422` cavabları.
- Task API-də sənəddəki filter-lər, `per_page <= 100`, eager loading və sort whitelist.

### 3.5 Activity, security və tests

- Canonical event adları və actor/subject/old/new/project/task/time məlumatı.
- Password/token/secret loglanmamalıdır.
- Policy, membership, assignee membership, CSRF, XSS, rate limit, mass assignment və attachment download yoxlamaları.
- Pest ilə sənəddə sadalanan critical feature/API/activity ssenariləri.

## 4. Compliance matrisi

| Requirement | Project A | Project B | Nəticə |
| --- | --- | --- | --- |
| Dörd business modulu | **Yox** — yalnız Projects qovluğu var | **Var** | B saxlanır |
| Repository interface + Eloquent | Projects üçün düzgün qovluq ayrımı var | Interface-lər var, amma qovluq/naming vahid deyil; member service direct Eloquent edir | A strukturu + B query-ləri |
| Service-də real business logic | Zəif; əsasən array mapping/wrapper | Güclü; transactions, status, assignment, activity var | B saxla/refactor et |
| Purposeful DTO | Yalnız bir `ProjectData` və token DTO | Project + Tasks DTO-ları var | B əsas, create/update DTO-ları ayır |
| Enum-lar | Projects status raw string | Project/task/member enum-ları var | B saxlanır |
| Nazik controller | Orta; DTO qurulması təkrarlanır | Orta; bir neçə list query repository-yə birbaşa gedir, sıx one-line metodlar var | Hər ikisi refactor |
| Policy + permission | Var, amma role casing və admin davranışı qüsurludur | Daha ardıcıl enum/permission/policy kombinasiyası | B əsas, scope fix |
| Archived project qaydası | Policy update-i bloklayır | Update policy/service bloklamır | A qaydasını B-yə daşı |
| Projects Web | Var, amma base layout boşdur | Funksional və daha reusable UI | B saxlanır |
| Tasks Web | Yox | CRUD/status/assignment/comment/attachment var | B saxlanır |
| Activity Web | Yox | Var | B saxlanır |
| Dashboard Web | Sadə host debug səhifəsi | Role-aware metrics UI var | B saxlanır və tamamlanır |
| Auth Web | Login/register, rate limit yoxdur | Login/remember/rate limit | B saxlanır; public register daşınmır |
| Sanctum token API | Var | Yox | A-dan götür |
| Projects API | Var | Yox | A-dan götür və düzəlt |
| Digər API-lər | Yox | Yox | Sıfırdan yaz |
| API Resources | Project/member/user var | Yox | A əsas + yeni resources |
| Livewire | Heç bir real component yoxdur | Heç bir real component yoxdur | Sıfırdan, yalnız 4 approved component |
| Vanilla JS use-case-ləri | `app.js` boşdur | `app.js` boşdur | Sıfırdan, UI behavior üçün |
| Activity business log | Yox | Var | B saxlanır/refactor |
| Events/listeners | Real event/listener yoxdur | Real event/listener yoxdur; Activity direct service call-dır | Initial tight coupling üçün blocker deyil; real consumer yarandıqda roadmap Phase 3-də tətbiq edilir |
| Database completeness | Projects cədvəlləri | Bütün əsas cədvəllər | B əsas |
| Factories | Yalnız User | Yalnız User | Module factory-ləri sıfırdan |
| Pest tests | Yox | Paket var, business test yoxdur | B runner + A ssenariləri |

## 5. Project A — yaxşı tərəflər

1. **Real Sanctum personal-token axını var.**
   - `app/Http/Controllers/Api/V1/AuthenticationController.php`
   - `app/Services/AuthenticationService.php`
   - `app/Data/CreatePersonalAccessTokenData.php`
   - `app/Http/Resources/AuthenticatedUserResource.php`
   - Plaintext token yalnız create response-da qaytarılır; current token revoke edilir.

2. **Projects API real olaraq route/controller/request/resource qatlarına bölünüb.**
   - `Modules/Projects/routes/api.php`
   - `Modules/Projects/app/Http/Controllers/APi/V1/ProjectController.php`
   - `Modules/Projects/app/Http/Resources/ProjectResource.php`
   - `Modules/Projects/app/Http/Resources/ProjectMemberResource.php`
   - Read/write token ability middleware-ləri ayrılıb və controller policy çağırır.

3. **Repository contract strukturu source of truth-a daha yaxındır.**
   - `Modules/Projects/app/Repositories/Contracts/ProjectRepositoryInterface.php`
   - `Modules/Projects/app/Repositories/Eloquent/EloquentProjectRepository.php`
   - Binding `ProjectsServiceProvider` daxilindədir.

4. **Projects üçün kritik ssenarilərə yönəlmiş test niyyəti var.**
   - Non-member access, archive, member duplication, list scope və API list scope üçün ayrıca test class-ları yaradılıb.
   - `phpunit.xml` SQLite `:memory:` və test `APP_KEY` təyin edir; bu, portable test bazası üçün yaxşı başlanğıcdır.

5. **Seeder ilkin rolları və demonstrasiya istifadəçilərini yaradır.**
   - `database/seeders/DatabaseSeeder.php` `RolePermissionSeeder`-i çağırır.

6. **Nested member deletion-də parent-child uyğunluğu yoxlanır.**
   - Web və API controller-də `projectMember->project_id !== project->id` üçün 404 verilir.

## 6. Project A — əsas çatışmazlıqlar

1. `Modules/` daxilində yalnız `Projects` mövcuddur; `modules_statuses.json` isə olmayan `Tasks`, `Activity`, `Dashboard` modullarını enabled göstərir.
2. `resources/views/layouts/app.blade.php` bütövlükdə Blade comment daxilindədir. `@extends('layouts.app')` istifadə edən Projects səhifələri content render etmir və Vite asset-ləri yüklənmir.
3. Projects-dən başqa Web/API/domain funksionallığı yoxdur.
4. Heç bir Livewire component və real vanilla JS behavior yoxdur.
5. `EloquentProjectRepository` admin rolunu `Admin` kimi yoxlayır, seeder isə `admin` yaradır. Admin list scope-u səhv işləyəcək.
6. Project testləri `Project manager` rolunu yaradır, policy isə `project_manager` axtarır. Bu testlərin manager-positive branch-i statik olaraq uyğun gəlmir.
7. Root example feature test `/` üçün `200` gözləyir, route unauthenticated user-i login-ə redirect edir.
8. `ProjectsServiceProvider` module migrations-ı `loadMigrationsFrom` ilə qeydiyyatdan keçirmir. Standard Laravel `RefreshDatabase` axınında Projects cədvəllərinin tapılmasına zəmanət yoxdur.
9. API qovluqları `APi` yazılıb, namespace isə `Api`-dir. Windows-da gizlənən bu fərq Linux case-sensitive deployment-də PSR-4 autoload-u qıra bilər.
10. Create/member API cavabları `201` deyil; project list `per_page` üçün validation/cap yoxdur.
11. Project/member validation-da max length, date ordering və `member_role` enum/whitelist yoxdur.
12. Project create/update/archive/member əməliyyatlarında transaction və activity log yoxdur.
13. `ProjectMetricsService` boş class-dır; istifadəsiz scaffold saxlanılıb.
14. Create/edit Blade form-ları böyük həcmdə təkrarlanır və UI authorization bəzi düymələrdə göstərilmir.
15. Web login üçün throttle yoxdur; public registration requirement-də açıq tələb edilmədiyi halda əlavə attack surface yaradır.

## 7. Project B — yaxşı tərəflər

1. **Domen əhatəsi ən genişdir.**
   - `Modules/Projects`, `Modules/Tasks`, `Modules/Activity`, `Modules/Dashboard` real provider, model, service, route və view-lara sahibdir.

2. **Service layer real business qaydaları daşıyır.**
   - `ProjectService`: create/update/archive/activate, unique slug, transaction, owner membership, activity.
   - `TaskService`: active-project və membership qaydaları, transaction, task number, activity.
   - `TaskAssignmentService`: assignee project membership yoxlaması.
   - `TaskStatusService`: status transition map, manager reopen, timestamps və activity.
   - `TaskAttachmentService`: fayl saxlanarkən DB xətası baş verərsə yeni faylı təmizləyir.

3. **Enum-lar və model casts yaxşı tətbiq edilib.**
   - `ProjectStatus`, `ProjectMemberRole`, `TaskStatus`, `TaskPriority`.
   - `Project`, `ProjectMember`, `Task` bu enum-ları cast edir.

4. **Projects create atomik use-case-dir.**
   - Project save, owner-in manager membership-i və activity log bir transaction daxilindədir.

5. **Task query-si filter/sort/eager-loading baxımından yaxşı bazadır.**
   - `EloquentTaskRepository` search, status, priority, project, assignee, due date, whitelist sort və eager loading tətbiq edir.

6. **Authorization modeli A-dan daha ardıcıldır.**
   - `PermissionName` və `UserRole` enum-ları.
   - Broad permission + record policy birlikdə istifadə olunur.
   - Nested comment/attachment route-larında child-in task-a aid olması yoxlanır.

7. **Web UI daha işlək və reusable-dır.**
   - Funksional authenticated app layout, responsive sidebar/header, flash message.
   - Projects üçün `_form.blade.php` create/edit reuse edilir.
   - Login-də ayrıca `LoginRequest`, remember-me və rate limiting var.

8. **Activity və Dashboard üçün role-aware service bazası var.**
   - Activity scope admin/manager/member üçün ayrılır.
   - Dashboard counts, overdue, completed today, my tasks və recent activity verir.

9. **Pest dependency-si quraşdırılmış vəziyyətdə lock olunub.**
   - Target project üçün test runner-i yenidən seçməyə ehtiyac yoxdur.

## 8. Project B — əsas çatışmazlıqlar

1. **Projects list data leak:** `EloquentProjectRepository::paginate()` actor qəbul etmir və bütün project-ləri qaytarır. `viewAny` permission-i olan member başqa project-lərin name/owner/status/dates məlumatını görə bilər.
2. **Tasks list data leak:** `EloquentTaskRepository::paginate()` actor scope etmir. Member bütün task-ları və filter option kimi bütün project/user siyahısını görə bilər.
3. `routes/api.php` boşdur; module `routes/api.php`, API controller/request/resource-ları və Sanctum token endpoint-ləri yoxdur.
4. Livewire dependency-si olsa da dörd approved component-dən heç biri yoxdur.
5. `DatabaseSeeder` `RolePermissionSeeder`-i çağırmır və yaratdığı test user-ə rol vermir. Seed olunan user login etsə də module permissions əldə etmir.
6. Project update archived project-i bloklamır. `ProjectPolicy::update` və `ProjectService::update` archived state yoxlamır.
7. Duplicate membership və bir sıra task business violations `LogicException` verir. HTTP layer mapping olmadığından bunlar istifadəçiyə `500` kimi çıxa bilər.
8. Project və Task list controller-ləri repository-ni birbaşa çağırır. Bu, source-of-truth-dakı HTTP → DTO → Service → Repository flow-u və gələcək Web/API paylaşımını zəiflədir.
9. `ProjectMemberService` və dashboard/activity query-ləri birbaşa Eloquent yazır; əsas aggregates üçün tələb olunan repository standardı tamamlanmayıb.
10. `ActivityQueryService::filterOptions()` MySQL-specific `JSON_UNQUOTE(JSON_EXTRACT(...))` istifadə edir; SQLite seçimi ilə portable deyil.
11. Activity migration-da `down()` yoxdur və project-members schema iki migration ilə sonradan “align” edilir. Yeni merged baseline üçün bunlar squash edilməlidir.
12. Activity update log-ları əsasən yalnız dəyişən field adlarını saxlayır; TaskFlow tələb etdiyi old/new values tam deyil.
13. Dashboard-da project üzrə task distribution yoxdur və dashboard API yoxdur.
14. Project detail task-ları göstərmək əvəzinə yalnız task list-ə link verir.
15. `resources/js/app.js` boşdur; tələb olunan confirmation/modal/preview/copy/counter behaviors yoxdur.
16. Bütün business Blade-ları bir app layout istifadə edir, amma ayrıca reusable `guest` layout/component sistemi yoxdur. Login böyük standalone HTML-dir.
17. Layout və bir çox controller/view bir sətirə sıxılıb; behavior işlək olsa da junior üçün maintainability və review çətindir.
18. Pest business testləri yoxdur. Root-dakı `PersistenceProbeTest.php` configured `tests/Feature`/`tests/Unit` suite-lərinə daxil deyil.
19. `phpunit.xml` MySQL `taskflow_test`-ə sərt bağlıdır və test `APP_KEY` təyin etmir; portable clean checkout testləri üçün zəif bazadır.
20. Project/Task/Comment/Attachment factory-ləri yoxdur.
21. Attachment delete zamanı storage faylı DB transaction commit-dən əvvəl silinir; DB rollback faylı geri qaytara bilməz.

## 9. Severity üzrə issue register

### Critical

#### C-01 — Project A-nın əsas feature scope-u yoxdur

- **Problem:** Tasks, Activity və Dashboard modulları, həmçinin onların Web/API/database funksionallığı yoxdur.
- **Location:** `taskflow-main-ehmed/Modules/`
- **Why:** TaskFlow-un əsas domen tələblərinin böyük hissəsi təmin edilmir.
- **Solution:** Bu modulları A-da yenidən yazmaq əvəzinə B-ni base götürmək.
- **Merge decision:** B-dən KEEP; A module-status ghost entry-lərini REMOVE.

#### C-02 — Project A authenticated layout content-i udur

- **Problem:** Layout tam comment-dir.
- **Location:** `taskflow-main-ehmed/resources/views/layouts/app.blade.php`
- **Why:** Projects Blade view-ları bu layout-u extend edir və faktiki content/asset render olunmur.
- **Solution:** B-nin işlək app layout-unu base götürmək və partial-lara bölmək.
- **Merge decision:** A layout REMOVE; B layout KEEP + REFACTOR.

#### C-03 — Project B list endpoint-lərində record scope yoxdur

- **Problem:** Projects və Tasks paginate query-ləri actor-a görə məhdudlaşdırılmır; filter option-lar da bütün project/user-ları qaytarır.
- **Location:** `Modules/Projects/app/Repositories/EloquentProjectRepository.php`, `Modules/Tasks/app/Repositories/EloquentTaskRepository.php`
- **Why:** Member başqa project/task metadata-sını görə bilər; bu, açıq security requirement-i pozur.
- **Solution:** Repository signatures-a `User $actor` əlavə etmək, admin/manager/member visibility query-sini mərkəzləşdirmək, option query-lərini də eyni scope ilə qurmaq.
- **Merge decision:** B query functionality KEEP, visibility hissəsi REWRITE; A-nın user-scoped Projects ideyasından istifadə et.

#### C-04 — Merged API üçün tələb olunan endpoint-lər yoxdur

- **Problem:** A yalnız auth + Projects API verir; B heç bir API vermir.
- **Location:** hər iki layihənin `routes/api.php` və module route/controller qovluqları.
- **Why:** REST API və Sanctum ilkin tapşırığın əsas tədris nəticəsidir.
- **Solution:** A auth/projects bazasını düzəldib B services üzərində bütün Tasks/Activity/Dashboard API-ni yazmaq.
- **Merge decision:** A-dan KEEP + REFACTOR; qalanını IMPLEMENT FROM SCRATCH.

### High

#### H-01 — Livewire yalnız dependency-dir

- **Location:** hər iki layihə; `app/Livewire` və module `app/Livewire` component-ləri yoxdur.
- **Why:** `TaskFlow.md` dörd konkret component tələb edir; package-in composer-də olması implementation deyil.
- **Solution:** `QuickTaskCreate`, `TaskFilters`, `TaskStatusSelector`, `TaskCommentForm` service/policy sərhədləri ilə yazılmalıdır.
- **Merge decision:** IMPLEMENT FROM SCRATCH.

#### H-02 — Project A role adları ziddiyyətlidir

- **Location:** `EloquentProjectRepository.php`, `ProjectPolicy.php`, `RolePermissionSeeder.php`, Projects tests.
- **Why:** `Admin`/`admin` və `Project manager`/`project_manager` fərqi authorization və test nəticəsini dəyişir.
- **Solution:** B-nin `UserRole`/`PermissionName` enum-larını yeganə source kimi istifadə etmək.
- **Merge decision:** A raw strings REMOVE; B enums KEEP.

#### H-03 — Project B archived project-i dəyişməyə imkan verir

- **Location:** `ProjectPolicy::update`, `ProjectService::update`.
- **Why:** Source-of-truth archived project-in dəyişdirilməməsini critical test kimi tələb edir.
- **Solution:** Policy-də deny, service-də business guard və Web/API testləri.
- **Merge decision:** A behavior-ni B service/policy-yə PORT + TEST.

#### H-04 — Project B domain exception-ları HTTP 500 ola bilər

- **Location:** `ProjectMemberService`, `TaskService`, `ProjectService::activate`.
- **Why:** Duplicate member, inactive project və invalid actor user input/domain conflict-dir, server fault deyil.
- **Solution:** Field-ə aid hallarda `ValidationException`; ayrıca domain exception-lar üçün API/Web render mapping (`422`/redirect errors).
- **Merge decision:** REFACTOR.

#### H-05 — Test infrastrukturu requirement-i sübut etmir

- **Location:** A Projects tests və `phpunit.xml`; B `tests/`, `phpunit.xml`.
- **Why:** A-da naming/route/migration uyğunsuzluqları var; B-də business test yoxdur və root probe suite-ə daxil deyil.
- **Solution:** B Pest runner + A test intent-i; SQLite `:memory:`, module migrations, factories və tam critical matrix.
- **Merge decision:** A tests PORT/REWRITE; B runner KEEP; probe/example tests REMOVE.

#### H-06 — Project A module migration registration zəifdir

- **Location:** `Modules/Projects/app/Providers/ProjectsServiceProvider.php`.
- **Why:** Normal app/test migrate flow module cədvəllərini görməyə bilər.
- **Solution:** B provider-lərindəki `loadMigrationsFrom` pattern-i saxlanmalıdır.
- **Merge decision:** B provider lifecycle KEEP; A Route/API registration ideyası ayrıca birləşdirilir.

#### H-07 — Project A API path case-i portable deyil

- **Location:** `Http/Controllers/APi`, `Http/Requests/APi`; namespace `Api`.
- **Why:** Linux filesystem-də PSR-4 lookup exact case tələb edir.
- **Solution:** Qovluqları `Api/V1` kimi normalize etmək.
- **Merge decision:** REFACTOR BEFORE PORT.

#### H-08 — Activity audit payload tam deyil

- **Location:** B `ProjectService`, `TaskService`, `ActivityRecorder`.
- **Why:** `TaskFlow.md` old/new data tələb edir; update log-ları yalnız field adlarını saxlayır.
- **Solution:** Safe whitelist üzrə old/new snapshot, canonical keys `old`/`new`, secrets exclusion tests.
- **Merge decision:** B recorder KEEP + REFACTOR.

### Medium

- A request validation max lengths/date ordering/member role enum baxımından zəifdir.
- A API create status code-ları və `per_page` cap düzgün deyil.
- B list query-ləri controller-dən repository-yə birbaşa gedir; query service/use-case service araya salınmalıdır.
- B `ProjectMemberService` repository interface-dən istifadə etmir.
- B Activity filter option query-si DB-portable deyil.
- B migration baseline-i squash və reversible edilməlidir.
- B attachment file deletion DB transaction ilə real atomik deyil; after-commit cleanup/compensation lazımdır.
- B Dashboard project üzrə task distribution vermir.
- Hər iki layihədə module factories yoxdur.
- Hər iki layihədə Livewire loading/error state və vanilla JS behavior yoxdur.
- B app layout partial/component-lara ayrılmayıb; guest layout yoxdur.
- A create/edit Blade duplication-u var.

### Low

- A-da boş `ProjectMetricsService` və olmayan modullar üçün status entry-ləri var.
- B-də root persistence probe və `storage/test-file-diagnostics/*` development artifact-ları var.
- Bir sıra B class/view-ları one-line formatdadır; review və tədris üçün formatlanmalıdır.
- A import ordering və trailing comma/naming ardıcıllığı zəifdir.
- Hər iki layihənin bəzi docs-larında installed/planned package statusu real composer state ilə uyğun deyil.

## 10. Birbaşa müqayisə və merge qərarı

| Sahə | Project A | Project B | Qərar |
| --- | --- | --- | --- |
| Base application | Dar scope | Dörd modul və daha çox real flow | **B base** |
| Projects domain | Sadə CRUD/member | Enum, owner membership, transactions, slug, activity | **B service/model**, A contracts/scope ideyası |
| Tasks domain | Yox | Əsas Web/domain flow var | **B KEEP + security refactor** |
| Activity | Yox | Recorder/query/UI var | **B KEEP + payload/portability refactor** |
| Dashboard | Debug səhifəsi | Role-aware dashboard var | **B KEEP + missing distribution/API** |
| Repository standardı | Tələb olunan qovluq/naming-ə yaxındır | Functional query-lər, amma standard natamam | **A structure + B logic** |
| Service layer | Shallow | Real business use-case-lər | **B** |
| DTO/enum | Natamam | Daha geniş və typed | **B + DTO split** |
| Web auth | Login/register, throttle yoxdur | Login request, remember, throttle | **B** |
| Sanctum auth | Real implementation | Yox | **A port** |
| Business API | Projects var | Yox | **A Projects port + qalanı yeni** |
| Layout/UI | Qırıq layout | Funksional və daha keyfiyyətli UI | **B refactor** |
| Livewire | Yox | Yox | **Yeni** |
| Tests | Faydalı ssenari başlanğıcı, amma qüsurlu | Pest installed, test yoxdur | **B runner + rewritten A scenarios** |
| DB schema | Projects only | Əsas domen tam | **B squash/fix** |

## 11. Base seçimi

**Merged project üçün Project B (`taskflow-main-ferhad`) base seçilməlidir.**

Səbəblər:

- Dörd tələb olunan modul artıq real kodla mövcuddur.
- Tasks status/assignment, comments, attachments, activity və dashboard use-case-ləri sıfırdan yazılmalı deyil.
- Service transaction-ları, enum/cast-lar və UI A-dan daha yetkindir.
- B-də Pest artıq lock olunub.
- A-nı base seçmək B-nin böyük hissəsini port etmək və faktiki üçüncü tətbiqi yenidən qurmaq demək olardı.

Bu seçim B-nin olduğu kimi qəbul edilməsi deyil. Projects/Tasks data scope düzəlmədən, API əlavə edilmədən və tests yazılmadan merged project istifadə edilə bilməz.

## 12. Konkret keep/refactor/remove/new qərarları

### Project A-dan saxlanacaq

- `app/Data/CreatePersonalAccessTokenData.php`
- `app/Services/AuthenticationService.php`
- `app/Http/Controllers/Api/V1/AuthenticationController.php`
- `app/Http/Requests/Api/V1/CreatePersonalAccessTokenRequest.php`
- `app/Http/Resources/AuthenticatedUserResource.php`
- `routes/api.php` auth endpoint ideyası
- Projects API controller/request/resource-ları, path casing və response semantics düzəldilməklə
- `Repositories/Contracts` + `Repositories/Eloquent` qovluq standardı və interface binding pattern-i
- Projects visibility testlərinin intent-i
- SQLite `:memory:` test konfiqurasiya ideyası
- `DatabaseSeeder`-in role seeder və rol verilmiş demo users çağırması

### Project B-dən saxlanacaq

- Dörd module və provider lifecycle-i
- Project/Task enum-ları və model cast/relation-ları
- `ProjectService`, `TaskService`, `TaskAssignmentService`, `TaskStatusService`, comment/attachment services
- Transaction və meaningful activity bazası
- Task filter/sort/eager-loading query məntiqi
- Project owner-in manager membership kimi avtomatik əlavə edilməsi
- Bütün əsas migrations, squash və constraint fix-ləri ilə
- Authenticated layout, login UI, Projects/Tasks/Activity/Dashboard Blade-ları
- `LoginRequest` rate limiter və remember flow
- Permission/role enum və seeder xəritəsi
- Activity recorder/query/display bazası
- Dashboard role-aware scope bazası
- Pest dependencies/config skeleton

### Refactor ediləcək

- B repository-ləri A qovluq/interface standardına.
- B list query-ləri actor visibility scope və validated filter DTO-ları ilə.
- B flat controller/request qovluqları `Web` və `Api/V1` ayrımına.
- B `ProjectData` create/update DTO-larına; Task date field-ləri immutable date value-lara.
- B activity payload-ları old/new values ilə.
- B layout guest/app + partial/component strukturuna.
- A API `APi` case, status code, collection, pagination cap və nested `{user}` route-a.
- B service exception-ları istifadəçiyə təhlükəsiz domain/validation response-a.
- Attachment delete compensation/after-commit strategiyasına.

### Silinəcək

- A-nın commented `layouts/app.blade.php`-si və debug `dashboard.blade.php`-si.
- A-nın public registration flow-u (ayrıca product qərarı verilənədək).
- A-nın boş `ProjectMetricsService`-i; yalnız real metric method əlavə ediləndə yenidən yaradılacaq.
- A-dakı ghost module status entry-lərindən yaranan yanlış baseline.
- A duplicated project create/edit markup-ı.
- B `PersistenceProbeTest.php`, `storage/test-file-diagnostics/*` və default example/probe testlər.
- B `align_project_members_table` migration-u merged clean baseline-dan.
- Eyni məqsədli A/B Project service/repository/controller-lərinin zəif variantı.

### Sıfırdan implement ediləcək

- Tasks, comments, attachments, activity və dashboard API-ləri.
- Task/Activity/Dashboard API Resource və Collection-ları.
- Dörd approved Livewire component.
- Project/Task/Activity API/Web query request-ləri və filter DTO standardı.
- Project/Task/Comment/Attachment factories.
- Project metrics və dashboard project distribution.
- Vanilla JS behavior-ları.
- Tam Pest critical test matrix.
- API rate limit və consistent exception response mapping.

## 13. Son 20 suala qısa cavab

1. **A-nın ən yaxşı hissələri:** Sanctum token flow, Projects API/Resources, repository contract qovluqları, Projects test intent-i, SQLite test config.
2. **B-nin ən yaxşı hissələri:** dörd modul, service business logic, transactions, enum/casts, activity, dashboard və işlək Blade UI.
3. **A-nın əsas problemləri:** üç modul yoxdur, layout qırıqdır, API natamamdır, Livewire yoxdur, role casing və test/migration problemləri var.
4. **B-nin əsas problemləri:** list scope security gap, API/Sanctum yoxdur, Livewire yoxdur, tests yoxdur, archived update və exception/portability qüsurları var.
5. **Hər ikisində çatışmayan:** tam API, dörd Livewire component, complete test matrix, module factories, project task distribution, tələb olunan JS behavior-ları və hazırda real event/listener implementasiyası. Sonuncu `TaskFlow.md` Section 28-ə uyğun olaraq initial merge blocker-i deyil, decoupling mərhələsinə saxlanılır.
6. **Base:** B; çünki domen və UI səthinin böyük hissəsi mövcuddur.
7. **A-dan götürüləcək:** auth token API, Projects API bazası, contracts/Eloquent strukturu, test ssenariləri və SQLite config.
8. **B-dən götürüləcək:** modules, domain services, enum/models/migrations, activity/dashboard, auth UI və layout.
9. **Refactor:** B scope/repositories/controllers/layout/activity/exceptions; A API casing/responses/validation.
10. **Silinəcək:** qırıq/duplicate/debug/empty/probe kod və ikinci zəif Project implementation.
11. **Sıfırdan:** missing APIs, Livewire, factories, remaining metrics/JS/tests.
12. **API:** module-owned `/api/v1` routes, shared services, Sanctum abilities + policies, Resources/Collections və exact status/meta formatı.
13. **Web:** B UI üzərində `Web` controller/request ayrımı, shared services və visibility-safe queries.
14. **Layout:** `guest` və authenticated `app`; navigation/header/flash/form/badge partial/component-ları; module-specific layout yalnız real fərq yaranarsa.
15. **Livewire:** yalnız QuickTaskCreate, TaskFilters, TaskStatusSelector, TaskCommentForm; hər biri authorize edib service çağırır.
16. **Repository/Service/DTO:** A interface folder standardı, B business services, use-case-specific typed DTO-lar.
17. **Sanctum:** A flow B-yə port; rate limit, abilities, policies, 401/403 tests və current-token revoke.
18. **Tests:** Pest + SQLite memory; unit service/status və feature Web/API/security/activity testləri.
19. **Merge order:** əvvəl B base cleanup/security, sonra layer standardı/auth, Projects, Tasks, API, UI/Livewire, Activity/Dashboard, tests/stabilization.
20. **Roadmap:** ilkin merge-dən sonra architecture stabilization, cross-module contracts/events, advanced collaboration features, quality/performance və delivery/observability.
