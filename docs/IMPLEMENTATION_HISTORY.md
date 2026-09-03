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

# TF-401 — Membership roles və removal integrity

## Məqsəd

TF-401 project üzvünün rolunu dəyişdirmək və üzvlüyü silmək üçün Web/API use case-lərini Product Brief qaydalarına uyğun, təhlükəsiz və atomic etmək üçündür. Xüsusilə project owner həmişə `manager` qalmalı, açıq işi olan üzv silinməməli və uğurlu üzvlük dəyişiklikləri audit tarixçəsi ilə birlikdə saxlanmalıdır.

## Başlanğıc vəziyyət

`ProjectMemberService` yalnız add və remove əməliyyatlarını dəstəkləyirdi; rol yeniləmə use case-i, `PATCH` route-u və bu əməliyyat üçün DTO yox idi. Add/remove Activity yazsa da, service öz transaction sərhədini açmırdı. Remove açıq task assignment-larını yoxlamırdı; buna görə manager səhvən üzvü project-dən silib onun open work-larını assignment tarixçəsi ilə zidd vəziyyətə sala bilərdi. Available-user query-si də suspended account-ları seçimə qaytarırdı.

## Edilən dəyişikliklər

- `Modules/Projects/app/Data/UpdateProjectMemberData.php` readonly DTO-su əlavə edildi. O, validated `ProjectMemberRole` enum dəyərini service-ə ötürür; controller business data-nı özü formalaşdırmır.
- `UpdateProjectMemberRequest`, Web/API `ProjectMemberController::update()` metodları və `PATCH /projects/{project}/members/{user}` route-ları əlavə edildi. API route `api.v1.projects.members.update` adı, `projects:write` ability-si və module provider-in `api`/`auth:sanctum`/`active-user`/`throttle:taskflow-api` middleware zənciri ilə qeyd olunur.
- `ProjectMemberRepository` və Eloquent implementasiyasına `find`, `updateRole` və `openAssignmentCount` əlavə edildi. Assignment count Tasks cədvəlində həmin project+user üçün `done` və `cancelled` olmayan task-ları sayır; Eloquent query controller-ə keçmir.
- `ProjectMemberService` add, role update və remove use case-lərini `DB::transaction()` daxilinə aldı. Membership mutation və `ActivityRecorder` eyni database transaction-da olduğuna görə Activity yazılışı failure olarsa membership də commit olunmur.
- Owner remove edilmir; owner üçün yalnız mövcud `manager` rolu qorunur, `member`-ə demotion service qatında rədd edilir. Mövcud olmayan member üçün də safe domain failure qaytarılır.
- `MemberHasOpenAssignments` purpose-specific domain exception-u əlavə edildi. Remove zamanı say sıfırdan böyükdürsə əməliyyat transaction-dan çıxır, membership/activity dəyişmir. API exception renderer-i yalnız bu sənədləşdirilmiş conflict üçün `409`, stable `member_has_open_assignments` code-u, `user_id` error-u və `open_assignment_count` meta sahəsini qaytarır; TF-204-də silinmiş catch-all `LogicException → 409` davranışı geri gətirilmədi. Web controller eyni exception-u form error-a çevirir.
- `availableUsers()` yalnız `active` statuslu, hələ həmin project-də membership-i olmayan user-ləri qaytarır. Service də suspended user-in birbaşa çağırışla membership-ə əlavə edilməsini rədd edir.
- Members Blade səhifəsinə owner olmayan üzv üçün role select və PATCH form əlavə edildi; remove form-u ayrıca qalır. Beləliklə Web və API eyni `ProjectMemberService` use case-lərindən istifadə edir.
- `Modules/Projects/tests/Feature/ProjectMemberIntegrityTest.php` əlavə edildi. Testlər duplicate, owner demotion/removal, context manager, outsider, API conflict count, atomic remove/history, Completed/Archived mutation və available-user scope ssenarilərini əhatə edir.

## Texniki izah

Project role global Spatie role-dan ayrıdır. Məsələn, global `member` olan user project-də `manager` ola bilər. Policy `manageMembers` həmin project context-də owner/manager/admin qərarını verir, controller isə yalnız bunu authorize edir. Sonra DTO və service real mutation-u edir. Bu bölünmə controller-in transaction, Eloquent query və membership qaydaları daşımasının qarşısını alır.

`openAssignmentCount()` open task-ları repository-də hesablayır. Count varsa `MemberHasOpenAssignments` atılır və `DB::transaction()` heç bir delete və `project.member_removed` Activity event-i commit etmədən rollback edir. Count yoxdursa membership silinir, sonra eyni transaction-da tarixçə yazılır. Product Brief-dəki watcher cleanup qaydası TF-606-da yaranacaq `task_watchers` pivot-u ilə fiziki olaraq implement olunacaq; bu TF-401 schema-sında watcher persistence hələ mövcud deyil. Buna görə hazırkı successful remove operation-da silinəcək watcher sətri yoxdur; gələcək TF-606 həmin mutation transaction-na faktiki cleanup-u əlavə etməlidir, historical activity isə indidən qorunur.

Completed və Archived project yoxlaması HTTP düyməsinə güvənmir: `ensureMutable()` hər üç mutation use case-inin service daxilində işləyir. Beləliklə Web/API route gizlədilsə belə birbaşa service çağırışı project-i dəyişə bilmir.

## Acceptance Criteria

- **Duplicate, owner və role halları:** focused test duplicate membership-i, owner demotion/removal qadağasını və context manager-in non-owner role update əməliyyatını yoxlayır.
- **Open assignment conflict:** API test bir açıq assignment üçün 409, stable code, actionable `user_id` mesajı və `open_assignment_count: 1` qaytarıldığını; membership və remove Activity-nin dəyişmədiyini yoxlayır.
- **Completed/Archived və outsider:** iki-status dataset-i add/update/remove service mutation-larının hər birini rədd edir; Web outsider PATCH request-i 403 alır.
- **Context manager permission:** global member olan project manager Web PATCH ilə başqa non-owner member-in rolunu dəyişir. Bu project context rolunun controller policy ilə düzgün tanındığını təsdiqləyir.
- **Scoped users:** available-user test-i owner, mövcud member və suspended account-u gizlədir, yalnız active non-member-i qaytarır; suspended user direct service çağırışında da rədd edilir.
- **Partial failure yoxdur:** açıq assignment conflict test-i rollback nəticəsində membership və Activity sayının əvvəlki kimi qaldığını yoxlayır. Successful remove test-i membership-in silindiyini və tək `project.member_removed` history event-inin yazıldığını təsdiqləyir.

## Testlər və yoxlama

`php artisan test --compact Modules/Projects/tests/Feature/ProjectMemberIntegrityTest.php`

Nəticə: PASS — 7 test, 27 assertion. TF-401 authorization, conflict, read-only state və scoped-query matrix-i yoxlandı.

`php artisan test --compact Modules/Projects/tests`

Nəticə: PASS — 12 test, 46 assertion. TF-400 lifecycle/key testləri ilə yeni membership integrity suite-i birlikdə keçdi.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Yeni controller-lər DTO/service adapter olaraq qaldı; forbidden controller query və catch-all exception mapping yoxdur.

`php artisan route:list --path=api/v1/projects --json | rg 'projects/.+members|projects.members'`

Nəticə: member `GET`, `POST`, `PATCH`, `DELETE` endpoint-ləri `/api/v1` altında module controller, expected Sanctum ability və provider middleware-ləri ilə qeyd olundu.

`php artisan route:list --path=api/v1 --json | rg -o '"name":"[^"]+"' | sort | uniq -d`

Nəticə: boş output — duplicate API route name yoxdur.

`php artisan test --compact`

Nəticə: PASS — 51 test, 230 assertion. Default SQLite suite əvvəlki verified behavior-la birlikdə tam keçdi.

## Qarşılaşılan problemlər

İlk focused test run-da enum cast edilmiş `member_role` dəyəri string ilə müqayisə edilirdi və ProjectMember factory-də mövcud olmayan `member()` state-i çağırılırdı. Bunlar production failure deyil, test expectation/factory istifadəsi idi: assertion enum instance ilə müqayisəyə keçirildi, factory explicit `member_role` state ilə yaradıldı. Sonrakı focused, Projects və full run-lar PASS oldu.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-401 role update, owner invariant, assignment conflict, transactional Activity, Web/API flow və active-user scope acceptance criteria-ları executable SQLite testləri ilə təsdiqləndi. Watcher pivot-u hələ TF-606 owner-dir; mövcud schema-da cleanup ediləcək watcher record-u yoxdur.

## Növbəti task

Canonical dependency graph-a görə TF-402 dependency-ready-dir. O, bu service-ləri tam project Web/API presentation, Resources və manual-flow checklist-i ilə birləşdirəcək. TF-401 həmin presentation scope-unu əvvəlcədən genişləndirmədi.

# TF-402 — Projects Web/API presentation

## Məqsəd

TF-402 Projects modulunun artıq mövcud create, update, lifecycle və membership service-lərini tam Web/API presentation flow-na bağlayır. Məqsəd user-in yalnız görə bildiyi project-ləri axtara bilməsi, detail səhifəsində faydalı summary görməsi və API client-in documented Resource envelope/status contract-ını almasıdır.

## Başlanğıc vəziyyət

Project list Web-də `q` və status query parametrlərini typed Form Request olmadan qəbul edirdi. Repository project key üzrə axtarmır, list/detail query-ləri member/task count-larını yükləmirdi. API Resource summary count-ları vermirdi, member Resource-da e-poçt yox idi və köhnə `/activate`/`/archive` API alias-ları canonical `PATCH /projects/{project}/status` endpoint-i ilə paralel qalırdı. Buna görə API contract-da lazımsız iki status yolu mövcud idi.

## Edilən dəyişikliklər

- `Modules/Projects/app/Http/Requests/ProjectIndexRequest.php` əlavə edildi. Web list `q` (maksimum 120 simvol) və enum əsaslı `status` filter-lərini controller-dən əvvəl validate edir.
- `Project` modelinə `tasks()` relation-u, `ProjectRepository`-yə `detailFor()` metodu və Eloquent repository-yə vahid actor-visibility scope əlavə edildi. List/detail query-ləri owner-i eager load edir, `memberships_count` və `tasks_count` hesablayır, admin üçün hamını, digər user üçün yalnız owner/member olduğu project-ləri seçir. Search name/description ilə yanaşı immutable project `key`-ini də əhatə edir.
- Web `ProjectController` list üçün validated request, detail üçün repository-nin scoped/eager-loaded presentation modelini istifadə edir. Blade artıq count və relation üçün query etmir.
- `ProjectResource` `member_count` və `task_count` sahələrini yalnız repository tərəfindən yüklənmiş count-lardan qaytarır. `ProjectMemberResource` nested, authorized member identity üçün `email` sahəsini explicit siyahıya əlavə edir.
- API show və member index endpoint-ləri record-u əvvəl repository visibility scope-dan keçirir. Outsider üçün təhlükəsiz 404 alınır; ability olmayan token isə middleware-dən 403 alır. API create/update/status cavabları eyni scoped `ProjectResource` ilə count-ları qaytarır.
- Canonical status endpoint yeganə API lifecycle yolu kimi saxlanıldı: legacy `POST /projects/{project}/activate` və `POST /projects/{project}/archive` route-ları və istifadə olunmayan controller action-u silindi. Web lifecycle form-ları dəyişmədən eyni `ProjectService::changeStatus()` use case-indən istifadə edir.
- Project list-in desktop cədvəl və mobile kart görünüşünə project key, member/task summary əlavə edildi. Detail səhifəsinin summary kartlarına task sayı daxil edildi; mövcud lifecycle action-ları və authorized latest Activity bölməsi qorundu.
- `Modules/Projects/tests/Feature/ProjectPresentationTest.php` əlavə edildi. Testlər Web list/detail/empty-scoped behavior, filter/input validation, Resource counts, 201/200/204/403/404/422 API outcome-ları, context manager və member endpoint-lərini yoxlayır.
- `docs/MANUAL_BROWSER_CHECKLIST.md` final TF-1003 gate üçün responsive list/detail, form error və API contract maddələri ilə genişləndirildi. Checklist icra edilmiş manual test kimi işarələnməyib.

## Texniki izah

`EloquentProjectRepository::visibleTo()` list və detail üçün eyni visibility qaydasını istifadə edir. Bu o deməkdir ki, actor Web list-də görmədiyi project-i API detail və nested member list endpoint-i ilə də tapa bilmir. Repository `withCount()` ilə database-də count-ları hesablayır; Blade və Resource yalnız hazır presentation data-nı oxuyur. Bu yanaşma N+1 query riskini və Blade daxilində data-access qaydasının pozulmasını aradan qaldırır.

Controller-lər yenə adapter olaraq qalır: Form Request-dən validated data alır, policy/ability yoxlamasını edir, repository/service çağırır və View/Resource qaytarır. Web və API create/update/status/membership mutation-ları ayrı business logic yazmır; əvvəldən mövcud `ProjectService` və `ProjectMemberService` istifadə olunur. `ProjectResource` raw model serializasiyası deyil, explicit field siyahısıdır; storage path, token və qeyri-müəyyən model sahələri response-a keçmir.

## Acceptance Criteria

- **Eyni service use case-ləri:** Web və API controller-ləri project create/update/status üçün `ProjectService`, member mutation-ları üçün `ProjectMemberService` çağırır. Focused API test-i create, update, status və member POST/PATCH/DELETE flow-larını real route vasitəsilə yoxlayır.
- **Exact API statuses/envelopes/policy:** feature test project/member read üçün 200, create/member add üçün 201, member remove üçün 204, validation üçün 422, ability denial üçün 403 və scoped outsider üçün 404 response-larını yoxlayır. Resource envelope-da key, count və allowed nested user sahələri təsdiqlənir.
- **Validated/scoped presentation:** Web filter request enum validation edir; list search key-i də tapır. Owner list/detail-də summary görür, outsider list-də project adı görünmür və API direct lookup 404 alır.
- **Key, lifecycle, member/task summary və activity/actions:** Web list/detail key, status və member/task count göstərir. Detail mövcud authorized Activity və lifecycle/member actions-ını policy ilə render edir; read-only state servisdə TF-400/TF-401 tərəfindən qorunur.
- **Form/component və query-free Blade:** create/edit eyni `_form.blade.php` partial-ını istifadə edir; list/detail relation/count-ları repository eager-load nəticəsindən oxuyur. Blade query əlavə edilmədi.
- **Manual checklist:** responsive/mobile list, detail summary, validation və API contract maddələri `MANUAL_BROWSER_CHECKLIST.md`-də final browser gate üçün hazırdır; TF-1003-dən əvvəl manual PASS iddiası edilmir.

## Testlər və yoxlama

`php artisan test --compact Modules/Projects/tests/Feature/ProjectPresentationTest.php`

Nəticə: PASS — 6 test, 48 assertion. Web scoped list/detail, validation, API Resource/status contract, outsider/ability davranışı, member endpoint-ləri və obsolete route-lar yoxlandı.

`php artisan test --compact Modules/Projects/tests`

Nəticə: PASS — 18 test, 94 assertion. TF-400 lifecycle/key, TF-401 membership integrity və TF-402 presentation testləri birlikdə keçdi.

`php artisan test --compact tests/Architecture/ControllerBoundaryGuardTest.php`

Nəticə: PASS — 2 test, 2 assertion. Yeni request/repository presentation flow-u controller query və catch-all exception guard-larını pozmadı.

`php artisan route:list --path=api/v1/projects --json | rg -o '"name":"[^"]+"' | sort | uniq -d`

Nəticə: boş output — `/api/v1/projects` route family-də duplicate route name yoxdur.

`php artisan route:list --path=api/v1/projects --json`

Nəticə: canonical GET/POST/PUT|PATCH project, PATCH status və GET/POST/PATCH/DELETE member route-ları module controller, `auth:sanctum`, `active-user`, `throttle:taskflow-api` və uyğun `projects:read`/`projects:write` ability middleware-ləri ilə qeyd olundu. Legacy activate/archive API route-ları yoxdur.

`php artisan test --compact`

Nəticə: PASS — 57 test, 278 assertion. Default SQLite suite failure olmadan keçdi.

## Qarşılaşılan problemlər

İlk focused presentation run-da key ilə filter tətbiq ediləndə repository yalnız name/description axtardığı üçün list boş qaytdı və Resource count assertion-u `null` oldu. Problem testdə deyil, presentation repository-də idi: canonical key istifadəçi üçün project identity olduğuna görə search predicate-ə `key` də əlavə edildi. Düzəlişdən sonra focused suite və bütün Projects/full suite PASS oldu.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-402 validated/scoped Web/API project presentation, target Resources, canonical route contract və final-gate manual checklist acceptance criteria-larını executable SQLite verification ilə qarşıladı.

## Növbəti task

Canonical dependency graph-a görə TF-500 ən erkən dependency-ready pending taskdır. O, Media modulunun scaffold, private metadata schema və repository/service sərhədlərini yaradacaq. TF-402 Media və ya future task behavior-u implement etmədi.

# TF-500 — Media modulunun scaffold və qeydiyyatı

## Məqsəd

TF-500 bütün private file metadata-sının gələcəkdə vahid `Media` modulu tərəfindən idarə olunması üçün minimal, işlək təməl yaradır. Bu task physical file upload, MIME yoxlaması, download/preview və Task association behavior-u yazmır; həmin təhlükəsizlik və migration addımları ardıcıl olaraq TF-501–TF-503-ə məxsusdur.

## Başlanğıc vəziyyət

Layihədə yalnız Tasks moduluna aid inherited `task_attachments` cədvəli və `TaskAttachmentService` vardı. Həmin struktur disk/path metadata-sını Task daxilində saxlayırdı və central `Media` module/provider, `media` cədvəli, model, repository və safe Resource mövcud deyildi. `modules_statuses.json` Media modulunu tanımır, PHPUnit/Pest isə Media module test qovluğunu discover etmirdi.

## Edilən dəyişikliklər

- `Modules/Media/module.json`, `composer.json` və `MediaServiceProvider` əlavə edildi; `modules_statuses.json`-da `Media: true` qeyd olundu. Provider `MediaRepository` interface-ni `EloquentMediaRepository`-yə bind edir, module migration-larını yükləyir və gələcək endpoint-lər üçün boş owner route fayllarını təhlükəsiz qeyd edir.
- `2026_09_02_100000_create_media_table.php` migration-u central `media` cədvəlini yaratdı: public-safe unique `uuid`, uploader foreign key, internal `disk`/unique `path`, original name, extension, server-side MIME üçün metadata sahələri, size, SHA-256, optional image dimensions, timestamps və soft delete. UUID/path unique constraint-ləri və uploader/date, MIME index-ləri əlavə edildi; `down()` cədvəli reversible şəkildə silir.
- `Media` modelində `uploader()` relation-u, type cast-lar və trusted metadata persistence üçün fillable sahələr yaradıldı. `MediaRepository` yalnız metadata save, UUID lookup və soft delete əməliyyatlarını owns edir.
- `MediaMetadataData` readonly DTO-su trusted gələcək storage pipeline-ın istehsal etdiyi metadata-nı immutable formada daşıyır. `MediaMetadataService::register()` həmin DTO-dan transaction daxilində Media record yaradır; o Task modelini, Task policy-ni və ya user-in Task-a çıxış qərarını bilmir.
- `MediaResource` explicit safe response sahələrini qaytarır: id, uuid, display metadata, ölçü, dimensions və created time. `disk`, `path` və `sha256` qəsdən heç vaxt serializasiya edilmir.
- `MediaFactory` və `MediaModuleTest` əlavə edildi. `tests/Pest.php` və `phpunit.xml` Media module Feature/Unit qovluqlarını default test discovery-yə daxil edir.
- Composer autoload map-i yeni module composer metadata-sını tanısın deyə `composer dump-autoload --no-scripts` işlədildi. Dependency install/update/remove edilmədi.

## Texniki izah

`media` cədvəli binary-nin özünü yox, onun sahiblik və təhlükəsizlik üçün lazım olan metadata-sını saxlayır. `uuid` gələcək API/URL üçün internal integer ID-dən daha təhlükəsiz public identifier rolunu oynayır. `disk`, `path` və checksum persistence üçün vacibdir, lakin client-ə açılarsa private storage location və file correlation məlumatını sızdıra bilər; buna görə onlar modeldə olsa da Resource-da yoxdur.

Media modulunun service-i yalnız trusted metadata record-u yaradır. Task association və authorization onun daxilinə salınmayıb: Architecture-a görə Tasks parent Task-ı authorize edir, association-u yoxlayır, sonra Media-dan storage/stream building block istəyir. Bu scaffold-də `Modules\\Tasks`, `TaskPolicy` və `authorize()` istifadə edilmədiyini yoxlayan test həmin sərhədi qoruyur. Storage path randomization, MIME/extension pairing, file limitləri, physical cleanup, streaming və compensation hələ TF-501 scope-udur; thumbnail, scanner və queue abstraction əlavə edilmədi.

## Acceptance Criteria

- **Module müstəqil boot edir:** focused test `media` cədvəlinin migration-la yaradıldığını və container-in `MediaRepository` üçün Eloquent implementasiyanı resolve etdiyini təsdiqləyir.
- **Media Task authorization-u owns etmir:** source-boundary test Media scaffold-də Tasks dependency-si, `TaskPolicy` və `authorize()` çağırışının olmadığını yoxlayır.
- **Schema ownership və integrity:** metadata test-i central DTO/service/repository flow-u ilə record yazır; ayrıca test unique UUID və unique internal path constraint-lərini SQLite-də yoxlayır.
- **Private metadata disclosure yoxdur:** Resource test-i `disk`, `path`, `sha256` key-lərinin və onların dəyərlərinin response array-də olmadığını təsdiqləyir.
- **Speculative future work yoxdur:** Taskın source inventory-si yalnız metadata scaffold-i daşıyır; physical storage, preview/download endpoint-ləri, thumbnail/scanner/queue implementation-u yazılmayıb və TF-501–TF-503-ə saxlanıb.
- **Module test/migration registration:** Media Feature/Unit directories default PHPUnit/Pest discovery-yə daxil edildi; rollback test bütün loaded migration chain-i, o cümlədən Media migration-u ilə keçdi.

## Testlər və yoxlama

`composer dump-autoload --no-scripts`

Nəticə: PASS — optimized autoload yenidən yaradıldı və 8509 class ehtiva etdi. Dependency dəyişdirilmədi, Composer script-i işə salınmadı.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog php artisan test --compact Modules/Media/tests/Feature/MediaModuleTest.php`

Nəticə: PASS — 5 test, 24 assertion. Module boot/binding, Task authorization sərhədi, central metadata persistence, unique constraints və Resource privacy yoxlandı.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog php artisan test --compact tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 1 test, 7 assertion. Default SQLite migration chain-inin fresh/rollback davranışı Media migration-u yüklənmiş halda keçdi.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 62 test, 302 assertion. Default SQLite suite TF-500 scaffold-i və əvvəlki verified behavior-u birlikdə failure olmadan keçdi.

## Qarşılaşılan problemlər

Yeni module composer metadata-sı əlavə ediləndən sonra outer `php artisan` bootstrap-i `storage/logs` və `bootstrap/cache` içində `nobody:nogroup` sahibliyində olan mövcud fayllara yazmağa cəhd etdi. Cari workspace process-i bu fayllara yazmaq icazəsi almadığı üçün direct command `Permission denied` verdi; `sudo chown` cəhdi də interactive password tələb etdiyinə görə istifadə edilmədi. Test database və storage behavior-u ilə əlaqəsi olmayan bu host permission problemini təhlükəsiz keçmək üçün yalnız command process-də `/tmp` cache/view path-ləri və errorlog channel təyin edildi. SQLite `:memory:` database, fake local storage və source code dəyişmədən qaldı. Bu fallback ilə focused, rollback və full suite real şəkildə keçdi.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-500 Media module registration, private metadata schema, repository/service scaffold, safe Resource və default test discovery acceptance criteria-larını executable SQLite testləri ilə təsdiqlədi. TF-501 storage/streaming behavior-u hələ implement edilməyib və bu task onu qabaqlamadı.

## Növbəti task

Canonical dependency graph-a görə TF-501 dependency-ready-dir. O, Media modulunun randomized private storage, server-side validation, streaming və compensation behavior-unu implement edəcək. TF-500 həmin gələcək security behavior-unu yalnız schema/service sərhədi ilə hazırladı.

# TF-501 — Təhlükəsiz Media storage və streaming service-ləri

## Məqsəd

TF-501 private Media metadata scaffold-ini real, təhlükəsiz binary storage davranışı ilə tamamlayır: server faylı özü yoxlayır, random private path-də saxlayır, təhlükəsiz stream edir və database/storage failure zamanı request-in yaratdığı faylları kompensasiya edir.

## Başlanğıc vəziyyət

TF-500 `media` schema-sını, model, repository, metadata DTO/service və safe Resource-u yaratmışdı. Lakin physical write, server-side MIME validation, checksum, image limiti, download/preview və cleanup yox idi. Legacy `TaskAttachmentService` isə Tasks modulunda qalırdı; onun Media association-na köçürülməsi TF-502-yə məxsusdur və bu taskda qəsdən edilmədi.

## Edilən dəyişikliklər

- `Modules/Media/config/config.php` private `local` disk, maksimum 5 fayl, hər fayl üçün 10 MB, 8000×8000 və 40 milyon pixel image limitlərini konfiqurasiya etdi.
- `Modules/Media/app/Services/MediaStorageService.php` əlavə edildi. `storeMany()` bütün faylları əvvəl validate edir, sonra UUID əsaslı `media/{uuid}.{extension}` private path-ə yazır, SHA-256 hesablayır və batch metadata-nı yaradır. Bu, user filename-ni storage location-dan ayırır.
- MIME tipi client declaration-dan yox, temporary server faylının `finfo` nəticəsindən alınır. Extension/MIME cütlüyü yalnız PDF, PNG/JPEG/WebP, plain text, Word və Excel allowlist-ində olduqda qəbul edilir. SVG, unknown/executable extension və plain text məzmunlu `.pdf` rədd edilir.
- Original filename CR/LF-də kəsilir, path separator və təhlükəli simvollardan təmizlənir. Download/preview response-ları safe `Content-Type`, `Content-Disposition`, `X-Content-Type-Options: nosniff` və `Cache-Control: private, no-store` header-ləri verir. Image/PDF inline, digər formatlar attachment olur; Range/206 əlavə edilməyib.
- `MediaMetadataService`-ə transaction-lı `registerMany()` və `delete()` əlavə edildi. Storage və ya database yazılışı uğursuz olarsa service həmin request-də yaradılmış bütün disk path-lərini silir. Uğurlu delete metadata-nı soft-delete edir, sonra physical file-i silir; physical delete failure-i gizlədilmir.
- `Modules/Media/tests/Feature/MediaStorageServiceTest.php` əlavə edildi; scaffold-in no-Task-authorization source guard-u storage service-i də əhatə etdi.

## Texniki izah

Media service Task modelini və Task policy-ni bilmir. Gələcək Tasks flow əvvəl Task authorization və nested association-u yoxlayacaq, sonra bu service-i çağıracaq. Bu ayrılıq Media-nın parent access qərarını öz üzərinə götürməsinin qarşısını alır.

All-or-nothing davranış iki qatdadır: ilk olaraq bütün batch validate edilir, buna görə altıncı fayl və ya böyük image varsa heç bir fayl yazılmır. Storage başlandıqdan sonra metadata transaction və ya disk write qırılsa, saxlanmış random path-lər silinir. Resource və Activity contract-i internal `disk`, `path`, `sha256` sahələrini açmır; mövcud safe Resource testi bunu yenidən qoruyur.

## Acceptance Criteria

- **Spoofed/unknown content:** Focused test plain-text `.pdf`, SVG və unknown input-un server-side allowlist ilə rəddini yoxlayır.
- **Limitlər:** 6 fayllı batch, 10 MB-dan böyük fayl və 8001 pixel eni olan image persistence-dən əvvəl rədd edilir.
- **Private metadata və safe filename:** Success test UUID private path, detected `text/plain`, SHA-256 və header-injection mətninin disposition-a keçmədiyini təsdiqləyir.
- **Streaming/missing file:** `nosniff`, private cache, inline/attachment, full 200 response yoxlanır; diskdə olmayan record safe exception verir.
- **Compensation/cleanup:** Fake repository database failure-i physical faylı kompensasiya edir; unavailable disk metadata yaratmır; successful delete soft-delete və physical cleanup edir.
- **Disclosure yoxdur:** TF-500 `MediaResource` privacy testi bütün Media suite-də keçir; Media Task authorization və Activity sahibliyini almır.

## Testlər və yoxlama

`php -l Modules/Media/app/Services/MediaStorageService.php && php -l Modules/Media/app/Services/MediaMetadataService.php && php -l Modules/Media/tests/Feature/MediaStorageServiceTest.php`

Nəticə: PASS — syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Media/tests/Feature/MediaStorageServiceTest.php`

Nəticə: PASS — 7 test, 32 assertion. Validation, compensation, stream və cleanup behavior-u yoxlandı.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Media/tests`

Nəticə: PASS — 12 test, 56 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 69 test, 334 assertion. Default SQLite suite failure olmadan keçdi.

## Qarşılaşılan problemlər

İlk test variantında UploadedFile helper-in client MIME məlumatı təhlükəsiz server detection sübutu vermirdi. Service birbaşa temporary faylın `finfo` nəticəsini oxumağa dəyişdirildi. CR/LF silindikdən sonra ikinci sətrin filename-də qala bilməsi də aşkar edildi; sanitization indi CR/LF-də adı kəsir. Hər iki problem focused testlə yenidən yoxlanıldı.

Hostun `storage/logs` və `bootstrap/cache` sahibliyi direct `php artisan` outer bootstrap yazılışını bloklayır. Source və real permission dəyişdirilmədi; command-lar process-local `/tmp` cache/view və `errorlog` ilə işə salındı. Bu external host məhdudiyyəti TF-501 verification-u bloklamadı.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-501 private randomized storage, server-side validation, checksum/metadata, safe streaming, cleanup və failure compensation acceptance criteria-larını executable testlərlə qarşıladı.

## Növbəti task

Canonical dependency graph-a görə `TF-502 — Migrate inherited task attachments` dependency-ready-dir. O, legacy `task_attachments` metadata-sını təhlükəsiz Media association-na daşıyacaq; həmin migration və Task flow bu taskda qəsdən implement edilmədi.

# TF-502 — Inherited Task attachment-lərinin Media association-na köçürülməsi

## Məqsəd

TF-502-nin məqsədi əvvəl Tasks modulunun özündə saxlanan file metadata-sını central `Media` record-u ilə əlaqələndirməkdir. Task attachment artıq physical storage-un sahibi deyil; o yalnız authorized Task və Media arasındakı association olur. Mövcud attachment record və physical fayl itirilmədən saxlanmalıdır.

## Başlanğıc vəziyyət

`task_attachments` cədvəlində `disk`, `path`, original filename, MIME və size birbaşa Task record-u ilə idi. `TaskAttachmentService` faylı `task-attachments/{task}` altına özü yazır və download-u həmin raw path-dən verirdi. TF-501-də Media storage artıq hazır olsa da, Task attachment-də `media_id` və Media relation yox idi.

## Edilən dəyişikliklər

- `Modules/Tasks/database/migrations/2026_09_02_110000_add_media_id_to_task_attachments_table.php` nullable `media_id` foreign key və unique index əlavə etdi. Mövcud data üçün column əvvəl nullable qalır, sonra backfill işləyir; legacy `disk`/`path`/metadata sütunları silinmir.
- `TaskAttachmentMediaBackfill` hər `media_id`-siz inherited record üçün eyni disk/path-li bir `media` record yaradır, checksum-u mövcud private file-dan hesablayır və attachment-i həmin record-a bağlayır. File diskdə yoxdursa deterministik marker checksum yazılır; record silinmir və gələcək reconciliation üçün izlənilə bilir. Backfill idempotentdir.
- `TaskAttachment` modelinə `media()` relation-u, factory-yə Media factory-si, repository list/pagination sorğularına eager-loaded `media` relation-u əlavə edildi.
- `TaskAttachmentService` yeni upload-da əvvəl `MediaStorageService` çağırır, sonra Task association və safe Activity event-ni transaction-da yaradır. Association/Activity failure olsa yaratdığı Media record və physical file kompensasiya edilir. Legacy sütunlar migration verification müddətində Media metadata-dan doldurulur, amma runtime download üçün authoritative mənbə deyil.
- Download artıq `MediaStorageService` ilə işləyir. Delete əvvəl association/activity transaction-unı tamamlayır, sonra central Media metadata/file cleanup-u edir. `TaskAttachmentResource` public-safe Media metadata-sını (`media_uuid`, name, MIME, size) qaytarır; `disk`, `path`, `sha256` açılmır.
- `TaskAttachmentMediaMigrationTest` yeni association, legacy backfill-də record/file count və checksum, Media-backed download, Resource privacy və cleanup davranışını yoxlayır.

## Texniki izah

Migration fiziki file-i yeni yerə daşımır: inherited `disk` və `path` olduğu kimi Media record-a köçürülür. Bu, mövcud download URL-lərinin eyni faylı tapmasını qoruyur və data migration zamanı əlavə file-copy failure riski yaratmır. `media_id` unique olduğu üçün hazırkı scope-da bir Media record yalnız bir Task attachment association-na sahibdir.

Yeni upload flow-da Media disk/path/checksum və content validation işini owns edir. Tasks yalnız Task ID, uploader, association və Activity context-ni saxlayır. Bu, Media private-storage qaydalarının Task controller/service-lərinə yenidən kopyalanmasının qarşısını alır. Parent Task authorization hələ Tasks tərəfində qalır; TF-503 onun Web/API multi-file flow-larını və nested-tampering contract-ini tamamlayacaq.

## Acceptance Criteria

- **Record/file/download preservation:** Backfill test-i bir legacy attachment üçün əvvəlki physical file-in yerində qaldığını, hər iki table-də count-un 1 olduğunu, Media path/checksum-un uyğun olduğunu və Media-backed download-un 200 qaytardığını yoxlayır.
- **Orphan/inaccessible association yoxdur:** Yeni upload test-i bir Task attachment-in tək `media_id` relation-u ilə yarandığını yoxlayır. Delete test-i association-i silir, Media record-u soft-delete edir və physical file-i təmizləyir. Association transaction failure-i üçün TF-501 compensation building block-u istifadə olunur.
- **Legacy fields erkən silinməyib:** Migration yalnız `media_id` əlavə edir; old disk/path/name/MIME/size sütunları qalır və yeni association testində Media ilə eyni qiymətləri saxladığı göstərilir.
- **Safe metadata:** Resource yalnız public-safe Media identity/display sahələrini qaytarır; internal disk/path/checksum mövcud deyil.

## Testlər və yoxlama

`php -l Modules/Tasks/app/Services/TaskAttachmentService.php && php -l Modules/Tasks/app/Models/TaskAttachment.php && php -l Modules/Tasks/app/Support/TaskAttachmentMediaBackfill.php && php -l Modules/Tasks/database/migrations/2026_09_02_110000_add_media_id_to_task_attachments_table.php && php -l Modules/Tasks/tests/Feature/TaskAttachmentMediaMigrationTest.php`

Nəticə: PASS — syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/TaskAttachmentMediaMigrationTest.php`

Nəticə: PASS — 4 test, 31 assertion. Association, legacy backfill, download, Resource privacy və cleanup yoxlandı.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 1 test, 7 assertion. Yeni `media_id` migration-u rollback chain-də keçdi.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests`

Nəticə: PASS — 7 test, 41 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 73 test, 365 assertion. Default SQLite suite failure olmadan keçdi.

## Qarşılaşılan problemlər

External blocker yaranmadı. Migration backfill məntiqi əvvəl anonymous migration daxilində idi; legacy record/file preservation behavior-unı birbaşa feature testlə sübut etmək üçün bu kod `TaskAttachmentMediaBackfill` helper-inə çıxarıldı. Migration və test eyni idempotent məntiqi istifadə etdiyindən test future source drift riskini azaldır.

Hostun `storage/logs` və `bootstrap/cache` sahibliyi direct `php artisan` outer bootstrap yazılışını bloklayır. Source və permission dəyişdirilmədi; testlər process-local `/tmp` cache/view və `errorlog` ilə keçdi. Bu TF-502 üçün task-owned blocker deyil.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-502 inherited attachment-lərin Media association-na təhlükəsiz backfill-i, Media-backed runtime flow-u, legacy field preservation və rollback acceptance criteria-larını executable testlərlə qarşıladı.

## Növbəti task

Canonical dependency graph-a görə `TF-503 — Task media Web/API flows` dependency-ready-dir. O, multi-file upload/list/preview/download/delete endpoint-lərini parent Task authorization və read-only state rules ilə tamamlayacaq.

# TF-503 — Task Media Web/API flow-ları

## Məqsəd

TF-503 Task-a bağlı private Media record-ları üçün canonical Web/API flow-larını tamamlayır. Parent Task authorization-u əvvəl yoxlanır, sonra Media service storage/stream əməliyyatını edir; user başqa Task altında media identifier istifadə etməklə məlumat ala bilməməlidir.

## Başlanğıc vəziyyət

TF-502-də Media association və single-file compatibility service flow-u vardı, lakin API hələ legacy `/attachments` endpoint-lərini istifadə edir, `media[]` batch request, preview route və target API contract yox idi. Uploader delete policy-si global delete permission-a bağlı olduğuna görə canonical uploader-or-manager qaydasını tam ifadə etmirdi.

## Edilən dəyişikliklər

- API route-ları canonical `GET/POST /api/v1/tasks/{task}/media`, `GET .../{attachment}/preview`, `GET .../{attachment}/download` və `DELETE .../{attachment}` contract-inə keçirildi. Web route və Task detail form/link-ləri də eyni media naming-i istifadə edir.
- `UploadTaskAttachmentRequest` `media[]` array-i, minimum bir və maksimum beş fayl shape-i qəbul edir. `TaskAttachmentService::uploadMany()` əvvəl Media service-in batch validation/storage işini çağırır, sonra bütün Task association-larını və safe `attachment.uploaded` Activity-lərini bir transaction-da yaradır. Bir fayl invalid olsa Media service heç bir fayl/record yazmır; association/activity failure olsa yaradılmış bütün Media-lar kompensasiya edilir.
- Preview yalnız Media service-in icazə verdiyi image/PDF inline response davranışını istifadə edir; download attachment response olaraq qalır. Hər nested media action-u attachment-in `task_id` dəyərini parent route Task ilə müqayisə edir və mismatch üçün 404 qaytarır.
- `TaskPolicy` upload və delete üçün Active project şərti əlavə etdi. Delete artıq Task-u görə bilən uploader-a və context manager-ə icazə verir; member-in öz faylını silməsi global `attachments.delete` permission-dan asılı deyil. Service ayrıca Completed/Archived invariant-ını qoruyur.
- Task detail səhifəsi multi-file input, Media metadata, preview link və yeni named route-ları istifadə edir. `TaskAttachmentResource` media UUID və safe preview/download URL-lərini verir; internal storage metadata açılmır.
- `Modules/Tasks/tests/Feature/TaskMediaFlowTest.php` target endpoint, five-file batch, invalid compensation, cross-task 404, authorization/state matrix və stream header-lərini yoxlayır.

## Texniki izah

Controller yalnız route parent-i, policy və validated request shape-i idarə edir. `TaskAttachmentService` Media service-dən alınan record-ları Task association-na çevirir və Activity-ni eyni transaction-da yazır. Storage path, MIME, checksum, response headers və physical cleanup Media modulunda qalır. Bu səbəbdən Tasks eyni private-storage təhlükəsizlik qaydalarını yenidən implement etmir.

Batch transaction Media storage-nin all-or-nothing qaydasını Task səviyyəsinə genişləndirir. Əvvəl bütün input validate olunur; daha sonra association və Activity-lərdən biri uğursuz olsa, həmin request-də yaradılan hər Media record/file silinməyə cəhd edilir. Cross-task check route model binding-in identifier tapmasından sonra işləyir və foreign Task-a aid record üçün 403 əvəzinə təhlükəsiz 404 verir.

## Acceptance Criteria

- **Cross-task tampering:** Focused API test başqa Task altında download və delete cəhdinin 404 qaytardığını yoxlayır.
- **Authorization və read-only state:** Outsider list üçün 403, uploader öz faylı üçün 204, manager başqa uploader faylı üçün 204 alır; Completed və Archived project upload-u 403 ilə rədd edir. Service səviyyəsində read-only invariant TF-205-dən qorunur.
- **Beş fayl və invalid batch:** API beş valid text faylını 201 collection ilə yaradır. Valid+SVG batch 422 alır, `task_attachments` və `media` count-u sıfır qalır, private storage qovluğu boş olur.
- **List/preview/download/delete və safe Resource:** List 200, image preview inline `nosniff`, download attachment disposition, delete 204 verir. Resource disk/path/checksum açmır və Activity yalnız safe UUID/name/MIME/size metadata-sı yazır.

## Testlər və yoxlama

`php -l Modules/Tasks/app/Services/TaskAttachmentService.php && php -l Modules/Tasks/app/Http/Controllers/TaskAttachmentController.php && php -l Modules/Tasks/app/Http/Controllers/Api/V1/TaskAttachmentController.php && php -l Modules/Tasks/app/Http/Requests/UploadTaskAttachmentRequest.php && php -l Modules/Tasks/tests/Feature/TaskMediaFlowTest.php`

Nəticə: PASS — syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/TaskMediaFlowTest.php`

Nəticə: PASS — 6 test, 33 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 79 test, 398 assertion. Default SQLite suite failure olmadan keçdi.

## Qarşılaşılan problemlər

External blocker və task-owned failure yaranmadı. Direct `php artisan` host cache/log permission məhdudiyyətinə görə işləmədiyindən, testlər təhlükəsiz process-local `/tmp` cache/view və `errorlog` environment-i ilə icra edildi.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-503 canonical Task Media Web/API contract-i, authorization/state qaydaları, all-or-nothing batch behavior və safe streaming acceptance criteria-larını executable testlərlə qarşıladı.

## Növbəti task

Canonical dependency graph-a görə `TF-600 — Project-local issue allocation` növbəti dependency-ready taskdır. TF-103 isə owner taskları tamamlanana qədər deferred verification siyahısında qalır.

# TF-600 — Project-local issue allocation

## Məqsəd

TF-600 hər Task üçün project daxilində artan integer `issue_number` və insanın oxuduğu immutable display key (`PAY-42`) yaratmaq üçündür. Route binding internal Task ID ilə qalır; display key UI, API Resource və search üçün identity-dir.

## Başlanğıc vəziyyət

TF-400 project `key` və `next_issue_number` yaratmış, Task service isə `number` sahəsinə `KEY-N` yazmağa başlamışdı. Lakin Task schema-da local sequence ayrıca yox idi, project-local unique constraint yox idi və inherited `TSK-*` task-lar üçün deterministic backfill/report mapping-i mövcud deyildi.

## Edilən dəyişikliklər

- `2026_09_02_120000_add_issue_numbers_to_tasks_table.php` `tasks.issue_number` sahəsini backfill edir, sonra onu və display `number`-i non-null edir, `project_id + issue_number` unique index-i yaradır. `task_number_migration_reports` köhnə display number dəyişən record-lar üçün old/new mapping saxlayır.
- `TaskDisplayNumberBackfill` hər project-in task-larını ID sırası ilə emal edir. Mövcud uyğun `KEY-N` dəyəri yalnız conflict yoxdursa saxlanır; köhnə `TSK-*` və conflict-lər deterministik local sıra ilə `KEY-N`-ə çevrilir. Project `next_issue_number` maksimum istifadə olunan rəqəmdən sonrakı dəyərə qaldırılır.
- `ProjectService::allocateIssueNumber()` artıq local integer və display key daşıyan `AllocatedIssueNumberData` qaytarır. `TaskService` eyni transaction-da hər iki Task sahəsini yazır; persist failure olduqda project sequence update rollback olur.
- Task model/factory/Resource `issue_number` və `display_key`-i tanıyır. UI/search artıq internal ID route binding ilə işləyir, insan üçün isə mövcud `number`/display key göstərilir.
- `ProjectLocalIssueAllocationTest` local sequence, iki project-də eyni sequence 1, unique/non-null constraint, failed persistence rollback və inherited `TSK-*` report backfill davranışını yoxlayır.

## Texniki izah

Project row lock altında allocator cari `next_issue_number`-i oxuyur, `PAY-1` kimi display key və integer 1 qaytarır, sonra sequence-i artırır. Task save və bu update eyni database transaction-undadır; save baş tutmazsa sequence commit olunmur. Database-də composite unique index eyni project üçün eyni issue number-in iki dəfə yazılmasına qarşı son müdafiə qatıdır. Fərqli project-lər isə hər ikisi sequence 1-dən başlaya bilər, çünki `PAY-1` və `OPS-1` müxtəlif display key-dir.

## Acceptance Criteria

- **Atomic/non-null allocation:** Focused failure test-də Task persistence exception-u project sequence-i 1-də saxlayır; schema non-null sequence/display field-i və unique index-i yoxlanır.
- **Project-local uniqueness:** `PAY-1`, `PAY-2` və `OPS-1` real service testində yaranır; eyni project-də ikinci issue number 1 database tərəfindən rədd edilir.
- **Inherited backfill/report:** `TSK-000123` record-u deterministik `PAY-1` olur və `task_number_migration_reports` old/new mapping saxlayır.
- **Binding/resource/UI:** internal Task ID route binding olaraq qalır; `TaskResource` `display_key` və `issue_number` qaytarır, mövcud Blade/search isə display `number` sahəsini istifadə edir.

## Testlər və yoxlama

`php -l Modules/Projects/app/Data/AllocatedIssueNumberData.php && php -l Modules/Projects/app/Services/ProjectService.php && php -l Modules/Tasks/app/Support/TaskDisplayNumberBackfill.php && php -l Modules/Tasks/database/migrations/2026_09_02_120000_add_issue_numbers_to_tasks_table.php && php -l Modules/Tasks/tests/Feature/ProjectLocalIssueAllocationTest.php`

Nəticə: PASS — syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/ProjectLocalIssueAllocationTest.php`

Nəticə: PASS — 4 test, 19 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Projects/tests/Feature/ProjectLifecycleAndKeyTest.php`

Nəticə: PASS — 5 test, 19 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 1 test, 7 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 83 test, 417 assertion.

## Qarşılaşılan problemlər

İlk focused test yeni immutable allocation DTO-sunun module `app/Data` autoload path-indən kənarda yaradıldığını göstərdi. Fayl canonical `Modules/Projects/app/Data` qovluğuna köçürüldü və focused test yenidən PASS oldu. Bu task-owned integration problemi documentation-dan əvvəl həll edildi.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-600 project-local sequence, display key, inherited deterministic backfill/report, unique/non-null database invariant və rollback acceptance criteria-larını executable testlərlə qarşıladı.

## Növbəti task

Canonical dependency graph-a görə `TF-601 — Work types and one-level subtasks` dependency-ready-dir.

# TF-601 — Work types və birsəviyyəli subtasks

## Məqsəd

TF-601 fixed work type-ları (`task`, `bug`, `story`, `subtask`) Task domain-ə əlavə edir və subtask hierarchy-ni yalnız bir parent səviyyəsi ilə məhdudlaşdırır.

## Başlanğıc vəziyyət

Task modelində type və parent əlaqəsi yox idi. Create/update DTO-ları, request-lər, Resource, Web form və filter yalnız generic task məlumatını daşıyırdı. Parent task-in açıq subtasks olduqda Done olmasını bloklayan domain yoxlaması da yox idi.

## Edilən dəyişikliklər

- `TaskType` enum-u və `tasks` cədvəlində default `task` type, nullable self-referencing `parent_id` foreign key-i və project/parent index-i əlavə edildi.
- Create/update DTO-ları və request-lər type/parent input-unı qəbul edir. Update DTO parent field-inin həqiqətən göndərilib-göndərilmədiyini ayrıca saxlayır; köhnə client request-i type/parent-i təsadüfən dəyişdirmir.
- `TaskService` subtask üçün məcburi parent, same-project parent, standard parent type, self/nested hierarchy və parent-i olan task-in subtask-a çevrilməsi qaydalarını service qatında yoxlayır.
- `TaskStatusService` parent Done transition-dan əvvəl open subtask query-si edir. Done/Cancelled olmayan child varsa `InvalidTaskStatusTransition` qaytarılır.
- Repository parent/subtask summary-lərini eager-load edir; type filter-i əlavə edildi. Resource parent/subtask safe summary, type, Web create/edit form type/parent select-i və list/detail view type/hierarchy göstərir.
- `TaskTypeAndSubtaskTest` Web/API DTO flow-u, hierarchy conflict-ləri, fərqli valid assignee-ləri və completion guard-ı yoxlayır.

## Texniki izah

Subtask yalnız eyni project-dəki `task`, `bug` və ya `story` parent-ə bağlana bilər; başqa subtask parent ola bilməz. Bu sadə qayda recursive hierarchy və cycle yaratmadan birsəviyyəli struktur verir. Standard work type parent ID qəbul etmir. Parent task-in child-ları repository-də eager-load olunduğu üçün Resource və detail page relation üçün əlavə query yaratmır.

## Acceptance Criteria

- **Type və parent schema/integration:** enum, migration, DTO/request, Resource, API/Web form və type filter əlavə edildi.
- **Hierarchy validation:** focused test cross-project, missing parent, subtask-of-subtask və standard type ilə parent cəhdlərini rədd edir.
- **Type-change invalidation:** child-ları olan standard parent subtask-a çevrilə bilmir.
- **Completion guard:** open child olan parent Done olmur; child Done olduqda parent transition keçə bilir.
- **Assignee müstəqilliyi:** parent və subtask fərqli, eyni project-ə məxsus assignee-lərlə uğurla yaradılır.

## Testlər və yoxlama

`php -l Modules/Tasks/app/Enums/TaskType.php && php -l Modules/Tasks/app/Services/TaskService.php && php -l Modules/Tasks/app/Services/TaskStatusService.php && php -l Modules/Tasks/database/migrations/2026_09_02_130000_add_type_and_parent_to_tasks_table.php && php -l Modules/Tasks/tests/Feature/TaskTypeAndSubtaskTest.php`

Nəticə: PASS — syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/TaskTypeAndSubtaskTest.php`

Nəticə: PASS — 4 test, 21 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 87 test, 438 assertion.

## Qarşılaşılan problemlər

Task-owned external blocker yaranmadı. Test command-ları host cache/log permission məhdudiyyətinə görə process-local `/tmp` cache/view və `errorlog` environment-i ilə icra edildi.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-601 fixed work types, one-level subtask hierarchy, parent completion guard və presentation/filter acceptance criteria-larını executable testlərlə qarşıladı.

## Növbəti task

Canonical dependency graph-a görə `TF-602 — Work-item visibility, report, edit, and delete rules` dependency-ready-dir.

# TF-602 — Work-item visibility, report, edit və delete qaydaları

## Məqsəd

Project üzvünün assignment-dan asılı olmadan bütün project task-larını görməsini, Active project-də task report etməsini və mutation səlahiyyətlərinin manager/reporter context-inə görə tətbiq olunmasını təmin etmək.

## Başlanğıc vəziyyət

Task repository adi member üçün yalnız özünə assigned task-ları qaytarırdı. `TaskService::create()` yalnız manager-i qəbul edir, update/delete service çağırışları isə HTTP policy-sindən kənarda project state və actor invariant-larını eyni səviyyədə qorumurdu. Dashboard və Activity scope-ları da member-in project visibility qaydasına uyğun deyildi.

## Edilən dəyişikliklər

- `TaskPolicy`, `TaskService` və `EloquentTaskRepository` dəyişdirildi. Project member Active project-də report edə və bütün öz project task-larını görə bilir. Manager istənilən mutable task-ı edit/delete edir; reporter yalnız öz `Todo` task-ını edit edir. Completed/Archived project-də create/update/delete həm policy, həm də service qatında rədd edilir.
- `TaskService` actor üçün user lookup-u `UserRepository` ilə edir, create/update/delete transaction-larını saxlayır və update Activity-sinə təhlükəsiz `old`/`new` dəyərləri əlavə edir. Description content-i audit payload-a yazılmır, yalnız dəyişdiyi qeyd edilir.
- `ActivityQueryService` və `DashboardService` project owner və istənilən membership üçün eyni visible project setindən istifadə edir. Beləliklə outsider Activity, list/filter və Dashboard count məlumatı almır; member isə unassigned daxil olmaqla project work-u görür.
- `TaskVisibilityAndMutationTest.php` role × membership × reporter × project-state matrix-i əlavə etdi; Web, API və birbaşa service davranışını, soft delete və audit rekordunu yoxlayır.

## Texniki izah

Assignment məsuliyyəti bildirir, visibility isə project membership-dən gəlir. Repository query-si admin üçün hamını, digər user üçün owner olduğu və ya membership-i olduğu project-lərin task-larını seçir; normal Eloquent query soft-deleted record-u avtomatik gizlədir. Delete service transaction daxilində soft delete və `task.deleted` Activity rekordunu birlikdə yaradır, buna görə task normal list-dən yox olur, audit tarixi isə qalır.

Reporter edit qaydası task hələ `Todo` olduqda və `creator_id` actor-a aid olduqda keçər; manager üçün project context manager/owner/admin qərarı istifadə edilir. Policy HTTP entry point-i qoruyur, service isə birbaşa çağırışın həmin read-only və actor invariant-larını bypass etməsinə imkan vermir.

## Acceptance Criteria

- **Role/project-role/reporter/outsider/state matrix:** Focused test member və outsider list/detail/API davranışını, member report-u, manager edit/delete-ni, reporter Todo editini, başqa task və late-state denial-ını, Completed/Archived create/update/delete rəddini Web/API/direct-service səviyyəsində yoxladı.
- **Soft delete audit və normal list:** Manager API delete 204 qaytarır; test record-un `withTrashed()` ilə mövcud, normal repository list-də yox və `task.deleted` Activity event-i ilə auditable olduğunu təsdiqlədi.
- **Activity/count visibility:** Focused matrix member üçün visible Activity və Dashboard `totalTasks`, outsider üçün boş Activity scope-u yoxladı. Board hələ TF-607-də yaradılacaq; bu task future board UI yazmadan mövcud list/filter/detail/activity/count entry point-lərini bağladı.

## Testlər və yoxlama

`php -l Modules/Tasks/app/Policies/TaskPolicy.php && php -l Modules/Tasks/app/Services/TaskService.php && php -l Modules/Tasks/app/Repositories/EloquentTaskRepository.php && php -l Modules/Activity/app/Services/ActivityQueryService.php && php -l Modules/Dashboard/app/Services/DashboardService.php && php -l Modules/Tasks/tests/Feature/TaskVisibilityAndMutationTest.php`

Nəticə: PASS — dəyişdirilən PHP fayllarında syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/TaskVisibilityAndMutationTest.php`

Nəticə: PASS — 5 test, 38 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 92 test, 476 assertion.

## Qarşılaşılan problemlər

Task-owned failure yaranmadı. Host cache/log permission məhdudiyyətinə görə Artisan test command-ları yalnız proses üçün `/tmp` cache/view path-ləri və `errorlog` channel ilə işə salındı; bu application behavior-u dəyişdirmir.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-602 visibility, report, mutation authority, read-only state, audit və soft-delete acceptance criteria-ları executable testlərlə təsdiqləndi.

## Növbəti task

Canonical dependency graph-a görə `TF-603 — Single-assignee rules` dependency-ready-dir.

# TF-603 — Single-assignee qaydaları

## Məqsəd

Hər task üçün nullable tək `assignee_id` modelini qorumaq, member-in yalnız özünü assign etməsini və manager-in istənilən aktiv project member-i assign/unassign etməsini təhlükəsiz şəkildə tətbiq etmək.

## Başlanğıc vəziyyət

Assignment service yalnız membership-i yoxlayırdı. Member başqa user-i assign edə, Completed project-də mutation cəhdi service qatına çata və assignment nəticəsində watcher/notification yaranmaya bilərdi.

## Edilən dəyişikliklər

- `TaskPolicy`, Web/API controller-ləri və `TaskAssignmentService` assignment target-i ilə authorize edir. Member yalnız özünü, manager isə project-dəki aktiv member-i assign edə və unassign edə bilir.
- Service Active project, actor statusu, assignee membership/statusu və no-op qaydasını transaction daxilində yoxlayır. `TaskService::create()` də member-in yalnız self-assignment etməsi qaydasını tətbiq edir.
- `task_watchers` pivot migration-u, `TaskWatcherRepository` və `Task::watchers()` relation-u əlavə edildi. Yeni assignee transaction-da watcher olur.
- Host `notifications` migration-u və `TaskAssignedNotification` əlavə edildi. Actor recipient-dən fərqlidirsə yalnız safe task/project/actor identifier-ləri ilə bir database notification yaranır; eyni assignment no-op olduğu üçün duplicate notification yaratmır.
- `TaskAssignmentRulesTest.php` Web/API/direct-service assignment matrix-i əlavə etdi.

## Texniki izah

Assignment policy HTTP request-in target user ilə səlahiyyətini yoxlayır, service isə eyni invariant-ları birbaşa çağırışda da qoruyur. Task-da assignee pivot-u yaradılmadı; mövcud nullable foreign key bir işin yalnız bir məsul şəxsi olmasını database modelində sadə saxlayır.

Assignee dəyişdikdə task save, watcher `syncWithoutDetaching`, database notification və `task.assigned` Activity event-i eyni transaction-da işləyir. Activity-də old/new assignee ID və adı saxlanır; token, path və ya başqa secret yoxdur. Tam watcher management və comment/status recipient flow-ları TF-606-da genişlənəcək.

## Acceptance Criteria

- **Foreign/non-member/removed/suspended:** focused test foreign, removed və suspended target-ləri service qatında rədd edir.
- **Unauthorized other-user/self/manager/unassign:** member Web-də özünü assign edir, başqa member-i 403 ilə rədd olunur; manager API ilə assign/unassign edir. Create flow-da da member self, manager başqa member assignment qaydası yoxlanır.
- **No-op və notification de-duplication:** eyni assignee ikinci dəfə göndəriləndə yeni Activity və notification yaranmır; manager assignment-i watcher və bir notification yaradır, self-assignment actor notification-u yaratmır.

## Testlər və yoxlama

`php -l Modules/Tasks/app/Repositories/TaskWatcherRepository.php && php -l Modules/Tasks/app/Repositories/EloquentTaskWatcherRepository.php && php -l Modules/Tasks/app/Models/Task.php && php -l Modules/Tasks/app/Providers/TasksServiceProvider.php && php -l Modules/Tasks/app/Services/TaskAssignmentService.php && php -l app/Notifications/TaskAssignedNotification.php && php -l Modules/Tasks/database/migrations/2026_09_02_140000_create_task_watchers_table.php && php -l database/migrations/2026_09_02_140100_create_notifications_table.php && php -l Modules/Tasks/tests/Feature/TaskAssignmentRulesTest.php`

Nəticə: PASS — syntax xətası yoxdur.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/TaskAssignmentRulesTest.php tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 6 test, 30 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 97 test, 499 assertion.

## Qarşılaşılan problemlər

Task-owned failure yaranmadı. Host cache/log permission məhdudiyyətinə görə testlər yalnız proses üçün `/tmp` cache/view path-ləri və `errorlog` channel ilə işə salındı.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-603 single-assignee, membership/state, auto-watch, safe notification, Activity və no-op de-duplication acceptance criteria-ları executable testlərlə qarşılandı.

## Növbəti task

Canonical dependency graph-a görə `TF-604 — Fixed workflow və timestamps` dependency-ready-dir.

# TF-604 — Fixed workflow və timestamps

## Məqsəd

Task workflow-un Product Brief-dəki `backlog → todo → in_progress → review → done/cancelled` qaydalarına keçməsi, status dəyişikliklərinin Active project və assignee/manager səlahiyyəti ilə qorunması, həmçinin stale request-lərin conflict kimi rədd edilməsidir.

## Başlanğıc vəziyyət

Task yeni yaradılarkən `todo` olurdu, `backlog` statusu yox idi və transition map canonical qaydadan fərqlənirdi. Status request-lərində optimistic version yox idi; eyni köhnə task vəziyyəti ilə gələn ikinci request ayrıca status Activity yarada bilərdi.

## Edilən dəyişikliklər

- `TaskStatus` enum-una `backlog` əlavə edildi və `TaskService` yeni task-ı yalnız server tərəfindən `Backlog` ilə yaradır.
- `TaskStatusService` exact transition map-i, assignee ordinary transition və manager terminal reopen qaydasını, Active project/active actor service invariant-ını tətbiq etdi. Open subtask parent `Done` ola bilmir.
- `tasks.version` unsigned integer migration-u, model cast-i və `TaskResource` sahəsi əlavə edildi. Status request `expected_version` tələb edir; service locked record-u yoxlayır, uğurlu status/update/assignment mutation-larında version artırır.
- Stale status üçün `TaskStatusConflict` explicit olaraq API-də `409`, `task_status_conflict` code və actionable `expected_version` error-u qaytarır. Bu TF-204-də qadağan edilmiş catch-all mapping deyil.
- Web status form hidden version göndərir; Web və API controller-ləri eyni `ChangeTaskStatusData` və `TaskStatusService` flow-undan istifadə edir.
- `TaskWorkflowTest.php` transition dataset-i, timestamp semantics, read-only/subtask denial və stale API conflict/no-duplicate-Activity ssenarisini əlavə etdi.

## Texniki izah

Status service transaction daxilində task sətrini `lockForUpdate()` ilə yenidən oxuyur. Client-in `expected_version` dəyəri database-dəki cari version ilə eyni deyilsə heç bir status, timestamp və Activity dəyişmir. Uğurlu `InProgress` girişində `started_at` yalnız ilk dəfə yazılır; geriyə keçid onu silmir. `Done` `completed_at` yazır, Done-dan reopen isə onu təmizləyir; Cancelled completed sayılmır.

## Acceptance Criteria

- **Bütün transition/actor matrix:** altı başlanğıc status üçün manager, assignee və outsider available transition dataset-i exact map-lə yoxlandı.
- **Eyni service entry point-i:** Web və API status route-ları `ChangeTaskStatusData` ilə `TaskStatusService` çağırır. Livewire və board hələ TF-702/TF-608 scope-da yoxdur; yeni paralel workflow logic yazılmadı.
- **Stale conflict:** API test ikinci köhnə version request-inin 409 qaytardığını və Activity count-u artırmadığını təsdiqlədi.
- **Timestamp və parent guard:** focused test started/completed semantics və open subtask completion rejection-u yoxladı.

## Testlər və yoxlama

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact Modules/Tasks/tests/Feature/TaskWorkflowTest.php`

Nəticə: PASS — 9 test, 31 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact tests/Feature/MigrationRollbackTest.php`

Nəticə: PASS — 1 test, 7 assertion.

`env APP_PACKAGES_CACHE=/tmp/taskflow-laravel-cache/packages.php APP_SERVICES_CACHE=/tmp/taskflow-laravel-cache/services.php APP_CONFIG_CACHE=/tmp/taskflow-laravel-cache/config.php APP_ROUTES_CACHE=/tmp/taskflow-laravel-cache/routes-v7.php APP_EVENTS_CACHE=/tmp/taskflow-laravel-cache/events.php VIEW_COMPILED_PATH=/tmp/taskflow-laravel-views LOG_CHANNEL=errorlog LOG_LEVEL=critical php artisan test --compact`

Nəticə: PASS — 106 test, 530 assertion.

## Qarşılaşılan problemlər

Focused run-da Task factory-dən yaranan model instance-də database default `version` sahəsi hydrated deyildi. Model default attribute `1` ilə tamamlandı; nəticədə yeni Task Resource, DTO və optimistic yoxlama eyni initial version-u istifadə edir. Host cache/log permission məhdudiyyəti səbəbilə testlər `/tmp` cache/view və `errorlog` environment-i ilə işə salındı.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — TF-604 fixed workflow, timestamps, parent guard və optimistic status conflict acceptance criteria-ları executable SQLite testləri ilə təsdiqləndi.

## Növbəti task

Canonical dependency graph-a görə `TF-605 — Project-scoped labels` dependency-ready-dir.

# TF-605 — Project-scoped labels

## Məqsəd

Label-ları yalnız öz project-i daxilində yaratmaq və task-lara foreign-project məlumat sızdırmadan bağlamaq.

## Edilən dəyişikliklər

`task_labels` və `task_label` schema-sı, project+name/slug unique constraint-ləri, `TaskLabel` modeli, `TaskLabelService`, Task relation/Resource, DTO/request label ID-ləri və repository label filter-i əlavə edildi. Manager label yaradır/silir; sync yalnız eyni project label-larını qəbul edir. Web/API label route/controller-ləri project context-i ilə qeyd edildi.

## Acceptance Criteria

Focused test duplicate name və foreign-project label sync cəhdini rədd edir; Task relation yalnız project-scoped pivot istifadə edir. Generic category və Component model-i əlavə edilmədi.

## Testlər və yoxlama

`php artisan test --compact Modules/Tasks/tests/Feature/TaskLabelTest.php` (process-local `/tmp` cache/view environment ilə): PASS — 1 test, 3 assertion.

`php artisan test --compact tests/Feature/MigrationRollbackTest.php`: PASS — 1 test, 7 assertion.

`php artisan test --compact`: PASS — 107 test, 533 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED**

## Növbəti task

`TF-606 — Watchers and in-app notifications` dependency-ready-dir.

# TF-605 — Project-scoped labels (verification closure)

## Edilən dəyişikliklər

Label rəngləri üçün sabit `TaskLabelColor` enum-u, məqsəd-yönlü CRUD/sync DTO-ları və repository əlavə edildi. Manager Web/API create, update və delete əməliyyatlarını yalnız Active project-də yerinə yetirir; nested project/label uyğunsuzluğu 404, rəng və duplicate slug/name yoxlamaları 422 qaytarır. Task create/edit və ayrıca API/Web sync eyni-project label-ları transaction daxilində bağlayır; foreign label 422 ilə rədd edilir.

Web-də project label idarəetmə səhifəsi, task create/edit checkbox-ları, task detail label görünüşü və label filter əlavə edildi. API label collection/resource və task resource label-ları təhlükəsiz, minimal contract ilə qaytarır. Silinən label pivot əlaqələrini cascade ilə ayırır, task-ları silmir; label və sync dəyişiklikləri Activity-yə yazılır.

## Testlər və yoxlama

`php artisan test --compact Modules/Tasks/tests/Feature/TaskLabelTest.php`: PASS — 5 test, 51 assertion.

`php artisan test --compact`: PASS — 111 test, 581 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — duplicate, rəng, cross-project, read-only lifecycle, Web/API CRUD, task sync, filter, delete-detach və manager/member/outsider authorization matrix executable testlərlə təsdiqləndi.

# TF-606 — Watchers and in-app notifications

## Edilən dəyişikliklər

Mövcud `task_watchers` pivot-u üzərində self watch/unwatch və manager watcher idarəetməsi, project-membership/active-account qoruması və API endpoint-ləri tamamlandı. Reporter və assignee auto-watch olunur; member removal və account suspension watcher subscription-larını təmizləyir.

Assignment, comment və status dəyişiklikləri yalnız aktiv project-member watcher-lara, actor istisna olmaqla, bir dəfə database notification göndərir. Web notification siyahısı, unread sayğacı, mark-read/mark-all-read və task route üzərindən yenidən authorize olunan keçidlər əlavə edildi.

## Testlər və yoxlama

Focused watcher suite: PASS — 3 test, 21 assertion. Related watcher/assignment/workflow/visibility suite: PASS — 22 test, 113 assertion.

Final SQLite suite: `php artisan test --compact` PASS — 114 test, 602 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — watcher membership, cleanup, recipient deduplication, actor exclusion və Web notification inbox acceptance criteria executable testlərlə təsdiqləndi.

# TF-607 — Ranked backlog

Project-local `rank` sütunu, status-column indekslənməsi və deterministik 1000-addımlı rebalance əlavə edildi. Yeni task Backlog sonuna yazılır; status transition yalnız həmin task-ı target column sonuna yerləşdirir. Explicit neighbor reorder optimistic version yoxlaması, row lock və manager-only policy ilə Web/API-də tətbiq edildi.

Project backlog Web/API presentation-i rank sırası və pagination ilə əlavə olundu. Cross-project/non-manager neighbor, stale request, status placement və priority/rank ayrılığı focused testlərlə yoxlandı.

Final SQLite suite: `php artisan test --compact` PASS — 117 test, 615 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — rank allocation, reorder locking/version conflict, authorization, backlog pagination və status-column placement acceptance criteria executable testlərlə təsdiqləndi.

# TF-608 — Kanban board

Project-scoped board read modeli fixed status column-ları və eager-loaded card relation-ları ilə əlavə edildi. Web board search state saxlayır, server-side status form fallback verir və vanilla drag/drop eyni status endpoint-inə expected version ilə müraciət edir; conflict istifadəçiyə görünən şəkildə bərpa olunur. API board yalnız authorize edilmiş project task-larını qruplaşdırır.

Focused board suite: PASS — 2 test, 13 assertion.

Final SQLite suite: `php artisan test --compact` PASS — 119 test, 628 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — project scope, eager grouped cards, fallback workflow authorization və visible drag/drop conflict recovery executable testlərlə təsdiqləndi.

# TF-609 — Search and filters

Task filter DTO-sı text/key, project, multi status/type/priority, assignee, reporter, labels, parent, due/overdue və signed sort parametr-lərini vahid query scope-a daşıyır. API unknown sort-u 422 ilə rədd edir; Web URL query state pagination ilə qorunur. Scope visible project-lərlə məhdudlaşır və filter query-ləri üçün index-lər əlavə edildi.

Focused filter suite: PASS — 2 test, 9 assertion.

Final SQLite suite: `php artisan test --compact` PASS — 121 test, 637 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — validated signed sort, shared scoped filters, safe visible options və URL state acceptance criteria executable testlərlə təsdiqləndi.

# TF-610 — Comments business completion

Comment request-i boş/5,000 simvoldan uzun mətni rədd edir; service isə trim, Active project, aktiv üzvlük və author/manager delete invariant-larını birbaşa qoruyur. Web Blade comment mətnini escaped göstərir, API Resource müəllif/tarix contract-ını qaytarır və nested task/comment uyğunsuzluğu 404 olur. Activity payload-da comment mətni saxlanmır; watcher yalnız uğurlu comment action üçün bir notification alır.

Focused comment/auth/watcher suite: PASS — 10 test, 74 assertion.

Final SQLite suite: `php artisan test --compact` PASS — 125 test, 680 assertion.

## Yekun vəziyyət

**COMPLETE / VERIFIED** — cross-task, outsider, read-only, XSS, validation/no-op notification, author/manager delete, Web/API contract və activity sanitization acceptance criteria executable testlərlə təsdiqləndi.
