# TF-100 — Portable Pest infrastructure

## Məqsəd

TF-100-un məqsədi testləri developer-in lokal MySQL database-indən asılı olmayan, SQLite `:memory:` ilə təkrarlana bilən mühitdə işə salmaq idi. Bu baza sonrakı taskların migration, factory və business-rule testlərini təhlükəsiz və eyni şəraitdə yoxlaması üçün lazımdır.

## Başlanğıc vəziyyət

`phpunit.xml` artıq SQLite, array cache/session/mail və sync queue üçün hazırlanmışdı, lakin default suite işə düşən kimi `project_members` migration-da dayanırdı. Cədvəl ilk migration-da primary key olmadan yaradılır, sonrakı `2026_08_14_110000_align_project_members_table.php` isə `ALTER TABLE` vasitəsilə `id` primary key əlavə etməyə çalışırdı. SQLite mövcud cədvələ primary key sütununun bu formada əlavə edilməsini dəstəkləmir.

Testlərdə `Storage::fake('local')` çağırılsa da, Laravel fake disk üçün repository-dəki `storage/framework/testing` yolunda qovluq yaratmaq istəyirdi. Bu qovluq cari proses üçün yazıla bilən deyildi və test isolation-u təmin edən fake storage test başlamazdan əvvəl xəta verirdi.

## Edilən dəyişikliklər

- `Modules/Projects/database/migrations/2026_08_14_100001_create_project_members_table.php` faylında `id` sütunu `project_members` cədvəlinin ilkin yaradılmasına əlavə edildi. Yeni, fresh SQLite schema artıq primary key-i `CREATE TABLE` zamanı alır.
- `Modules/Projects/database/migrations/2026_08_14_110000_align_project_members_table.php` faylından `id` əlavə/silmə əməliyyatları çıxarıldı. Migration indi yalnız sonradan əlavə olunmuş `member_role` və `joined_at` sahələrinə cavabdehdir.
- `phpunit.xml` faylında `LARAVEL_STORAGE_PATH=/tmp/taskflow-testing-storage` təyin edildi. Test storage-i project-in real storage yolundan ayrıldı.
- `tests/TestCase.php` tətbiq kernel-i boot olunmazdan əvvəl yalnız müvəqqəti test storage üçün cache, session, view və log qovluqlarını yaradır. Kod Laravel-in standart `createApplication()` davranışındakı cached config və cached route yoxlamalarını qoruyur, sonra framework-u boot edir. Mövcud `Storage::fake('local')` hər test üçün private disk davranışını fake storage ilə əvəz edir.

Bu dəyişikliklər TF-100 və TF-200 arasında yaranan dependency dövrəsini qırmaq üçün ən kiçik dəyişiklikdir. `project_members` migration-ın tam foreign key, index, nullability və rollback auditi, Activity migration-a `down()` əlavə edilməsi və persistent database upgrade sübutu TF-200-ün tam sahibliyindədir və burada qəsdən edilmədi.

## Texniki izah

Migration-lar fresh database qurulanda ardıcıllıqla işləyir. `id` sütunu cədvəlin bir hissəsidirsə, onu `Schema::create()` içində yaratmaq həm SQLite, həm də MySQL üçün normal davranışdır. Əvvəlki yanaşma isə mövcud cədvəli dəyişərək primary key yaratmaq istəyirdi; SQLite-in `ALTER TABLE` imkanları buna kifayət etmir.

Persistent database-lərdə əvvəllər hər iki migration artıq işlənibsə, bu mənbə migration-larının dəyişdirilməsi həmin database-də yenidən icra olunmur və mövcud məlumatı dəyişmir. Buna baxmayaraq, belə environment-lər üçün upgrade/backfill sübutu TF-200-də ayrıca aparılmalıdır.

Laravel view compiler cache və fake filesystem üçün storage alt qovluqlarına ehtiyac duyur. Test base class bu qovluqları yalnız `/tmp/taskflow-testing-storage` altında yaradır. Beləliklə testlər real application storage, developer faylları və persistent database-dən təcrid olunur.

## Acceptance Criteria

- **Suite application/module testlərini MySQL server olmadan tapır:** `vendor/bin/pest --list-tests` iki TF-100 infrastructure testini tapdı. Default run SQLite `:memory:` ilə keçdi.
- **Module migration-ları testdə yüklənir:** `php artisan test --compact` PASS oldu. Migration testində `users`, `projects`, `project_members`, `tasks`, `task_comments` və `task_attachments` cədvəllərinin SQLite memory database-də yaranması yoxlanır.
- **Test environment naməlum persistent database istifadə edə bilməz:** `phpunit.xml` SQLite `:memory:`-ni force edir. MySQL profilini environment variable olmadan başladanda xüsusi `TASKFLOW_MYSQL_TEST_DATABASE` tələb edən təhlükəsizlik xətası alındı; buna görə lokal `.env` database-i təsadüfən istifadə edilmir.

## Testlər və yoxlama

`php artisan test --compact`

İlkin nəticə: FAIL — SQLite `project_members` cədvəlinə `ALTER TABLE` ilə primary key əlavə edilməsini rədd etdi.

`php artisan test --compact`

Son nəticə: PASS — 2 test və 14 assertion uğurla keçdi.

`php vendor/bin/phpunit --configuration phpunit.xml --validate-configuration`

Nəticə: PASS — PHPUnit XML configuration etibarlıdır.

`vendor/bin/pest --list-tests`

Nəticə: PASS — default suite iki infrastructure testini kəşf edir, yəni sıfır-test nəticəsi yoxdur.

`php -l tests/TestCase.php && php -l tests/Pest.php && php -l Modules/Projects/database/migrations/2026_08_14_100001_create_project_members_table.php && php -l Modules/Projects/database/migrations/2026_08_14_110000_align_project_members_table.php`

Nəticə: PASS — dəyişdirilən bütün PHP fayllarında syntax xətası yoxdur.

`vendor/bin/pest --configuration phpunit.mysql.xml --list-tests`

Nəticə: Gözlənilən təhlükəsizlik rəddi — `TASKFLOW_MYSQL_TEST_DATABASE` verilmədiyi üçün profil test run başlamazdan əvvəl dayandı. Bu nəticə MySQL-in təsadüfən istifadə edilmədiyini sübut edir; real MySQL compatibility run üçün ayrıca task icazəsi verilməyib.

## Qarşılaşılan problemlər

Birinci problem SQLite-in existing cədvələ autoincrement primary key əlavə etməməsi idi. Bu, TF-100 test loading-ni bloklasa da, schema auditinin tam həcmi TF-200-ə aiddir. Yalnız fresh schema-nı portativ edən `id` yerləşdirməsi dəyişdirildi.

İkinci problem fake storage qovluğunun repository storage sahəsində yaradıla bilməməsi idi. Fake disk silinmədi və real diskə keçilmədi; test storage path `/tmp`-ə köçürüldü, tələb olunan framework qovluqları kernel boot-dan əvvəl yaradıldı.

## Yekun vəziyyət

**COMPLETE**

TF-100-un hər üç acceptance criterion-u executable sübutla qarşılandı. Dependency dövrəsini açmaq üçün edilən migration dəyişikliyi minimal saxlanıldı və TF-200-ün qalan schema işini əvəz etmir.

## Növbəti task

`TF-102 — Factories and deterministic seeders` indi dependency-ready-dir: onun yeganə dependency-si olan TF-100 verified vəziyyətindədir. TF-103 TF-200-ün dependency-si deyil və TF-102-dən sonra TF-200-ə keçməzdən əvvəl implementasiya edilməyəcək.

# TF-102 — Factories and deterministic seeders

## Məqsəd

TF-102 testlərdə təkrarlanan manual model qurulmasını factory-lərlə əvəz edir və local development üçün role-ları düzgün təyin olunmuş demo istifadəçilər yaradır. Seeder həm də production və testing environment-lərində məlum demo credential yaratmamalıdır.

## Başlanğıc vəziyyət

Yalnız `UserFactory` mövcud idi. Project, project membership, task, comment və inherited attachment üçün factory yox idi; `TaskComment` və `TaskAttachment` modelləri factory istifadə edə bilmirdi. `DatabaseSeeder` `RolePermissionSeeder`-i çağırmırdı və rolesuz `test@example.com` istifadəçisi yaradırdı.

## Edilən dəyişikliklər

- `database/factories` altında `ProjectFactory`, `ProjectMemberFactory`, `TaskFactory`, `TaskCommentFactory` və `TaskAttachmentFactory` əlavə edildi. Hər factory foreign key üçün uyğun factory yaradaraq test database-də etibarlı əlaqəli qeyd yaradır.
- Project status, project member role, task status/priority və user role seçimləri string əvəzinə mövcud enum-lardan istifadə edir. `active`, `completed`, `archived`, `manager`, `assigned`, `inProgress`, `done`, `asAdmin`, `asProjectManager` və `asMember` helper-ləri sonrakı testlərin niyyətini oxunaqlı saxlayır.
- `Project`, `ProjectMember`, `Task`, `TaskComment` və `TaskAttachment` modelləri factory resolver-ləri ilə tamamlandı; son iki modelə `HasFactory` əlavə edildi.
- `DatabaseSeeder` indi hər environment-də `RolePermissionSeeder` çağırır. `LocalDemoUserSeeder` yalnız `local` environment-də `admin@taskflow.test`, `manager@taskflow.test` və `member@taskflow.test` hesablarını yaradır və hər birinə uyğun `UserRole` verir. `test@example.com` artıq seed edilmir.
- `tests/Feature/FactoryAndSeederTest.php` factory əlaqələri, enum state-ləri, role helper-ləri, non-local seeding təhlükəsizliyi və local demo hesablarını yoxlayır.

## Texniki izah

Factory Eloquent modelinin test üçün etibarlı default məlumatını hazırlayır. Məsələn, `TaskFactory` task üçün Project və creator User yaradır, `assigned()` isə ayrıca assignee yaradır. Beləliklə foreign key constraint-ləri pozulmur. `UserFactory` role helper-i istifadəçi persist edildikdən sonra Spatie role-ni verir; bu səbəbdən role records əvvəl `RolePermissionSeeder` tərəfindən yaradılır.

`DatabaseSeeder` production-da demo istifadəçi yaratmır. Local environment-də ayrıca seeder idempotent `firstOrCreate` istifadə edir və hər run-da role-ni `syncRoles` ilə sabit saxlayır. Məlum `password` yalnız local demo rahatlığı üçündür və seeder-in environment guard-ı onu production/testing-ə buraxmır.

## Acceptance Criteria

- **Testlər factory istifadə edir:** Yeni feature test bütün tələb olunan domain modellərini `::factory()` ilə yaradır və əlaqələri yoxlayır.
- **Local data usable admin və canonical roles yaradır:** Local demo seeder admin, project manager və member hesablarını uyğun enum role ilə yaradır; test bunu təsdiqləyir.
- **Production silent known demo credential almır:** `DatabaseSeeder` demo seeder-i yalnız `app()->isLocal()` olduqda çağırır. Testing/non-local testində `admin@taskflow.test` və legacy `test@example.com` hesablarının yaranmadığı yoxlanır.

## Testlər və yoxlama

`php artisan test --compact tests/Feature/FactoryAndSeederTest.php`

Nəticə: PASS — 4 test, 18 assertion. Factory, enum role və seeding davranışlarını birbaşa yoxlayır.

`php artisan test --compact`

Nəticə: PASS — 6 test, 32 assertion; TF-102 ilə birlikdə TF-100 infrastructure testləri də keçir.

`php -l database/factories/UserFactory.php && php -l database/factories/ProjectFactory.php && php -l database/factories/ProjectMemberFactory.php && php -l database/factories/TaskFactory.php && php -l database/factories/TaskCommentFactory.php && php -l database/factories/TaskAttachmentFactory.php && php -l database/seeders/DatabaseSeeder.php && php -l database/seeders/LocalDemoUserSeeder.php && php -l tests/Feature/FactoryAndSeederTest.php`

Nəticə: PASS — bütün dəyişdirilən PHP fayllarında syntax xətası yoxdur.

## Qarşılaşılan problemlər

Factory/seeder implementasiyasında task-owned failure yaranmadı. Fokuslanmış testlər əvvəl 3 testlə keçdi, local environment guard-ını da sübut etmək üçün dördüncü test əlavə edildi və sonra focused/full suite yenidən uğurla işə salındı.

## Yekun vəziyyət

**COMPLETE**

TF-102 bütün acceptance criteria-ları və recorded executable test sübutu ilə verified oldu.

## Növbəti task

`TF-200 — Clean/reversible inherited schema baseline` indi dependency-ready-dir: TF-100 və TF-102 verified-dir. TF-103 bu taskın dependency-si deyil və qəsdən başlanmır.

# TF-200 — Clean/reversible inherited schema baseline

## Məqsəd

TF-200 inherited migration baseline-ını SQLite-də fresh create və rollback üçün təhlükəsiz etmək üçündür. Bu task project domain-in yeni schema-sını əlavə etmir; yalnız mövcud baseline-ın portativ və reversible olmasını yoxlayır.

## Başlanğıc vəziyyət

`activity_log` migration-ında `down()` yox idi. Users migration rollback zamanı `sessions` foreign key child cədvəlini `users`-dan sonra silirdi. Həmçinin `project_members` cədvəlinin primary key-i ayrı alignment migration-da əlavə edildiyi üçün SQLite migration loading-i bloklayırdı; bu minimal fresh-schema düzəlişi TF-100 dependency cycle-unun açılması üçün artıq edilmişdi.

## Edilən dəyişikliklər

- `database/migrations/2026_08_12_082553_create_activity_log_table.php` faylına `Schema::dropIfExists('activity_log')` edən `down()` əlavə edildi.
- `database/migrations/0001_01_01_000000_create_users_table.php` rollback sırası `sessions`, `password_reset_tokens`, `users` oldu. Dependent cədvəllər parent users cədvəlindən əvvəl silinir.
- `tests/Feature/MigrationRollbackTest.php` fresh SQLite test database-də bütün yüklənən migration-ları rollback edir və əsas cədvəllərin yaradılıb silindiyini yoxlayır.
- Audit zamanı inherited foreign key delete davranışları, unique/composite index-lər, nullable sahələr, soft delete və mövcud enum-string sütunları oxundu. Bu task yalnız baseline auditidir; Task `number` nullable problemi TF-600, yeni key/rank/type schema-ları isə onların owning tasklarında qalır.

## Texniki izah

Migration `down()` metodu deployment rollback zamanı schema-nı təhlükəsiz geri almaq üçündür. Parent cədvəl silinməzdən əvvəl onu referens edən cədvəllər silinməlidir. Yeni rollback testi Laravel-in test üçün yaratdığı SQLite `:memory:` database-də işləyir, buna görə persistent developer database-ə toxunmur.

Persistent database-də əvvəl run edilmiş migration faylının sonradan redaktə olunması onun `up()` hissəsini yenidən icra etmir. Bu taskdakı bütün up-dəyərləri fresh schema üçündür; mövcud data üçün destructive/backfill əməliyyatı yoxdur. Belə environment-də MySQL və real upgrade yoxlaması yalnız təsdiqlənmiş dedicated database ilə aparılmalıdır.

## Acceptance Criteria

- **Hər migration üçün safe `down()` yolu:** Activity migration-a çatışmayan rollback əlavə edildi; focused test bütün loaded migration-ları uğurla rollback etdi.
- **Fresh SQLite schema documented inherited baseline-a uyğundur:** Full suite 7 test/39 assertion ilə keçdi, Projects və Tasks cədvəlləri SQLite `:memory:`-də yükləndi.
- **Persistent data upgrade preservation:** Mövcud persistent migration records üçün yeni `up()` və data dəyişdirən əməliyyat əlavə edilmədi. Source migration redaktələri yalnız fresh run-a təsir edir; persistent environment üçün ayrıca approved MySQL/upgrade gate hələ tələb olunur.

## Testlər və yoxlama

`php artisan test --compact tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 1 test, 7 assertion; fresh SQLite schema rollback etdi.

`php artisan test --compact`

Nəticə: PASS — 7 test, 39 assertion.

`php -l database/migrations/2026_08_12_082553_create_activity_log_table.php && php -l database/migrations/0001_01_01_000000_create_users_table.php && php -l tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — syntax xətası yoxdur.

MySQL compatibility run: SKIPPED — təsdiqlənmiş `TASKFLOW_MYSQL_TEST_DATABASE` və ayrıca execution icazəsi yoxdur.

## Qarşılaşılan problemlər

SQLite primary key ALTER TABLE limiti TF-100 verification-u bloklayırdı. Dependency deadlock-u açmaq üçün `id` fresh create migration-a köçürüldü; tam schema audit TF-200-də aparıldı. Activity rollback çatışmazlığı və users rollback order problemi bu taskda düzəldildi.

## Yekun vəziyyət

**COMPLETE**

TF-200 SQLite fresh/rollback acceptance kriteriyaları executable testlərlə qarşılandı. MySQL run yalnız təhlükəsiz, explicit approval verilmiş environment-də ayrıca aparılmalıdır.

## Növbəti task

TF-200-ün completion-u TF-201–TF-205-in dependency-lərini avtomatik açmır. TF-100 artıq verified olduğuna görə növbəti global dependency-ready task `TF-101`-dir. O, TF-200 dependency-si deyil və bu taskda implementasiya edilməyib.

# TF-101 — Decompose inherited `qa` regression

## Məqsəd

35-testlik legacy `qa/Regression/TaskFlowRegression.pest` monolitini standart test qovluqlarına bölmək və faydalı assertion-ları Product Brief ilə uyğunlaşdırmaq.

## Başlanğıc vəziyyət

Suite hər testdə köhnə MySQL `taskflow_test` database-ini gözlədiyi üçün TF-100 SQLite `:memory:` test mühitində 35/35 test ilk assertion-da dayanırdı.

## Edilən dəyişikliklər

Legacy test bootstrap expectation-u SQLite `:memory:`-yə dəyişdirildi. Bu production code dəyişiklik deyil; real regression assertion-larının icrasını açır.

## Texniki izah

Yeni run 30 PASS, 5 FAIL və 463 assertion verdi. Activity səhifə/filter failure-ları SQLite-də olmayan MySQL `JSON_UNQUOTE` funksiyasından gəlir. Bu AUD-25-dir və plan üzrə TF-800 sahibidir; TF-101 həmin production query-ni dəyişmir.

## Acceptance Criteria

Hələ qarşılanmayıb: faydalı testlər standart qovluqlara bölünməyib və `qa/` silinməyib.

## Testlər və yoxlama

`php artisan test --compact qa/Regression/TaskFlowRegression.pest`

İlkin: FAIL — 35 test MySQL expectation-u səbəbilə dayanıb.

SQLite expectation-dan sonra: PARTIAL — 30 PASS, 5 FAIL, 463 assertion.

## Qarşılaşılan problemlər

`JSON_UNQUOTE` SQLite portability problemi TF-800 owner-dir. Legacy visibility/API assertion-ları Product Brief əsasında rewrite olunmalıdır.

## Yekun vəziyyət

**PARTIALLY COMPLETE** — TF-101 aktivdir; monolit hələ parçalanmayıb.

## Növbəti task

Cari task tamamlanmadığı üçün növbəti task seçilmir.

## Deferred istisna

2026-09-01 tarixində istifadəçi TF-101-in qalan test-miqrasiya/refactor işini explicit olaraq deferred etdi. Bu işlər tamamlanmayıb və TF-101 `in_progress` qalır: passing legacy blokların standart qovluqlara köçürülməsi, implementation-detail assertion-ların Product Brief davranışına rewrite olunması və ekvivalent coverage-dən sonra `qa/` silinməsi gələcəkdə ayrıca bərpa olunmalıdır. Bu istisna yalnız TF-101 üçündür; digər incomplete taskların avtomatik skip olunmasına icazə vermir. Canonical dependency graph-a əsasən növbəti task TF-103-dür.

## Regression xəritəsi

35 legacy blok aşağıdakı hədəf sahələrə xəritələndirildi: Auth/Admin — token management və dörd admin-user bloku; Projects — core workflow, archived project, Projects API və project-members API; Tasks — status, comments (üç blok), attachments (üç blok), Tasks API və Web index; Activity — səkkiz activity scope/filter/embedded/navigation/API bloku; Dashboard — yeddi dashboard/navigation/metrics/recent-activity/API bloku; CrossModule — core workflow və archived-project parity. Bu xəritə testlərin köçürülməsində heç bir assertion-un sahibsiz qalmaması üçün istifadə olunacaq.

SQLite run-da keçən 30 assertion bloku köçürülməyə namizəddir. Qalan Activity page/filter/navigation/API blokları `JSON_UNQUOTE` portability failure-na görə TF-800 / AUD-25-dən asılıdır. Projects API-dəki legacy visibility assertion-u isə accepted project-member visibility modelinə zidd olduğuna görə TF-205/TF-402 zamanı Product Brief contract-i ilə yenidən yazılmalıdır.

# TF-103 — Architecture guard tests

## Məqsəd

Controller, exception mapping və gələcək architecture sərhədlərini source səviyyəsində qoruyan regression guard-ları yaratmaq.

## Edilən dəyişikliklər

`tests/Architecture/ControllerBoundaryGuardTest.php` əlavə edildi. Birinci guard controller fayllarında `Model::query()`, `DB::` və `Storage::` pattern-lərini, ikinci guard isə `bootstrap/app.php` daxilində catch-all `LogicException`-in 409-a çevrilməsini yoxlayır.

## Testlər və yoxlama

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: expected FAIL — 2 test. Guard `TaskController.php`-də `Project::query()`/`User::query()` və `ProjectMemberController.php`-də `User::query()` tapdı. Bunlar TF-201/TF-202 owner-dir. İkinci guard `bootstrap/app.php` catch-all mapping-ini tapdı; owner TF-204-dür.

## Yekun vəziyyət

**PARTIALLY COMPLETE** — guard implementation mövcuddur, final PASS owning production tasklardan sonra yoxlanacaq. İstifadəçinin explicit təlimatı ilə növbəti task TF-201-dir.

# TF-201 — Module-owned route registration

## Məqsəd

TF-201 business API route-larının host `routes/api.php` faylında mərkəzləşməsi əvəzinə, sahib olduqları Projects, Tasks, Activity və Dashboard modullarında qeyd olunmasını təmin edir.

## Başlanğıc vəziyyət

Projects, Tasks, Activity və Dashboard controller-ləri module daxilində olsa da, onların API endpoint-ləri root `routes/api.php` faylında idi. Module provider-lər yalnız Web route-larını yükləyirdi və business API route-larının çoxunun adı yox idi.

## Edilən dəyişikliklər

- `Modules/Projects/routes/api.php`, `Modules/Tasks/routes/api.php`, `Modules/Activity/routes/api.php` və `Modules/Dashboard/routes/api.php` yaradıldı.
- Projects API-ləri project və membership; Tasks API-ləri task, comment və attachment; Activity API-ləri global/project/task activity; Dashboard API-ləri summary, my-tasks və overdue endpoint-ləri kimi sahib modula köçürüldü.
- Hər module provider-də `Route::prefix('api/v1')`, `api`, `auth:sanctum`, `throttle:taskflow-api` və `as('api.v1.')` bir dəfə tətbiq edilir. Module route faylı yalnız capability middleware və endpoint adını verir.
- Root `routes/api.php` yalnız host-owned `ApiTokenController` token endpoint-lərini saxlayır. Hər token və business endpoint named route oldu.

## Texniki izah

Provider route group bütün module endpoint-lərinə eyni version, authentication və rate-limit sərhədini verir. Endpoint daxilindəki `abilities:*` middleware capability-ni daraldır. `api.v1.` prefix-i ilə `projects.index` kimi local ad `api.v1.projects.index` olur; bu, fərqli modullar arasında name collision riskini azaldır.

## Acceptance Criteria

- **Duplicate method/path/name yoxdur:** route inventory və duplicate-name command-i no duplicate nəticəsi verdi.
- **Business route sahib moduldadır:** root faylda yalnız token/auth endpoint-ləri qaldı; business API-lər dörd module route faylındadır.
- **Middleware qorunur:** inventory `api`, `auth:sanctum`, `throttle:taskflow-api` və endpoint capability middleware-lərini göstərdi.

## Testlər və yoxlama

`php artisan route:list --path=api/v1 --json`

Nəticə: route-lar `/api/v1` altında, uyğun module controller-ləri və tələb olunan middleware-lərlə qeyd olunub.

`php artisan route:list --path=api/v1 --json | rg -o '"name":"[^"]+"' | sort | uniq -d`

Nəticə: output yoxdur — duplicate route name tapılmadı.

`php artisan test --compact`

Nəticə: PASS — 7 test, 39 assertion.

## Qarşılaşılan problemlər

Route migration zamanı token route-larının əvvəl name-i yox idi; root host route group-a `api.v1.` prefix və explicit token name-ləri əlavə edilməklə bu düzəldildi.

## Yekun vəziyyət

**COMPLETE**

TF-201 bütün acceptance criteria-ları route inventory və default test suite ilə verified oldu.

## Növbəti task

TF-204 dependency-ready-dir, çünki onun yeganə dependency-si TF-201 verified oldu. TF-202 hələ TF-103 final verification-u ilə bağlı explicit deferred guard vəziyyətindədir.

# TF-204 — Domain exceptions and response mapping

## Məqsəd

Gözlənilməz `LogicException` xətalarının API-də səhv olaraq domain conflict `409` kimi göstərilməsinin qarşısını almaq.

## Başlanğıc vəziyyət

`bootstrap/app.php` bütün API `LogicException` halları üçün exception mesajını JSON-a yazır və `409` qaytarırdı. Bu programming və infrastructure xətalarını documented domain conflict kimi gizlədirdi.

## Edilən dəyişikliklər

Catch-all `LogicException` renderer silindi. Artıq yalnız gələcək purpose-specific domain exception-lar explicit mapping ilə `409` qaytara bilər; gözlənilməz xəta Laravel-in normal safe error handling-i ilə `500` olur. Authorization `403`, validation `422` və authentication `401` öz middleware/request mexanizmlərində qalır.

## Testlər və yoxlama

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: exception-mapping guard PASS oldu. Digər controller-boundary guard TF-202 owner olan `Project::query()`/`User::query()` istifadələrinə görə FAIL qalır.

`php artisan test --compact`

Nəticə: PASS — 7 test, 39 assertion.

## Yekun vəziyyət

**COMPLETE**

TF-204-owned catch-all mapping aradan qaldırıldı və guard onun geri qayıtmasını aşkar edəcək. Növbəti dependency-ready production task TF-202-dir; TF-103 final verification-u TF-202-dən sonra yenidən aparılmalıdır.

# TF-202 — Repository and query boundaries

## Məqsəd

Controller-lərdə Eloquent query işlətmədən lookup-ları repository sərhədinə daşımaq.

## Edilən dəyişikliklər

`App\Repositories\UserRepository` və `EloquentUserRepository` əlavə edildi, `AppServiceProvider` binding-i qeyd edir. `ProjectRepository`-yə `findOrFail()` əlavə edildi. Project member və Task Web/API controller-ləri artıq validated ID-ni bu repository-lərə verir; controller-də `User::query()` və `Project::query()` yoxdur.

## Texniki izah

Controller HTTP adapter olaraq qalır, data-access isə repository-də yerləşir. Bu, eyni lookup davranışını saxlayır, lakin query implementation-unun controller-lərə yayılmasının qarşısını alır.

## Testlər və yoxlama

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test. TF-202 controller query guard-u və TF-204 exception guard-u keçdi.

`php artisan test --compact`

Nəticə: PASS — 7 test, 39 assertion.

## Yekun vəziyyət

**COMPLETE**

TF-202 verified oldu. TF-103 guard-ları indi PASS olduğundan production violations bağlandı; onun broader guard acceptance-ları gələcək scope-larla genişlənəcək. Növbəti dependency-ready task TF-203-dür.

# TF-203 — Purpose-specific DTO and date normalization

## Məqsəd

Generic `ProjectData`-nı create, update, filter və status use-case-ları üçün ayrıca DTO-lara bölmək və business date-ləri immutable etmək.

## Edilən dəyişikliklər

`CreateProjectData`, `UpdateProjectData`, `ProjectFiltersData` və `ChangeProjectStatusData` əlavə edildi. Create/update DTO-ları `starts_at` və `due_at` dəyərlərini `DateTimeImmutable`-ə çevirir. Web/API create/update controller-ləri dedicated DTO göndərir; Project repository pagination-u `ProjectFiltersData` qəbul edir. Status controller-ləri `ChangeProjectStatusData` ilə service `changeStatus()` method-unu çağırır. Legacy `ProjectData.php` silindi; deferred `qa` suite production test discovery-də deyil və onun sonrakı TF-101 migration-u ayrıca saxlanır.

## Texniki izah

Bir DTO yalnız bir əməliyyatın input-unun sahibi olur. Bu səbəbdən filter dəyərləri mutation data ilə qarışmır, status transition ayrıca enum DTO-dur və controller raw business dəyərini özü qurmur. Task create/update DTO-larındakı immutable `due_at` dəyişiklikləri ilə Project date normalization eyni qaydanı tətbiq edir.

## Testlər və yoxlama

`php artisan test --compact`

Nəticə: PASS — 7 test, 39 assertion.

`rg -n 'ProjectData' Modules app tests`

Nəticə: legacy generic `ProjectData` production usage-i yoxdur; yalnız yeni purpose-specific class adları görünür.

## Yekun vəziyyət

**COMPLETE**

Mutation DTO-ları validated request data-dan yaradılır, date/priority enum normalization DTO sərhədindədir və Web/API eyni service DTO type-larını istifadə edir.

# TF-205 — Authorization, visibility, and immutable-project matrix

## Məqsəd

Project membership visibility-ni assignment-dan ayırmaq, context manager səlahiyyətini global roldan asılı etməmək və Active olmayan project-lərdə mutation-ları service qatında bloklamaq.

## Edilən dəyişikliklər

ProjectPolicy-də manager/owner/admin update, archive və membership idarəetməsi üçün context qərarı tətbiq edildi; boolean bypass parameter-ləri silindi. TaskPolicy-də project member visible work-i görə və Active project-də report edə bilir, outsider isə görə və yarada bilmir. Comment və attachment service-ləri create/delete/upload əməliyyatından əvvəl project statusunu yoxlayır və Completed/Archived project-də `LogicException` atır.

`Modules/Tasks/tests/Feature/AuthorizationMatrixTest.php` role × membership × project-state matrix-i yoxlayır: member/outsider visibility və create, həmçinin Completed/Archived comment/attachment mutation denial dataset-i.

## Testlər və yoxlama

`php artisan test --compact Modules/Tasks/tests/Feature/AuthorizationMatrixTest.php`

Nəticə: PASS — 3 test, 10 assertion.

`php artisan test --compact`

Nəticə: PASS — 10 test, 49 assertion.

## Acceptance Criteria

Context manager/member capability policy-də project context-dən hesablanır. Non-member TaskPolicy tərəfindən visibility və create-dan məhrumdur. Completed/Archived comment və attachment mutations service qatında bloklanır. Token ability policy-ni bypass etmir, çünki route ability-dən sonra policy yenə tətbiq edilir.

## Yekun vəziyyət

**COMPLETE**

TF-205 verified oldu. Növbəti dependency-ready task TF-300-dür; TF-103 isə Media/Livewire/Activity-Dashboard owner taskları tamamlanana qədər deferred guard verification olaraq qalır.

# TF-300 — Daxili istifadəçi həyat dövrü

## Məqsəd

TF-300 public registration olmayan TaskFlow-da daxili hesabların təhlükəsiz idarə edilməsini tamamlayır. Admin istifadəçi yarada, qlobal rolu dəyişə, hesabı suspend/reactivate edə və parolu reset edə bilməlidir. İstifadəçi də yalnız öz cari parolunu təsdiqləməklə parolunu dəyişə bilməlidir.

## Başlanğıc vəziyyət

Əvvəl `users` cədvəlində hesabın aktiv və ya suspend olmasını göstərən vəziyyət yox idi. Admin yalnız ad, e-poçt və qlobal rolu dəyişə bilirdi; token, session və açıq task assignment-ləri ilə bağlı deprovision davranışı yox idi. Parol reset və self-service password change endpoint-ləri də mövcud deyildi. Bunun nəticəsində hesabı deaktiv etmək, köhnə token-i dərhal ləğv etmək və son administratoru qorumaq mümkün deyildi.

## Edilən dəyişikliklər

- `database/migrations/2026_09_01_090000_add_status_to_users_table.php` hər user üçün indekslənmiş `status` sütunu əlavə etdi. `AccountStatus` enum-u yalnız `active` və `suspended` dəyərlərini qəbul edir; `User` modelində cast və `isActive()` helper-i əlavə edildi.
- `AdminUserService` create/update zamanı e-poçtu `trim` və lowercase edir. Service suspend zamanı son aktiv admin invariant-ını yoxlayır, personal access token-ləri və database session-ları silir, bitməmiş task-ları unassign edir, sonra hesabı `suspended` edir. Reactivate yalnız statusu `active` edir; köhnə token və session geri qaytarılmır.
- Admin reset və self-service change üçün purpose-specific readonly DTO-lar (`ResetAdminUserPasswordData`, `ChangeOwnPasswordData`) və validated Form Request-lər əlavə edildi. Reset bütün target session/token-lərini silir. Self-change bütün token-ləri və cari session-dan başqa session-ları silir; controller cari session ID-ni regenerate edir.
- `TaskRepository`-yə `unassignOpenWorkFor()` əlavə edildi. Eloquent implementation Done/Cancelled olmayan task-larda yalnız `assignee_id` sahəsini `null` edir. Beləliklə reporter, task və Activity tarixçəsi saxlanır.
- `EnsureActiveUser` middleware-i protected host Web route-larında və mövcud module/root API route group-larında tətbiq edildi. Suspend edilmiş hesab Web üçün logout/redirect edilir, API üçün isə 403 alır. `LoginRequest` credential query-sinə `status=active` şərti əlavə edildi; suspend edilmiş user session yarada bilmir.
- Admin user edit/list səhifələrinə status, suspend/reactivate və parol reset form-ları; authenticated user üçün `/account/password` self-service form-u əlavə edildi. Heç bir Blade səhifəsi hash, token plaintext və ya password dəyərini göstərmir.
- `tests/Feature/Admin/InternalUserLifecycleTest.php` əlavə edildi. Testlər admin gate-i, email normalization-u, project role-in global roldan ayrılığını, suspension-un open task/token/session təsirini, son aktiv admin qorumasını, admin reset-in secret-safe Activity yazmasını və self-change session/token qaydasını yoxlayır.

## Texniki izah

`status` user modelinin authentication sərhədindəki faktıdır; role-dan ayrı saxlanılır. Bu səbəbdən project daxilində manager olan global `member` hesabının project rolu admin user update zamanı dəyişmir. `AdminUserService` transaction daxilində access cleanup və status update edir: token-lər Sanctum relationship-i ilə, persistent session-lar isə `sessions.user_id` ilə silinir. `TaskRepository` yalnız açıq task assignment-lərini təmizləyir; bitmiş işin tarixi assignee-si qorunur.

Watcher subscription mexanizmi bu baseline-də hələ mövcud deyil; onun pivot və notification behavior-u canonical plan-da TF-606 sahibidir. Buna görə TF-300 yeni watcher schema-sı yaratmır və suspend zamanı silinəcək persisted watcher record yoxdur. Bu, gələcək TF-606-nın scope-unu qabaqlamır.

`EnsureActiveUser` authentication-dan sonra işləyir və statusun yalnız login-də yox, artıq verilmiş token istifadə ediləndə də yoxlanmasını təmin edir. Login request e-poçtu canonical lower-case formaya çevirir və yalnız active account-a credential match etməyə icazə verir. Password DTO-ları Form Request `validated()` nəticəsindən qurulur; parol dəyəri Activity properties-ə verilmədiyinə görə audit qeydi yalnız təhlükəsiz `user_id` saxlayır.

## Acceptance Criteria

- **Yalnız admin hesab və qlobal rol idarə edir:** `manageUsers` Gate-i admin role + `users.roles.manage` permission tələb edir; feature test member üçün 403, admin üçün 200 yoxlayır.
- **Suspended user login və token istifadə edə bilmir:** login credential query-si active status tələb edir; suspension token-ləri silir, focused test köhnə bearer token üçün 401 və login üçün validation error təsdiqləyir. `active-user` middleware gələcəkdə qalmış authenticated request üçün də qoruyur.
- **Son aktiv admin demote/suspend edilmir:** service active admin sayını transaction daxilində yoxlayır; test hər iki əməliyyatda `LogicException` gözləyir.
- **Admin reset və self-change qaydaları:** admin reset target-in bütün token/session-lərini silir. Self-change request `current_password:web` validation-u tələb edir, service digər session-ları və bütün token-ləri silir, controller cari session-u regenerate edir. Test bu davranışları və yanlış current password error-unu yoxlayır.
- **Secret/hash sızmır:** `User` hidden attributes-də password/remember token qalır; Resource/view əlavə edilməyib; Activity test-i yeni parolun properties-də olmadığını yoxlayır.
- **Tarixi reference-lər saxlanır:** user delete route-u əlavə edilməyib və open task üçün yalnız assignment `null` edilir. Activity qeydində təhlükəsiz identifikator var.

## Testlər və yoxlama

`php artisan test --compact tests/Feature/Admin/InternalUserLifecycleTest.php`

Nəticə: PASS — 6 test, 25 assertion. Bu suite lifecycle-in müsbət və mənfi authorization, suspension, token/session, son-admin və password qaydalarını yoxlayır.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Yeni controller-lərdə Eloquent/DB/Storage query-si və catch-all `LogicException → 409` mapping-i yoxdur.

`php artisan test --compact`

Nəticə: PASS — 16 test, 74 assertion. Default SQLite suite bütün əvvəlki verified davranışlarla TF-300 lifecycle testlərini birlikdə işə saldı.

`php artisan route:list --path=admin/users --json`

Nəticə: admin account list/create/update/reset/suspend/reactivate route-ları `auth`, `active-user` və `can:manageUsers` middleware-ləri ilə qeyd olunub.

## Qarşılaşılan problemlər

Watcher cleanup Product Brief-də suspension tələbi olsa da, mövcud schema-da watcher pivot və subscription implementation-u yoxdur; bu explicit olaraq TF-606-nın gələcək scope-udur. TF-300 həmin future schema-nı erkən yaratmadı. Hazırkı sistemdə silinəcək watcher record olmadığından suspension-un token/session/open-assignment hissəsi tam test edildi; TF-606 implement olunanda həmin cleanup bu lifecycle service-in canonical invariant-ına əlavə olunmalıdır.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-300 acceptance criteria-ları mövcud Phase 3 başlanğıc schema-sı çərçivəsində implementasiya və executable testlərlə təsdiqləndi. Yeni migration SQLite default suite-də tətbiq edildi və reversible migration mexanizminin mövcud test coverage-i saxlandı.

## Növbəti task

`docs/IMPLEMENTATION_PLAN.md` dependency graph-ına görə TF-301 artıq dependency-ready-dir. TF-301 session authentication davranışını, throttle və protected request suspension semantics-ini ayrıca sabitləşdirəcək. TF-103 isə yalnız onun canonical owner taskları tamamlandıqdan sonra yenidən final verification-a qaytarılmalıdır.

# TF-301 — Session authentication-in sabitləşdirilməsi

## Məqsəd

TF-301 Web session authentication flow-unun təhlükəsizlik qaydalarını yoxlanılan və vahid hala gətirir: aktiv hesab login edə bilməli, suspended hesab isə nə yeni session yarada, nə də artıq mövcud Web session ilə protected səhifəyə daxil ola bilməlidir.

## Başlanğıc vəziyyət

TF-300 login credential query-sinə active status şərtini və `EnsureActiveUser` middleware-ini əlavə etmişdi. Lakin root home və module-owned Web route-ların hamısında həmin middleware eyni formada tətbiq olunmamışdı. Login throttle key-ində e-poçtun `trim` normalization-u ilə credential normalization-u tam eyni deyildi və guest presentation ayrıca Blade layout kimi ifadə edilməmişdi.

## Edilən dəyişikliklər

- Root home, Projects, Tasks, Activity və Dashboard Web route group-larına `active-user` middleware-i əlavə edildi. Beləliklə suspended user-in köhnə Web session-u hər protected endpoint-də login səhifəsinə yönləndirilir və session/CSRF token təmizlənir.
- `LoginRequest::throttleKey()` e-poçtu lower-case ilə yanaşı `trim` edir. Login credential query-si ilə rate-limit identifikatoru artıq eyni canonical e-poçt formasından istifadə edir.
- Beş uğursuz cəhddən sonra qaytarılan mesaj hesabın mövcudluğu və ya suspension statusu barədə məlumat verməyən generic `Unable to sign in. Please try again later.` mesajına dəyişdirildi. Adi yanlış credential və suspended hesab isə eyni `These credentials do not match our records.` mesajını alır.
- `resources/views/layouts/guest.blade.php` yaradıldı və login səhifəsi bu dedicated guest layout-a keçirildi. Bu layout authenticated workspace navigation-u olmadan yalnız guest auth ekranını render edir.
- `tests/Feature/Auth/SessionAuthenticationTest.php` əlavə edildi. Testlər guest redirect, registration-un olmaması, normalized email + remember login, session ID regeneration, generic credential failure, beş-cəhd throttle, stale suspended session və logout session/CSRF behavior-u əhatə edir.

## Texniki izah

Web request əvvəl `auth` middleware ilə user-i session-dan tanıyır. Ardınca `EnsureActiveUser` user modelinin `AccountStatus` dəyərini yoxlayır. Status artıq `suspended` olarsa middleware logout edir, session-u invalidate edir, yeni CSRF token yaradır və safe login redirect qaytarır. Bu yoxlama route səviyyəsində bütün hazır protected Web modullarına tətbiq olunduğu üçün təkcə login zamanı yox, user sonradan suspend ediləndə də köhnə session işlək qalmır.

`LoginRequest` yalnız `active` user üçün `Auth::attempt()` edir. Uğurlu login-dən sonra controller `session()->regenerate()` çağırır; bu session fixation riskini azaldır. Logout isə Laravel-in standard `logout`, `invalidate` və `regenerateToken` ardıcıllığını saxlayır. `remember=true` Laravel-in recaller cookie davranışına ötürülür, lakin status yoxlaması protected request-də yenə məcburidir.

## Acceptance Criteria

- **Guest/authenticated ssenariləri:** guest home request login-ə redirect olunur; active user normalized e-poçtla və remember checkbox ilə uğurla sign in edir.
- **Suspended ssenarisi:** suspended user yeni session qura bilmir və artıq authenticated session-dan sonra status dəyişərsə protected route login-ə qaytarır. Test `assertGuest()` ilə session-un ləğvini yoxlayır.
- **Throttle:** eyni normalized e-poçt + IP üçün beş failure-dən sonra düzgün credential belə generic rate-limit xətası alır.
- **Logout:** authenticated user logout-dan sonra guest olur və CSRF token əvvəlki token olaraq qalmır.
- **Session fixation:** successful login test-i yeni session ID-nin əvvəlkindən fərqli olmasını yoxlayır.
- **Registration yoxdur:** `/register` üçün 404 feature test-i public registration-un əlavə edilmədiyini qoruyur.

## Testlər və yoxlama

`php artisan test --compact tests/Feature/Auth/SessionAuthenticationTest.php`

Nəticə: PASS — 6 test, 52 assertion. Guest, login, remember, throttle, suspended stale session və logout təhlükəsizlik davranışları yoxlanıldı.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Auth dəyişiklikləri controller architecture sərhədlərini pozmadı.

`php artisan test --compact`

Nəticə: PASS — 22 test, 126 assertion. Default SQLite suite TF-300 lifecycle, TF-301 auth və əvvəlki module testlərini birlikdə keçdi.

`php artisan route:list --path=tasks --json`

Nəticə: module Web task route-larında `auth`-dan sonra `App\\Http\\Middleware\\EnsureActiveUser` görünür; API route-larında da `auth:sanctum`-dan sonra həmin status yoxlaması qalır.

## Qarşılaşılan problemlər

Əlavə external blocker olmadı. Watcher cleanup, token issuance endpoint-i və API credential auth TF-301 scope-u deyil; bunlar müvafiq olaraq TF-606 və TF-302-yə məxsusdur.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-301-in session authentication acceptance criteria-ları executable feature testləri və full SQLite suite ilə təsdiqləndi.

## Növbəti task

Canonical dependency graph-a görə TF-302 dependency-ready-dir. O, mövcud inherited `/tokens` endpoint-lərini canonical credential-to-token API ilə əvəz edəcək; TF-301 həmin API scope-unu əvvəlcədən implement etmədi.

# TF-302 — Credential-to-token API

## Məqsəd

TF-302 Web session-u olmayan API client-in e-poçt/parol ilə ilk Sanctum personal access token almasını təmin edir. Köhnə `/api/v1/tokens` endpoint-ləri artıq mövcud token tələb etdiyinə görə bootstrap problemi yaradırdı; yeni contract həmin problemi canonical `/api/v1/auth/token` endpoint-i ilə həll edir.

## Başlanğıc vəziyyət

Host `routes/api.php` yalnız authenticated `GET/POST/DELETE /api/v1/tokens` endpoint-lərini saxlayırdı. Bu endpoint-lər token yaratmaq üçün əvvəlcədən token tələb etdiyi üçün yeni API client ilk credential-to-token addımını ata bilmirdi. Current authenticated user resource-u və current-token revoke endpoint-i də contract-dakı formada yox idi.

## Edilən dəyişikliklər

- `CreatePersonalAccessTokenData` readonly DTO-su əlavə edildi. DTO normalized e-poçtu, parolu, validated `device_name` dəyərini və `ApiTokenAbility` enum-larını saxlayır; service-ə yalnız canonical string ability siyahısını verir.
- `CreatePersonalAccessTokenRequest` e-poçt, parol, 120 simvolluq device name və canonical, təkrarsız Sanctum ability array-ni validate edir. Unknown ability, boş device name və shape səhvləri Laravel 422 field-error response-u alır.
- `AuthenticationService` repository vasitəsilə user-i normalized e-poçtla tapır, `Hash::check()` və active account qaydasını yoxlayır, yalnız sonra Sanctum token yaradır. Invalid və suspended credential üçün `null` qaytarır; controller hər iki halda eyni generic 422 error verir.
- `AuthenticationController` host-owned API adapter-i kimi əlavə edildi. `issueToken()` plaintext token-i yalnız 201 response-da bir dəfə verir, `me()` `AuthenticatedUserResource` qaytarır, `destroy()` isə bearer token-dən tapılan və cari user-ə məxsus token-i silib 204 qaytarır.
- `AuthenticatedUserResource` yalnız id, name, email, global roles və cari token-in abilities sahələrini açıq siyahı ilə verir. Password, remember token, token hash və plaintext token response-a daxil edilmir.
- `taskflow-token` named rate limiter-i `AppServiceProvider`-də əlavə edildi. O, normalized e-poçt + IP üzrə dəqiqədə beş credential cəhdi məhdudlaşdırır; normal authenticated API traffic əvvəlki `taskflow-api` limiter-i ilə qalır.
- Root API route-ları contract-a keçirildi: unauthenticated `POST /api/v1/auth/token`, `auth:sanctum` + `active-user` qorunan `GET /api/v1/me` və `DELETE /api/v1/auth/token`. Köhnə `/api/v1/tokens` route/controller/request/resource faylları silindi.
- `EnsureActiveUser` API suspended actor üçün 401 qaytaracaq şəkildə düzəldildi. Bu, revoked/missing token və suspended actor üçün API contract-dakı authentication semantics-ini saxlayır.
- `tests/Feature/Auth/CredentialTokenApiTest.php` əlavə edildi. Testlər valid issue, private field absence, invalid/suspended credentials, validation, dedicated throttle, missing/revoked/suspended token, ability 403 və inherited endpoint-lərin 404 olmasını yoxlayır.

## Texniki izah

Token endpoint-i authentication tələb etmir, lakin low-rate limiter və strict Form Request validation işləyir. Controller validated array-dən DTO yaradır; DTO ability string-lərini enum-a çevirir. Service user repository və password hash yoxlamasını istifadə edir; status `suspended` olduqda heç bir token yaradılmır. `createToken()` Sanctum-un database-də yalnız token hash saxlayan mexanizmini istifadə edir, plaintext hissə isə response tamamlandıqdan sonra yenidən əldə edilə bilməz.

`GET /me` və `DELETE /auth/token` əvvəl `auth:sanctum`, sonra `active-user` middleware-dən keçir. Revoke service-i bearer token-i Sanctum `findToken()` ilə həll edir və tokenable type/id-ni cari user ilə müqayisə edir. Buna görə başqa user-in token-i və ya session-authenticated request təsadüfən başqa token-i silə bilməz. API business route-larında ability middleware policy/permission yoxlamasını əvəz etmir; focused test `projects:read` token-in `tasks:read` route-da 403 almasını təsdiqləyir.

## Acceptance Criteria

- **201:** valid active credential, device name və canonical ability array ilə token yalnız bir dəfə plaintext olaraq qaytarılır.
- **422:** invalid credential, suspended credential, missing device və unknown ability eyni safe validation shape-i ilə rədd edilir.
- **429:** altıncı eyni normalized email/IP credential request dedicated limiter tərəfindən bloklanır.
- **401:** missing, database-dən silinmiş/revoked və sonradan suspended edilmiş token `/me` endpoint-inə daxil ola bilmir.
- **403:** token-də tələb olunan Sanctum ability olmadıqda protected business route ability middleware tərəfindən rədd edilir.
- **204:** `DELETE /api/v1/auth/token` yalnız cari bearer token-i silir; ikinci token aktiv qalır.
- **Secret absence:** token database-də hash kimi saxlanır; `/me` Resource password və token sahələrini vermir; plaintext token Activity, Resource və persistent UI state-ə yazılmır.
- **Köhnə bootstrap endpoint-ləri yoxdur:** `/api/v1/tokens` GET və POST üçün 404 feature test-i ilə retired edildi.

## Testlər və yoxlama

`php artisan test --compact tests/Feature/Auth/CredentialTokenApiTest.php`

Nəticə: PASS — 7 test, 38 assertion. Token issue, validation, rate limit, revoke, suspension, ability və private-field ssenariləri yoxlanıldı.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Yeni host API controller query/business-rule architecture sərhədlərini pozmadı.

`php artisan test --compact`

Nəticə: PASS — 29 test, 164 assertion. Default SQLite suite əvvəlki verified account/session/module behavior-u ilə birlikdə keçir.

`php artisan route:list --path=api/v1 --json`

Nəticə: `api.v1.auth.token.issue`, `api.v1.auth.token.destroy` və `api.v1.me.show` named route-ları `/api/v1` altında qeyd olundu. Issue route-da `taskflow-token`, protected route-larda isə `auth:sanctum`, `active-user` və `taskflow-api` middleware-ləri görünür; köhnə `/api/v1/tokens` route-u yoxdur.

## Qarşılaşılan problemlər

İlkin full suite TF-300 lifecycle testində artıq silinmiş `/api/v1/tokens` endpoint-inin 401 gözləntisini göstərdi. Bu TF-302-nin intentional route retirement dəyişiklikləri ilə zidd idi. Test canonical `/api/v1/me` endpoint-ə dəyişdirildi; suspension-dan sonra revoked token üçün 401 yenə real şəkildə yoxlanılır. Sonrakı full suite PASS oldu.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-302 credential-to-token API contract-ı və bütün documented status/security acceptance criteria-ları executable feature testləri ilə təsdiqləndi.

## Növbəti task

Canonical dependency graph-a görə TF-303 dependency-ready-dir. O, account/token audit və daha geniş security dataset-lərini tamamlayacaq. TF-302 həmin audit scope-unu qabaqlamır.

# TF-303 — Auth/admin audit və security pass

## Məqsəd

TF-303 hesab və token əməliyyatlarının audit izini təhlükəsiz edir, secret sızmasının qarşısını alan vahid sanitization qaydası yaradır və auth/admin təhlükəsizlik davranışlarını role, ability və account-state dataset-ləri ilə bağlayır.

## Başlanğıc vəziyyət

TF-300 user suspension, reactivate və password əməliyyatları üçün ayrı-ayrı `activity()` çağırışları edirdi; TF-302 isə token issue/revoke üçün Activity yazmırdı. Mövcud çağırışlarda parol verilməsə də, recursive sensitive-key denylist yox idi. Bundan əlavə `status` User modelinin fillable siyahısında idi; bu, gələcək mass-assignment entry point-də account state-in client tərəfindən dəyişdirilməsi riski yaradırdı. Son aktiv admin hesabı üçün check transaction daxilində olsa da, admin sətirlərinin database lock-u yox idi.

## Edilən dəyişikliklər

- `app/Services/SecurityAuditService.php` əlavə edildi. Service account/token event-lərini `causedBy`, `performedOn`, stable event name və sanitized properties ilə Activity-yə yazır.
- Sanitizer `password`, `token`, `secret`, `authorization`, `cookie`, `hash` və `credential` sözlərini ehtiva edən property key-lərini, nested array-lər daxil olmaqla, recursively silir. Buna görə token plaintext-i, token hash-i və password/authorization dəyərləri audit payload-a keçə bilməz.
- `AdminUserService` create, update, suspend, reactivate, admin password reset və self password change əməliyyatlarını `SecurityAuditService`-ə keçirdi. Create/update event-ləri yalnız user ID, role və status kimi təhlükəsiz metadata saxlayır.
- `AuthenticationService` successful `api_token.issued` və current-token `api_token.revoked` event-ləri yazır. Issue event-də device name və ability siyahısı audit edilir, plaintext token və database hash isə heç vaxt property-yə verilməz.
- `User` modelinin fillable siyahısından `status` çıxarıldı. Admin service statusu yalnız trusted domain logic-də `forceFill()` ilə təyin edir; HTTP input user statusunu mass assign edə bilmir.
- Son aktiv administratorun demote/suspend yoxlamalarına `lockForUpdate()` əlavə edildi. Bu lock transaction içində active admin setini serialize etməyə kömək edir və concurrent request-lərin ikisinin də son admin-i deaktiv etməsinin qarşısını alır.
- `tests/Feature/Auth/AuthAdminSecurityAuditTest.php` əlavə edildi. O, event traceability/secret absence, recursive sanitizer, mass assignment/hidden fields, sequential stale-instance final-admin guard və bütün global role × account status credential matrix-ini yoxlayır.

## Texniki izah

`SecurityAuditService` raw request və ya model dump qəbul etmir; caller explicit olaraq business üçün faydalı kiçik metadata verir. `sanitize()` hər key-i lower-case müqayisə edir və sensitive hissə tapdıqda həmin sahəni nəticəyə əlavə etmir. Nested array yenidən eyni funksiya ilə emal edilir. Bu yanaşma yeni account/token event-i əlavə ediləndə developer-in təsadüfən `password`, bearer token və ya hash property-si verməsinə qarşı ikinci müdafiə qatıdır.

Admin demotion və suspension `DB::transaction()` daxilində işləyir. `lockForUpdate()` active admin sətirlərini lock edir, sonra count yoxlanılır. Nəticədə iki request eyni anda son iki admin-i demote etməyə çalışsa, ikinci request birincinin dəyişmiş nəticəsini görür və son active admin invariant-ını pozmur. `status` fillable olmadığından bu invariant yalnız service qərarı ilə dəyişir.

Token auditində user token-i yaratdıqdan sonra yalnız device və canonical abilities saxlanır. Revoke event token identifier/hash qeyd etmədən user ID ilə yazılır. API Resource explicit field list istifadə etdiyindən password, remember token və token plaintext API response-a serializasiya olunmur.

## Acceptance Criteria

- **Account/token actions traceable-dir:** user create/update/suspend/reactivate/password reset/password change və token issue/revoke üçün stable Activity event-ləri yazılır.
- **Secrets audit-də yoxdur:** recursive sanitizer sensitive key-ləri silir; feature test temporary password, plaintext token və token hash-in Activity properties-də olmadığını yoxlayır.
- **Rate-limit və safe error behavior:** TF-301/TF-302 auth feature suite normalized login/token limiter və generic invalid credential response-larını yenidən işə salaraq qoruyur.
- **Mass assignment və hidden fields:** `status` fillable deyil; test client-provided suspended statusun database default `active` olaraq qalmasını və user serialization-da password/remember token olmamasını təsdiqləyir.
- **Final-admin concurrency qorunur:** active admin query-si lock alır; stale second instance ilə sequential concurrency simulation ikinci demotion-u `LogicException` ilə bloklayır.
- **Role × ability × account-state:** admin, project manager və member üçün active credential issue 201, suspended credential issue 422 dataset-i ilə yoxlanır. TF-302 ability test-i uyğun ability olmayan token üçün 403, revoked/suspended token üçün 401 behavior-u saxlayır.
- **Critical/High auth-admin finding yoxdur:** canonical account/token entry point-ləri Form Request, trusted DTO/service, sanitized audit, status guard, rate limit və focused security test coverage ilə qorunur.

## Testlər və yoxlama

`php artisan test --compact tests/Feature/Auth/AuthAdminSecurityAuditTest.php`

Nəticə: PASS — 10 test, 20 assertion. Audit payload, secret absence, mass assignment, hidden fields, final-admin guard və six-case role/status dataset-i yoxlandı.

`php artisan test --compact tests/Feature/Auth`

Nəticə: PASS — 23 test, 110 assertion. Session authentication, credential token API və yeni audit/security testləri birlikdə keçdi.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Security əlavələri controller architecture sərhədlərini pozmadı.

`php artisan test --compact`

Nəticə: PASS — 39 test, 184 assertion. Default SQLite suite bütün verified Phase 0–3 behavior-larını birlikdə keçdi.

## Qarşılaşılan problemlər

İlk focused audit run-da `AdminUserService` transaction closure-u yeni optional `$actor` parametrini capture etməmişdi və admin create/update zamanı `Undefined variable $actor` 500 xətası verdi. Closure `use` siyahısına `$actor` əlavə edildi, sonra focused və full suite yenidən PASS oldu. Bu task-owned failure idi və documentation-dan əvvəl həll edildi.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-303 account/token audit və security acceptance criteria-ları executable dataset, feature və full SQLite verification ilə təsdiqləndi.

## Növbəti task

Canonical dependency graph-da TF-400 bütün dependency-ləri verified olan ən erkən pending taskdır. TF-400 project key/lifecycle schema və invariant-larını implement edəcək. TF-103 isə media, Livewire, route/module və Activity owner taskları tamamlanana qədər deferred architecture verification siyahısında qalır.

# TF-400 — Project key, lifecycle və issue sequence schema

## Məqsəd

TF-400 hər project üçün insanın oxuya bildiyi immutable key (`PAY` kimi), project-local issue nömrəsi (`PAY-42` kimi) və Product Brief-dəki Draft/Active/Completed/Archived lifecycle qaydalarını database və service qatında tətbiq edir.

## Başlanğıc vəziyyət

Əvvəl Project modelində yalnız slug var idi; API/UI və TaskService global `TSK-000001` formatında nömrə yaradırdı. Project-də növbəti issue sequence saxlanmırdı. Lifecycle yalnız Draft → Active və archive-un bir hissəsini dəstəkləyirdi; Completed → Active reopen yox idi və Completed project-də project detail/member mutation-ları service səviyyəsində eyni read-only qaydaya tabe deyildi.

## Edilən dəyişikliklər

- `Modules/Projects/database/migrations/2026_09_01_100000_add_project_keys_and_issue_sequence.php` `projects` cədvəlinə 10 simvolluq unique, non-null `key` və default `1` olan `next_issue_number` sütunlarını əlavə etdi. Mövcud project-lər `id` sırası ilə oxunur, adları uppercase alphanumeric key-ə çevrilir və collision olduqda 10 simvol limitini qoruyan deterministik rəqəm suffix-i əlavə edilir. Migration sonunda unique və sequence index-i qurulur; `down()` hər iki index/sütunu geri silir.
- `Project` modelinə `key` fillable sahəsi, Project factory-yə deterministic test key və initial sequence əlavə edildi.
- Create/update DTO-ları və Form Request-lər project key-i qəbul edir. Create key-i mütləq 2–10 simvolluq uppercase letter/digit formatında və unique olmalıdır. Update key-i optional-dır, ancaq təqdim edilərsə eyni format/unique qaydası işləyir.
- `ProjectService` create zamanı key-i saxlayır. Update zamanı key dəyişmək istənirsə repository Task cədvəlində allocation olub-olmadığını yoxlayır; ilk issue-dən sonra key change `LogicException` ilə bloklanır.
- `ProjectRepository`-yə `lockForUpdate()` və `hasAllocatedIssues()` əlavə edildi. `ProjectService::allocateIssueNumber()` locked project sətrindən cari sequence-i alır, `KEY-N` nömrəsini qaytarır və `next_issue_number`-i transaction daxilində artırır.
- `TaskService` artıq global `TSK-*` nömrəsi yaratmır. Task persist edilməzdən əvvəl ProjectService-dən project-local number alır. Transaction rollback olarsa Task və sequence update eyni transaction-un içində geri qaytarılır.
- Lifecycle `changeStatus()` transition table-i ilə yenidən yazıldı: Draft → Active/Archived, Active → Completed/Archived, Completed → Active/Archived, Archived → heç nə. Status dəyişikliyi locked project sətrində işləyir və safe `old_status`/`new_status` Activity payload-u yazır.
- Completed və Archived project-lər ProjectService update və ProjectMemberService mutation-larında read-only oldu. Web UI key-i göstərir, Active project üçün complete action, Completed project üçün reopen action təqdim edir. API üçün canonical `PATCH /api/v1/projects/{project}/status` named route-i əlavə edildi.
- `ProjectResource` key sahəsini açıq siyahı ilə qaytarır; `ActivityDisplay` yeni `project.status_changed` event-i üçün human-readable label/summary göstərir.
- `Modules/Projects/tests/Feature/ProjectLifecycleAndKeyTest.php` validation/uniqueness, lifecycle table, local numbering, key freeze, service mutability və safe Activity behavior-u yoxlayır.

## Texniki izah

Project key slug-dan fərqlidir: slug UI URL üçün, key isə issue identity üçün istifadə olunur. `next_issue_number` project sətrində saxlandığı üçün hər project öz sequence-inə malikdir. `lockForUpdate()` paralel task creation zamanı eyni sequence dəyərinin iki dəfə oxunmasının qarşısını alır. Məsələn, `PAY` project-i iki task yaradanda nəticə `PAY-1`, sonra `PAY-2` olur və sequence `3` kimi saxlanır.

Lifecycle service controller-dən gələn statusu birbaşa qəbul etmir; yalnız `ChangeProjectStatusData` enum-u və explicit transition table işləyir. Bu table archived project-in terminal olmasını və completed project-in yalnız authorized manager/admin tərəfindən Active-ə reopen edilməsini qoruyur. Detail və member mutation-ları isə Completed/Archived statusunda service qatında rədd olunur; gizlədilmiş UI düyməsi bu invariant-ı əvəz etmir.

Project update Activity-si artıq sadəcə field adlarını yox, approved sahələrin `old` və `new` dəyərlərini saxlayır. Status event-i ayrıca old/new enum dəyərləri ilə yazılır. Project metadata password, token, storage path və ya başqa secret daşımadığından payload audit üçün faydalı, lakin təhlükəsiz qalır.

## Acceptance Criteria

- **Key validation və uniqueness:** Form Request uppercase formatı, unique rule və database unique index-i tətbiq edir. Focused test valid normalized `PAY`, duplicate `PAY` və invalid `1BAD` ssenarilərini yoxlayır.
- **Key immutability:** Project-də ilk Task yaratdıqdan sonra `hasAllocatedIssues()` true olur və ProjectService key update-i rədd edir. Test `PAY-1`/`PAY-2` allocation-dan sonra `NEW` key cəhdinin failure olduğunu təsdiqləyir.
- **Lifecycle table:** Focused test invalid Draft → Completed və Archived → Active transition-larını rədd edir; Draft → Active → Completed → Active → Archived tam allowed yolu keçir.
- **Completed/Archived read-only:** Project update və member add direct service testləri hər iki status üçün `LogicException` alır. Bu service-layer protection Web/API entry point-dən asılı deyil.
- **Safe activity:** status event-i `old_status` və `new_status`, update event-i approved old/new field-ləri yazır; secret sahə yoxdur.
- **Backfill/reversibility:** migration mövcud project-lər üçün ordered, collision-safe key backfill edir və `down()` index/sütunları geri silir. SQLite rollback test-i bütün migration chain-in reversible olduğunu PASS nəticəsi ilə təsdiqlədi.

## Testlər və yoxlama

`php artisan test --compact Modules/Projects/tests/Feature/ProjectLifecycleAndKeyTest.php`

Nəticə: PASS — 5 test, 19 assertion. Key validation, lifecycle, sequence, immutable key və read-only service matrix yoxlandı.

`php artisan test --compact tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 1 test, 7 assertion. Yeni key/sequence migration-u daxil olmaqla SQLite migration rollback chain-i keçdi.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Status controller route adapter olaraq qaldı və architecture guard pozulmadı.

`php artisan test --compact`

Nəticə: PASS — 44 test, 203 assertion. Default SQLite suite previous verified auth/module behavior-ları ilə TF-400 testlərini birlikdə keçdi.

`php artisan route:list --path=projects --json`

Nəticə: project Web/API inventory-də named `projects.complete` və `api.v1.projects.status` status route-ları, həmçinin expected auth/ability/active-user middleware-ləri göründü.

## Qarşılaşılan problemlər

Migration-a ilk dəfə console report üçün Laravel Migration obyektində olmayan `$command` property-si əlavə edilmişdi. SQLite test bootstrap bu property-ni vermədiyi üçün focused suite-də `Undefined property` xətası yarandı. Reporting property-si silindi; migration-in deterministik backfill davranışı database update logic-də qalır və migration rollback/focused suite yenidən PASS oldu.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-400 key, sequence, lifecycle, immutability və reversible migration acceptance criteria-ları executable testlərlə təsdiqləndi.

## Növbəti task

Canonical dependency graph-a görə TF-401 dependency-ready-dir. O, project membership role update/removal integrity, owner protection və open-assignment conflict behavior-unun sahibidir. TF-400 həmin membership scope-u əvvəlcədən implement etmədi.
