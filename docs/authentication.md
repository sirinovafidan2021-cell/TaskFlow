# Authentication

TaskFlow web üçün Laravel session authentication, API üçün Sanctum token authentication istifadə edir.

## Qeydiyyat

1. İstifadəçi GET /register səhifəsini açır.
2. RegisterRequest name, unique email, password və password_confirmation məlumatını yoxlayır.
3. RegisteredUserController transaction-da User yaradır; parol Hash ilə yazılır.
4. RolePermissionSeeder işə salınıbsa user-ə member rolu verilir.
5. User sessiyada login edilir və session ID yenilənir.
6. Registered event-i email verification notification-u işə salır.
7. User təsdiqlənməmiş vəziyyətdə verification.notice route-na yönlənir.

User modeli Authenticatable-dır, MustVerifyEmail tətbiq edir, HasApiTokens, HasRoles və Notifiable trait-lərinə malikdir. password və remember_token gizlədilir; password hashed cast-dir.

## Login və logout

GET /login login Blade səhifəsini göstərir. POST /login LoginRequest ilə email/parolu yoxlayır, credential doğrudursa Auth ilə login edir və sessiyanı yeniləyir; səhv credential validation xətası yaradır. POST /logout auth middleware-i altında web guard-dan çıxır, sessiyanı invalid edir və CSRF tokenini yeniləyərək login-ə yönləndirir.

## Authenticated user

Web route-ları auth middleware-i ilə request user-ini əldə edir. API-də GET /api/v1/user auth:sanctum altında user məlumatını UserResource ilə qaytarır. GET /api/v1/verified-user əlavə olaraq api.verified tələb edir.
