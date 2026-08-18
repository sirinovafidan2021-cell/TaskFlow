# Test Codebase

## New Project Notification

When an authorized **Admin** creates a project through either the web or API flow, the shared `ProjectService` first commits the project transaction and then dispatches `NewProjectCreatedNotification` to every registered user with an email address. No notification preferences or unsubscribe mechanism currently exists, so verified and unverified registered users are both included.

The Laravel notification uses the mail channel and contains the recipient greeting, project name, optional description, creator, creation date, and a project link. It implements `ShouldQueue`, so each email is queued rather than sent during the project-creation request. The database queue is the configured default (`QUEUE_CONNECTION=database`); run a worker with `php artisan queue:work`.

Notifications are not dispatched for validation or authorization failures, transaction failures, or projects created by non-admin actors. The notification dispatch lives only in `ProjectService`, which prevents duplicate web/API notifications. Focused notification tests use `Notification::fake()`, so they never send real email.

TaskFlow uses Pest with `RefreshDatabase` for feature tests. The `phpunit.xml` Modules suite discovers tests in Projects, Tasks, Activity, and Dashboard, while `tests/Pest.php` binds each feature-test location to Laravel's application test case. The suite runs against an in-memory SQLite database, so it does not use developer database records.

## Authentication Tests

- `tests/Feature/Auth/RegistrationTest.php` covers registration pages, valid registration, validation, duplicate email protection, password hashing, default Member assignment, and the existing web login flow.
- `tests/Feature/Auth/LoginTest.php` covers valid, invalid, and missing web login credentials.
- `tests/Feature/Auth/LogoutTest.php` verifies authenticated web logout and session invalidation.
- `tests/Feature/Api/V1/AuthTest.php` covers API registration, login success and validation failures, verified-only access, bearer-token issuance, and logout revocation.

## Sanctum Tests

- `tests/Feature/Auth/SanctumTest.php` uses real HTTP requests with real personal access tokens to verify Bearer authentication, missing and invalid tokens, protected user access, and token revocation after logout.

## Email Verification Tests

- `tests/Feature/Auth/EmailVerificationTest.php` fakes notifications and covers registration/resend notifications, unverified users, signed URL verification, already-verified redirects, invalid URLs, and verified middleware protection.

## Projects Tests

- `Modules/Projects/tests/Feature/ProjectApiTest.php` covers unauthenticated access, project CRUD, API response data, validation, permission denial, owner membership, soft deletion, and persistence.
- `Modules/Projects/tests/Feature/ProjectMemberTest.php` covers member add/list/remove operations, relationship persistence, and membership policy denial.
- `Modules/Projects/tests/Feature/ProjectWebTest.php` covers authentication redirects plus authorized project pages and web creation.

## Tasks Tests

- `Modules/Tasks/tests/Feature/TaskApiTest.php` covers authentication, CRUD, active-project and assignee relationships, generated task number, status/priority response values, validation, authorization, and soft deletion.
- `Modules/Tasks/tests/Feature/TaskWebTest.php` covers authenticated task pages and creation through the web route.

## Activity Tests

- `Modules/Activity/tests/Feature/ActivityApiTest.php` verifies real Spatie activity recording, actor/subject relationships, API retrieval structure, authentication, and authorization.
- `Modules/Activity/tests/Feature/ActivityWebTest.php` covers protected activity-page rendering and permission denial.

## Dashboard Tests

- `Modules/Dashboard/tests/Feature/DashboardApiTest.php` covers Sanctum protection, authorization, dashboard counts, task data, and response structure.
- `Modules/Dashboard/tests/Feature/DashboardWebTest.php` covers guest redirects, permitted page rendering, and denied users.

# Test Results

Final `php artisan test` result: **32 tests passed, 0 failed, 0 skipped**. Duration: **2.04 seconds**.

- Authentication: registration, login, logout, and authenticated-user coverage passed.
- Sanctum: token creation, Bearer authentication, missing/invalid tokens, protected routes, and revocation passed.
- Email Verification: notification, resend, verification, invalid links, and verified middleware passed.
- Projects: CRUD, membership, policies, web, API, and persistence passed.
- Tasks: CRUD, project/assignee relationships, policies, web, API, and persistence passed.
- Activity: recording, retrieval, relationships, authorization, web, and API passed.
- Dashboard: access control, authorization, statistics, web, and API passed.
