# TaskFlow — məhsul və arxitektura roadmap

> `implementation-plan.md` mövcud iki layihənin necə birləşdiriləcəyini izah edir. Bu sənəd isə həmin ilkin TaskFlow tamamlandıqdan sonra sistemin real məhsul kimi hansı ardıcıllıqla inkişaf etdiriləcəyini göstərir.

## Roadmap prinsipləri

- Əvvəl işlək və test olunan modular monolith, sonra decoupling.
- Product ehtiyacı olmayan abstraction əvvəlcədən yaradılmır.
- Hər yeni feature əvvəl mövcud service/repository/policy sərhədlərinə uyğunlaşdırılır.
- Security, tests və observability ayrıca “son iş” deyil; hər phase-in Definition of Done hissəsidir.
- Dəyişikliklər kiçik, izah edilə bilən junior task-larına bölünür.

## Phase 1 — Initial TaskFlow baseline-in tamamlanması

**Məqsəd:** `TaskFlow.md`-dəki ilkin scope-u production namizədi səviyyəsinə gətirmək.

Bu phase-in detalları `implementation-plan.md`-dədir. Exit criteria:

- Projects, Tasks, Comments, Attachments, Activity və Dashboard Web/API axınları tamamdır.
- Sanctum + abilities + permissions + policies birlikdə işləyir.
- Dörd approved Livewire component mövcuddur.
- Critical Pest suite keçir.
- Actor-scoped queries data isolation-ı qoruyur.
- İstifadəçi rolları və manual browser/API checklist təsdiqlənib.

**Junior learning outcome:** HTTP, validation, DTO, service, repository, policy, resource və test qatlarının bir use-case daxilində necə birləşdiyini izah etmək.

## Phase 2 — Architecture stabilization

**Məqsəd:** İlk işlək versiyada yığılmış texniki borcu ölçmək və module sərhədlərini dəyişmədən kodu sabitləşdirmək.

### İşlər

- Module dependency graph çıxar: `Tasks -> Projects/Activity`, `Dashboard -> Projects/Tasks/Activity`.
- Public module service-lərini və internal class-ları sənədləşdir.
- Repository signatures, DTO naming, exception taxonomy və API response conventions üçün architecture tests/checklist əlavə et.
- Project/task visibility scope-larını reusable query object/scope və ya repository method-larında vahidləşdir.
- Activity canonical event/payload schema-nı versiyala.
- Attachment storage lifecycle, orphan cleanup və retention policy əlavə et.
- Date/timezone, soft-delete restore/purge və audit retention qərarlarını sənədləşdir.
- API contract üçün OpenAPI və ya versioned Postman collection saxla.

### Exit criteria

- Module dependency-ləri accidental deyil, sənədləşdirilmişdir.
- Web/API/Livewire eyni use-case tests ilə davranış parity-si göstərir.
- Duplicate business rule və controller query-si qalmır.
- Public API payload dəyişiklikləri contract test ilə qorunur.

**Junior learning outcome:** “Kod işləyir” və “kodun sərhədləri sabitdir” arasındakı fərqi başa düşmək.

## Phase 3 — Modular communication və loose coupling refactor-u

**Məqsəd:** `TaskFlow.md` Section 28-də nəzərdə tutulan refactor-u real problemlər üzərində tətbiq etmək.

### 3.1 Projects access contract

- Tasks daxilindəki direct Project access nöqtələrini inventarlaşdır.
- Minimal `ProjectAccessInterface` və lazım olan read DTO-larını dizayn et.
- Projects modulunda Eloquent implementation yarat.
- Task creation/assignment policy input-larını contract arxasına keçir.
- Fake implementation ilə Tasks service unit tests yaz.

### 3.2 Metrics contracts

- Dashboard-un birbaşa Project/Task query-lərini ölç.
- `ProjectMetricsInterface` və `TaskMetricsInterface` kimi yalnız real dashboard ehtiyaclarını ifadə edən contract-lar yarat.
- Dashboard-u model namespace-lərindən ayır.

### 3.3 Domain events və listeners

- Cross-module side effect-ləri seç: task/project activity, notifications və gələcək webhooks.
- `TaskCreated`, `TaskAssigned`, `TaskStatusChanged`, `ProjectMemberAdded` kimi immutable domain event-lər yarat.
- Activity direct service call-larını mərhələli şəkildə listener-lərə keçir.
- Listener idempotency və transaction/after-commit davranışını test et.
- Sadə direct return-value tələb edən əməliyyatları event-ə çevirmə.

### 3.4 Module resilience

- Module enable/disable ssenarisini local/test mühitində araşdır.
- Composer/module dependency metadata-sını aydınlaşdır.
- Contract implementation tapılmadıqda fail-fast mesajı və health check əlavə et.

### Exit criteria

- Tasks business service-i Project Eloquent modelini birbaşa tanımır.
- Dashboard metrics contracts vasitəsilə işləyir.
- Activity side effect-ləri event/listener vasitəsilə, duplicate log yaratmadan işləyir.
- Before/after dependency graph və trade-off report-u var.

**Junior learning outcome:** Interface-in repository pattern üçün istifadəsi ilə cross-module dependency inversion arasındakı fərqi praktik göstərmək.

## Phase 4 — Teams və workspace modeli

**Məqsəd:** TaskFlow-u tək şirkət dataset-indən bir neçə team/workspace-i təhlükəsiz idarə edən məhsula çevirmək.

### İşlər

- `Workspace`/`Team`, membership və invitation domain modelini dizayn et.
- Project owner/member modelini workspace sərhədi ilə uyğunlaşdır.
- Role/permission scope-u global roldan workspace role-a mərhələli keçir.
- Workspace switcher və current-workspace context əlavə et.
- Bütün Projects/Tasks/Activity/Dashboard queries-də workspace isolation tətbiq et.
- Cross-workspace ID tampering və invitation security tests yaz.
- Admin user/role management UI/API əlavə et.

### Exit criteria

- Bir istifadəçi bir neçə workspace-ə fərqli rollarla üzv ola bilir.
- Heç bir Web/API/filter/export query-si cross-workspace data expose etmir.
- Workspace deletion/archive və data retention qaydaları müəyyən edilib.

**Junior learning outcome:** Multi-tenancy-də authorization ilə query scoping-in niyə birlikdə lazım olduğunu anlamaq.

## Phase 5 — Advanced task collaboration

**Məqsəd:** Mövcud project/task nüvəsinə real komanda əməkdaşlığı imkanları əlavə etmək.

Feature-lər aşağıdakı ardıcıllıqla əlavə edilməlidir:

### 5.1 Labels və saved views

- Workspace/project-scoped labels.
- Task-label many-to-many relation və indexes.
- Label filters və istifadəçinin saved filter/view-ları.
- API və Livewire TaskFilters parity-si.

### 5.2 Mentions və notifications

- Comment-lərdə `@mention` parsing və safe rendering.
- In-app və email notification preferences.
- Assignment, due-soon, status və mention notifications.
- Queue, retry, idempotency və notification read state.

### 5.3 Rich activity və audit

- Human-readable diff-lər və actor/device/IP kimi privacy-reviewed metadata.
- Admin audit export.
- Retention və legal/privacy silmə siyasəti.

### 5.4 Attachment lifecycle

- Multiple uploads, preview, download audit və malware scanning adapter-i.
- Storage quota və orphan cleanup job-u.
- Versioned attachment metadata; public URL yaratmamaq.

### Exit criteria

- Notification side effect-ləri request latency-ni bloklamır.
- Label/saved-view queries indexed və actor-scoped-dur.
- Attachment access bütün kanallarda policy ilə qorunur.

**Junior learning outcome:** Many-to-many data, queues, retries və idempotent listener-ləri real feature-də tətbiq etmək.

## Phase 6 — Planning və workflow imkanları

**Məqsəd:** TaskFlow-u sadə list-dən planlaşdırma və iş axını sisteminə genişləndirmək.

### 6.1 Boards

- Status əsaslı Kanban board read model-i.
- Accessible drag/drop UI; backend status service yenə authoritative qalır.
- Optimistic UI conflict handling və audit.

### 6.2 Task dependencies

- Blocking/blocked-by relation və cycle prevention.
- Blocked task status qaydaları.
- Dependency graph üçün index və testlər.

### 6.3 Recurring tasks

- Recurrence definition, timezone və next-run calculation.
- Scheduler/queue ilə idempotent task generation.
- Template change-in artıq yaranmış task-lara təsir qaydası.

### 6.4 Milestones və workload

- Project milestones, progress aggregation və overdue risk.
- Assignee workload/capacity view.
- Dashboard metrics contracts-in genişləndirilməsi.

### Exit criteria

- Drag/drop business rule-u bypass etmir.
- Dependency cycle testləri var.
- Recurring job retry zamanı duplicate task yaratmır.
- Metrics böyük dataset üçün ölçülüb.

**Junior learning outcome:** UI state, domain invariant və background processing arasındakı sərhədləri öyrənmək.

## Phase 7 — Search, integration və API lifecycle

**Məqsəd:** Daxili automation/reporting use-case-lərini təhlükəsiz və idarə olunan platformaya çevirmək.

### İşlər

- Projects/tasks/comments üçün permission-aware global search.
- Kiçik dataset-də DB full-text; ehtiyac sübut olunarsa external search adapter-i.
- Personal token abilities üçün user-selectable least-privilege scopes və expiry/rotation.
- Token management UI: device, last used, expiry, revoke-all.
- Idempotency key dəstəyi, xüsusilə automated task creation üçün.
- Webhook subscriptions, signing secret, retry və delivery log.
- API deprecation/versioning policy və contract tests.
- CSV export/reporting job-ları; export authorization və expiring downloads.

### Exit criteria

- Search yalnız actor-un görə bildiyi records qaytarır.
- Token default olaraq minimum ability ilə yaradılır və rotate edilə bilir.
- Webhook replay və signature verification testləri var.
- API breaking change policy sənədləşdirilib.

**Junior learning outcome:** Integration security, idempotency və API lifecycle idarəetməsi.

## Phase 8 — Testing və quality maturity

**Məqsəd:** Testləri yalnız bug yoxlamasından sistem davranışının canlı spesifikasiyasına çevirmək.

### İşlər

- Risk əsaslı test pyramid: service unit, repository integration, Web/API/Livewire feature.
- Role × ability × policy permission matrix üçün dataset-driven tests.
- API schema/contract və backwards-compatibility tests.
- Queue/event/listener idempotency tests.
- Storage, clock, notification və external integration fakes.
- Mutation testing-i əvvəlcə status transitions və policies kimi critical code-da pilot et.
- Static analysis səviyyəsini mərhələli yüksəlt.
- Architecture tests: controller-də Eloquent/DB facade, Livewire-da repository direct injection və module boundary violations tapılsın.
- Accessibility və browser smoke tests üçün kiçik, stabil suite.

### Exit criteria

- Critical domain qaydaları mutation/static analysis ilə real qorunur.
- Flaky tests izlənir və qəbul edilmir.
- Coverage faizi tək hədəf deyil; risk matrix-də hər critical behavior testlə bağlıdır.

**Junior learning outcome:** Test sayından çox testin qoruduğu risk və sərhədi qiymətləndirmək.

## Phase 9 — Performance və scale

**Məqsəd:** Ölçülmüş bottleneck-ləri aradan qaldırmaq; əvvəlcədən optimizasiya etməmək.

### İşlər

- Query telemetry və slow-query baseline.
- Project/task/activity list-lərində explain plan və composite index audit.
- Offset pagination problem yaratdıqda cursor pagination qiymətləndir.
- Dashboard metric query-lərini batch/cached read model-ə keçir; invalidation qaydasını sənədləşdir.
- Activity log partition/retention/archive planı.
- Large export, notifications, previews və cleanup işlərini queue-ya keçir.
- Cache key-lərində workspace/actor scope saxla.
- Load test ssenariləri: task list filters, dashboard, activity və attachment download.

### Exit criteria

- SLO üçün p50/p95 latency və error-rate baseline var.
- Cache heç bir authorization boundary-ni pozmur.
- Hər index/cache dəyişiklikdən əvvəl və sonra ölçü ilə əsaslandırılır.

**Junior learning outcome:** N+1, index, cache və queue qərarlarını profiling nəticəsi ilə vermək.

## Phase 10 — Deployment, CI/CD və observability

**Məqsəd:** TaskFlow-u təkrarlana bilən, monitor olunan və geri qaytarıla bilən şəkildə işlətmək.

### CI/CD

- Pull request pipeline: composer validation, Pint, static analysis, Pest, frontend build və security audit.
- Migration compatibility check və deployment checklist.
- Staging environment və smoke tests.
- Versioned release, rollback və feature flag qaydası.

### Operations

- Structured logs və request/correlation ID.
- Exception tracking, queue failure dashboard və alert-lər.
- Health/readiness checks: DB, cache, queue və storage.
- Metrics: request latency/error, queue age/failure, notification/webhook failure, storage usage.
- Backup, restore drill və recovery objectives.
- Secret management və credential rotation; secret-lər log və artifact-a düşməsin.
- Scheduler/queue worker supervision və zero-downtime deployment planı.

### Security operations

- Dependency və container/image scanning.
- Rate-limit tuning və suspicious token/login monitoring.
- Incident response playbook və audit export.
- Periodic authorization regression review.

### Exit criteria

- Hər release avtomatik test/build gate-dən keçir.
- Rollback və backup restore praktik olaraq sınaqdan keçirilib.
- Əsas SLO pozuntuları alert yaradır və owner/runbook məlumdur.

**Junior learning outcome:** Tətbiqin yazılması ilə etibarlı şəkildə işlədilməsi arasındakı fərqi öyrənmək.

## Prioritetləşdirmə qaydası

Yeni təklif bu ardıcıllıqla qiymətləndirilməlidir:

1. Security/data isolation problemi həll edirmi?
2. Mövcud `TaskFlow.md` flow-unun keyfiyyətini artırırmı?
3. Real istifadəçi ehtiyacı və measurable outcome varmı?
4. Mövcud service/module sərhədinə uyğun gəlirmi?
5. Test, migration, rollout və rollback planı varmı?
6. Juniorun izah edə bilməyəcəyi lazımsız abstraction yaradırmı?

Bu qayda olmadan boards, recurring tasks, webhooks və external search kimi cəlbedici feature-lər core authorization və data integrity işlərini kölgədə qoya bilər.

