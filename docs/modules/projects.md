# Projects modulu

## Məqsəd

Projects modulu layihənin yaradılması, siyahılanması, redaktəsi, statusu, sahibliyi və üzvlüyünü idarə edir.

## Fayl quruluşu

| Fayl qrupu | Məqsəd |
| --- | --- |
| app/Models/Project.php, ProjectMember.php | Layihə və membership Eloquent modelləri. |
| app/Http/Controllers/ProjectController.php, ProjectMemberController.php | Web layihə və üzvlük əməliyyatları. |
| app/Http/Controllers/Api/ | API ProjectController və ProjectMemberController. |
| app/Http/Requests və Requests/Api | Project, member və index validation. |
| app/Http/Resources | ProjectResource və ProjectMemberResource JSON formatı. |
| app/Services | ProjectService və ProjectMemberService biznes qaydaları. |
| app/Repositories | Interface və Eloquent project/member persistence. |
| app/Policies/ProjectPolicy.php | Layihə authorization. |
| routes/web.php, routes/api.php | Web və api/v1 route-ları. |
| database/migrations | projects və project_members sxemi. |
| database/factories | Project və ProjectMember factory. |
| tests/Feature | API, web, member və notification testləri. |

## Database və model

projects cədvəli owner_id, name, unique slug, description, status, starts_at, due_at, soft delete saxlayır. project_members project_id, user_id, member_role və joined_at saxlayır; eyni user bir layihəyə iki dəfə əlavə edilə bilməz.

Project HasFactory və SoftDeletes istifadə edir. Fillable sahələri name, slug, description, status, owner_id, starts_at, due_at-dır. status ProjectStatus enum, tarixlər date cast-dir. owner User-ə belongsTo, members pivot üzərindən belongsToMany, memberships ProjectMember-ə hasMany-dir.

## Controller-lər

ProjectController index ilə Policy viewAny-dən sonra repository siyahısını göstərir; create/store yaratma formasını və ProjectService.create çağırışını edir; show, edit, update modelə baxış/redaktə verir; archive ProjectService.archive, activate ProjectService.activate çağırır. Bütün web route-ları web, auth, verified altındadır.

ProjectMemberController index manageMembers authorization-dan sonra üzvləri və əlavə edilə bilən user-ləri göstərir. store StoreProjectMemberRequest məlumatını ProjectMemberService.addMember-ə verir. destroy removeMember çağırır.

API ProjectController index, show, store, update, destroy metodlarını saxlayır. Onlar müvafiq Policy yoxlamasından sonra repository/service çağırır və ProjectResource JSON qaytarır. API ProjectMemberController index, store, destroy metodları ilə member resource/JSON cavabı verir.

## Request, service və Policy

StoreProjectRequest və UpdateProjectRequest layihə adı, təsvir və tarixlər üçün HTTP validation edir. ProjectIndexRequest q, ProjectStatus və per_page filterlərini yoxlayır. StoreProjectMemberRequest user_id, ProjectMemberRole və optional joined_at məlumatını yoxlayır. API request-ləri həmin qaydaları paylaşır.

ProjectService.create transaction-da slug yaradır, draft Project saxlayır, yaradanı manager membership kimi əlavə edir və project.created activity-sini yazır. Transaction-dan sonra actor admin olduqda NewProjectCreatedNotification bütün email-li qeydiyyatlı user-lərə queue ilə göndərilir. update yalnız dəyişmiş sahələr üçün activity yaradır; archive, activate və delete də activity qeyd edir. Yalnız draft project active edilə bilər.

ProjectMemberService təkrar üzvlüyü rədd edir, owner-in silinməsini qadağan edir, membership əlavə/silmə activity-si yaradır. canManage admin, owner və ya project manager üçün doğrudur.

ProjectPolicy viewAny/create üçün permission, view/update/archive/delete/manageMembers üçün permission və həmin konkret layihəyə bağlılığı yoxlayır.

## Route və permissions

Web: GET /projects, /create, /{project}, /{project}/edit; POST /projects; PUT /projects/{project}; PATCH /archive və /activate; GET/POST/DELETE project members. API: GET/POST /api/v1/projects; GET/PUT/PATCH/DELETE /api/v1/projects/{project}; GET/POST/DELETE members. Əsas permissions projects.view, projects.create, projects.update, projects.archive, projects.members.manage-dir.

## Activity, notification və test

Modul project.created, project.updated, project.archived, project.activated, project.deleted, project.member_added və project.member_removed event-lərini ActivityRecorder-a verir. Admin layihə yaratdıqda mail notification queue-ya verilir.

ProjectApiTest CRUD, validation, authorization, soft delete və API response-u; ProjectMemberTest membership; ProjectWebTest web girişini; NewProjectCreatedNotificationTest dispatch şərtlərini yoxlayır.

Tam yaratma axını: Admin → web/API request → validation → create Policy → ProjectController → ProjectService transaction → Project/ProjectMember database → ActivityRecorder → commit → queued NewProjectCreatedNotification → email.
