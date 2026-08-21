# TaskFlow texniki sənədləşdirməsi

Bu sənəd mövcud kod, migration-lar və route inventarı əsasında Azərbaycan dilində hazırlanıb; kodda olmayan funksiya qeyd edilmir.

## Baxış və texnologiyalar

TaskFlow komanda layihələrini və tasklarını idarə edir: layihə/üzvlük, task/təyinat/status, şərh/fayl əlavəsi, əməliyyat jurnalı və rol əsaslı dashboard.

| Texnologiya | Layihədə istifadəsi |
| --- | --- |
| PHP 8.3 | Server tərəfi dili; bu mühitdə 8.3.6. |
| Laravel 13 | HTTP, Eloquent, validation, mail, queue, test; bu mühitdə 13.25.0. |
| Nwidart Modules | Projects, Tasks, Activity, Dashboard modulları. |
| Sanctum | API şəxsi access token və auth:sanctum. |
| Spatie Permission / Activitylog | Rollar-icazələr və activity jurnalı. |
| Blade, Tailwind CSS 4, Vite 8 | Web interfeysi və asset build. |
| Livewire 4 | Paket quraşdırılıb; xüsusi komponent yoxdur. |
| Pest 4, Pint | Feature test və kod üslubu. |

app/ User, authentication, API, middleware, notification; bootstrap/ middleware alias-ları və API JSON exception-ları; config/ auth, mail, queue, sanctum, permission, activitylog, modules; database/ host migration/factory/seeder; resources/ Blade, Tailwind, Vite; routes/ authentication; Modules/ dörd biznes modulu; tests/ Pest testləri; docs/ əlavə sənədlər.

Repository Eloquent sorğu/saxlamanı, service biznes qaydası və transaction-u, controller HTTP axınını daşıyır.

## Authentication, verification və Sanctum

Web-də qonaq register/login edir. Registration user yaradır, varsa member rolunu təyin edir, sessiya login-i və Registered event-i yaradır. User MustVerifyEmail tətbiq edir: /email/verify səhifəsi, imzalı /email/verify/{id}/{hash} linki və /email/verification-notification ilə yenidən göndərmə mövcuddur. Verification linki və resend throttle:6,1 ilə limitlənir. Əsas və modul web route-ları auth + verified istifadə edir. Logout sessiyanı etibarsızlaşdırır və CSRF tokenini yeniləyir. Parol hash edilir; password və remember_token gizlidir.

API-də POST /api/v1/register və POST /api/v1/login user, bir dəfə göstərilən token və Bearer token tipi qaytarır. Token device_name və hazırkı wildcard ability-si ilə yaradılır. Client Authorization: Bearer token göndərir. auth:sanctum tokeni yoxlayır, api.verified təsdiqsiz email üçün 403 JSON qaytarır. POST /api/v1/logout yalnız cari tokeni silir. Ability Policy və rol yoxlamasını əvəz etmir; route-larda ayrıca ability middleware-i yoxdur.

SMTP üçün .env-də MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME dəyərlərini öz mühitiniz üçün yazın; secret paylaşmayın. Queue connection QUEUE_CONNECTION ilə seçilir; jobs, job_batches, failed_jobs cədvəlləri var və worker php artisan queue:work ilə işə düşür.

NewProjectCreatedNotification ShouldQueue və afterCommit istifadə edir. Admin layihə yaratdıqda transaction-dan sonra email ünvanı olan bütün qeydiyyatlı istifadəçilərə layihə məlumatı və link göndərilir. Xəta və admin olmayan yaradan üçün bildiriş yoxdur; preference/unsubscribe mexanizmi yoxdur.

## Rollar və authorization

Authentication istifadəçinin kimliyinin, authorization isə konkret əməliyyat icazəsinin yoxlanmasıdır.

| Rol | Səlahiyyət |
| --- | --- |
| admin | Bütün seed edilmiş permissions. |
| project_manager | User/API idarəsi istisna olmaqla biznes permissions-ları. |
| member | projects.view, tasks.view, tasks.status.change, comments.create, attachments.upload, dashboard.view. |

Permissions: users.roles.manage, api_tokens.manage; projects.view/create/update/archive/members.manage; tasks.view/create/update/assign/status.change/delete; comments.create/delete; attachments.upload/delete; activity.view, dashboard.view.

ProjectPolicy, TaskPolicy, ActivityPolicy resurs səviyyəsini yoxlayır. ProjectMemberService admin, owner və project manager fərqini nəzərə alır. Task yalnız active layihədə yaradıla bilər; assignee layihə üzvü olmalıdır. TaskStatusService status keçidlərini məhdudlaşdırır.

## Modullar

### Projects

Layihə, sahib, status və üzvlüyü idarə edir. Project soft delete-dir; owner_id users-ə bağlıdır, members many-to-many project_members, memberships one-to-many əlaqələrinə malikdir. Statuslar draft, active, archived; yeni layihə draft olur və yalnız draft aktivləşir.

Web: siyahı, yaratma, göstərmə, redaktə, arxivləşdirmə, aktivləşdirmə, üzv list/add/remove. API: CRUD və member list/add/remove. Qatlar: controller-lər, Store/Update/Index/member request-ləri, Project/ProjectMember resource-ları, ProjectService/ProjectMemberService və interface + Eloquent repository-lər. Owner silinə bilməz. Activity event-ləri project.created, updated, archived, activated, deleted, member_added, member_removed.

Testlər: ProjectApiTest, ProjectMemberTest, ProjectWebTest, NewProjectCreatedNotificationTest.

### Tasks

Task soft delete-dir və project/creator/optional assignee ilə bağlıdır. TaskComment soft delete, TaskAttachment fayl metadata modelidir; hamısı üçün factory mövcuddur. Yeni task todo statusunda və TSK-000001 tipli nömrə ilə yaranır.

Task CRUD, assignment, status, comment və attachment üçün ayrıca service-lər mövcuddur. Attachment local diskdə task-attachments/{task-id} altında saxlanır. Web CRUD-dan əlavə assignee/status, comment və attachment upload/download/delete təmin edir. API yalnız task CRUD təmin edir; comment, attachment, assignee və status üçün API endpoint yoxdur. Activity: task.created/updated/deleted/assigned/status_changed, comment və attachment event-ləri.

Testlər: TaskApiTest, TaskWebTest.

### Activity

ActivityRecorder biznes qeydlərini yazır, ActivityQueryService oxuyur, ActivityDisplay görünüş dəstəyidir. activity_log cədvəli log name, təsvir, polymorphic subject/causer, event, JSON properties, batch UUID və zamanları saxlayır. activity.view və ActivityPolicy qoruması ilə web GET /activity, API GET /api/v1/activities mövcuddur. ActivityIndexRequest və ActivityResource istifadə edilir.

Testlər: ActivityApiTest, ActivityWebTest.

### Dashboard

DashboardService layihə/task sayları və user tasklarını hazırlayır. Admin bütün taskları, Project Manager üzv olduğu layihələrin tasklarını, Member isə öz tasklarını görür. dashboard.view permission-u tələb edilir. Web GET /dashboard, API GET /api/v1/dashboard mövcuddur.

Testlər: DashboardApiTest, DashboardWebTest.

## API endpoint-ləri

Bütün modul endpoint-ləri auth:sanctum + api.verified tələb edir. Validation 422, authentication 401, permission/policy/verification 403 qaytara bilər.

| Metod və URL | Authentication / məqsəd | Request |
| --- | --- | --- |
| POST /api/v1/register | Xeyr, throttle; user/token yaradır | name,email,password,password_confirmation,device_name |
| POST /api/v1/login | Xeyr, throttle; token yaradır | email,password,device_name |
| POST /api/v1/logout | Sanctum; cari tokeni silir | — |
| GET /api/v1/user | Sanctum; user qaytarır | — |
| GET /api/v1/verified-user | Sanctum + verified | — |
| GET /api/v1/projects | projects.view; siyahı/filter | ProjectIndexRequest query-si |
| POST /api/v1/projects | projects.create | name, optional description, starts_at, due_at |
| GET /api/v1/projects/{project} | Policy view | — |
| PUT/PATCH /api/v1/projects/{project} | Update + Policy | Layihə məlumatı |
| DELETE /api/v1/projects/{project} | Policy delete | — |
| GET/POST /api/v1/projects/{project}/members | Üzvlər / manage | POST: user_id,member_role, optional joined_at |
| DELETE /api/v1/projects/{project}/members/{user} | Üzv silmə | — |
| GET /api/v1/tasks | tasks.view | TaskIndexRequest query-si |
| POST /api/v1/tasks | tasks.create | project_id,title; optional description,assignee_id,priority,due_at |
| GET /api/v1/tasks/{task} | Policy view | — |
| PUT/PATCH /api/v1/tasks/{task} | Update + Policy | title; optional description,priority,due_at |
| DELETE /api/v1/tasks/{task} | Delete + Policy | — |
| GET /api/v1/activities | activity.view | ActivityIndexRequest query-si |
| GET /api/v1/dashboard | dashboard.view | — |

## Frontend və verilənlər bazası

Blade Vite vasitəsilə resources/css/app.css və resources/js/app.js yükləyir. CSS Tailwind 4-dür; JavaScript giriş faylında hazırda məntiq yoxdur. Auth görünüşləri login, register və verification səhifələridir; modul Blade görünüşləri Projects, Tasks, Activity və Dashboard üçündür.

| Cədvəl | Məzmun |
| --- | --- |
| users | ad, unique email, verification, password, remember token. |
| projects, project_members | owner, slug/status/tarixlər; üzv rol və qoşulma tarixi. |
| tasks, task_comments, task_attachments | Task, şərh və attachment məlumatı. |
| activity_log | Spatie jurnal məlumatı. |
| personal_access_tokens | Sanctum token, abilities və tarixlər. |
| Permission/Laravel cədvəlləri | roles/permissions pivot-ları; sessions, reset, cache, jobs. |

## Testlər və nəticə

Pest host və modul feature testlərini in-memory SQLite/RefreshDatabase ilə işə salır. Mövcud fayllar host-da Auth/Sanctum/EmailVerification/API testləri, modullarda Project, Task, Activity, Dashboard feature testləridir.

php artisan test bu sənəd yenilənərkən işə salınıb: **36 test keçdi, 0 uğursuz, 0 skip, 204 assertion, 2.56 saniyə.**

## Faydalı komandalar

    composer install
    npm install
    php artisan migrate
    php artisan db:seed
    php artisan optimize:clear
    php artisan route:list
    php artisan test
    npm run dev
    npm run build
    php artisan queue:work

## Mövcud vəziyyət və qalan işlər

| Sahə | Vəziyyət |
| --- | --- |
| Dörd modulun web/API əsas funksiyaları | Tamamlanıb |
| Authentication, Sanctum, email verification | Tamamlanıb |
| Rollar, permissions, Policy-lər | Tamamlanıb |
| Queue ilə layihə email bildirişi | Tamamlanıb |
| Test suite | Test olunub — 36/36 keçib |

Kodda açıq TODO və ya ayrıca qalan iş siyahısı tapılmadı. Yeni məhsul tələbi ayrıca task kimi müəyyənləşdirilməlidir.
