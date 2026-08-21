# Sanctum

Laravel Sanctum TaskFlow API client-ləri üçün personal access token yaradır və Bearer authentication təmin edir. User modelindəki HasApiTokens trait-i token yaratma imkanını verir.

## Axın

Login → LoginRequest credential-ları yoxlayır → AuthTokenService user-i tapır və Hash check edir → createToken çağrılır → düz mətn token yalnız cavabda qaytarılır → client Authorization: Bearer token göndərir → auth:sanctum tokeni yoxlayır → controller işləyir.

POST /api/v1/register də AuthTokenService vasitəsilə user yaradır, Registered event-i göndərir və token qaytarır. Register/login request-ləri device_name tələb edir; token hazırda wildcard ability ilə yaranır. Token abilities mövcud olsa da, modul route-larında abilities middleware-i istifadə edilmir; Policy və Spatie permission yoxlanışı qalır.

POST /api/v1/logout AuthTokenService.revokeCurrentToken vasitəsilə yalnız currentAccessToken-i silir. GET /api/v1/user qorunmuş user cavabıdır. Qeydiyyat və login throttle:6,1 limitinə malikdir. Qorunan modul route-ları auth:sanctum və api.verified ilə işləyir.
