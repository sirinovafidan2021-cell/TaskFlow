# Testlər

TaskFlow Pest və pest-plugin-laravel istifadə edir. Testlər Feature yönümlüdür; ayrıca Unit test qovluğu və Unit test faylı yoxdur. phpunit.xml host və Modules/Projects, Modules/Tasks, Modules/Activity, Modules/Dashboard test qovluqlarını suite-ə daxil edir. Pest.php Laravel TestCase bağlayır. RefreshDatabase ilə testlər in-memory SQLite istifadə edir, developer verilənlər bazasını dəyişmir.

| Yer | Əhatə |
| --- | --- |
| tests/Feature/Auth/RegistrationTest.php | Register, validation, duplicate email, hash və member rolu. |
| tests/Feature/Auth/LoginTest.php, LogoutTest.php | Web login credential-ları və session logout. |
| tests/Feature/Auth/SanctumTest.php | Bearer token, token yoxluğu/səhvi və revocation. |
| tests/Feature/Auth/EmailVerificationTest.php | Notification, resend, signed link, verified middleware. |
| tests/Feature/Api/V1/AuthTest.php | API register/login, token və verified access. |
| tests/PersistenceProbeTest.php | Persistence probe. |
| Modules/Projects/tests/Feature | ProjectApiTest, ProjectMemberTest, ProjectWebTest, NewProjectCreatedNotificationTest. |
| Modules/Tasks/tests/Feature | TaskApiTest, TaskWebTest. |
| Modules/Activity/tests/Feature | ActivityApiTest, ActivityWebTest. |
| Modules/Dashboard/tests/Feature | DashboardApiTest, DashboardWebTest. |

Komandalar:

    php artisan test
    vendor/bin/pest
    php artisan test --filter=ProjectApiTest
    vendor/bin/pest Modules/Tasks/tests/Feature/TaskApiTest.php

Bu sənədləşdirmə işi zamanı php artisan test işə salındı: 36 test keçdi, 0 uğursuz, 0 skip, 204 assertion, 2.56 saniyə.
