# API

API prefix-i /api/v1-dir. Qeydiyyat və login throttle:6,1 istifadə edir. Qorunan modul route-ları api, auth:sanctum və api.verified middleware-ləri ilə qeydiyyatdadır. Validation xətası 422, authentication xətası 401, authorization/verification xətası 403 JSON qaytara bilər.

## Authentication API

| Metod/URL | Controller metodu | Middleware | Request və cavab |
| --- | --- | --- | --- |
| POST /api/v1/register | AuthController.register | api, throttle | RegisterRequest: name, email, password, confirmation, device_name. AuthTokenService user/token yaradır; 201 user/token/Bearer. |
| POST /api/v1/login | AuthController.login | api, throttle | LoginRequest: email, password, device_name. Token qaytarır. |
| POST /api/v1/logout | AuthController.logout | auth:sanctum | Cari tokeni silir. |
| GET /api/v1/user | AuthController.user | auth:sanctum | UserResource ilə authenticated user. |
| GET /api/v1/verified-user | AuthController.verifiedUser | auth:sanctum, api.verified | Təsdiqli user. |

## Projects API

| Metod/URL | Controller metodu və yoxlama | Axın |
| --- | --- | --- |
| GET /api/v1/projects | ProjectController.index; ProjectIndexRequest, viewAny | Repository filter/pagination → ProjectResource collection. |
| POST /api/v1/projects | store; StoreProjectRequest, create Policy | ProjectService → Project model/database/activity → ProjectResource, 201. |
| GET /api/v1/projects/{project} | show; view Policy | Owner/members count yüklənir → ProjectResource. |
| PUT/PATCH /api/v1/projects/{project} | update; UpdateProjectRequest, update Policy | ProjectService → ProjectResource. |
| DELETE /api/v1/projects/{project} | destroy; delete Policy | ProjectService soft delete → uğurlu JSON. |
| GET /api/v1/projects/{project}/members | ProjectMemberController.index; manageMembers | MemberResource collection. |
| POST /api/v1/projects/{project}/members | store; StoreProjectMemberRequest, manageMembers | ProjectMemberService → activity → MemberResource, 201. |
| DELETE /api/v1/projects/{project}/members/{user} | destroy; manageMembers | ProjectMemberService silir; owner silinə bilməz. |

## Tasks API

| Metod/URL | Controller metodu və yoxlama | Axın |
| --- | --- | --- |
| GET /api/v1/tasks | TaskController.index; TaskIndexRequest, viewAny | Repository filter/pagination → TaskResource collection. |
| POST /api/v1/tasks | store; StoreTaskRequest, create Policy | Project tapılır → TaskService → model/activity → TaskResource, 201. |
| GET /api/v1/tasks/{task} | show; view Policy | Project, creator, assignee yüklənir → TaskResource. |
| PUT/PATCH /api/v1/tasks/{task} | update; UpdateTaskRequest, update Policy | TaskService → TaskResource. |
| DELETE /api/v1/tasks/{task} | destroy; delete Policy | TaskService soft delete → uğurlu JSON. |

## Activity və Dashboard API

| Metod/URL | Controller metodu | Request/cavab |
| --- | --- | --- |
| GET /api/v1/activities | ActivityController.index | ActivityIndexRequest və viewAny Policy → ActivityQueryService → ActivityResource collection. |
| GET /api/v1/dashboard | DashboardController.index | viewDashboard authorization → DashboardService → task/activity resource-ları ilə JSON xülasə. |

Yaradıcı endpoint üçün ümumi daxili axın Route → middleware → Form Request validation → Policy → Controller → Service → Model/Database → Resource → JSON cavabıdır. API Resource-lar yalnız seçilmiş sahələri qaytarır; ActivityResource password, token və secret property-lərini çıxarır.
