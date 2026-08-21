# Email Verification

User modeli MustVerifyEmail tətbiq etdiyinə görə qeydiyyatdan sonra Laravel verification notification-u göndərir. Yeni user-də email_verified_at boş qalır.

Axın: register → User yaranır → Registered event-i → SMTP mail → signed verification link → GET /email/verify/{id}/{hash} → EmailVerificationRequest.fulfill → email_verified_at yazılır → home route-na yönləndirmə.

| Route | Funksiya |
| --- | --- |
| GET /email/verify | Təsdiqlənməmiş user üçün auth.verify-email Blade səhifəsi. |
| GET /email/verify/{id}/{hash} | signed və throttle:6,1 ilə linki yoxlayır və emaili təsdiqləyir. |
| POST /email/verification-notification | auth user üçün yeni verification notification-u göndərir; throttle:6,1. |

EmailVerificationController notice, verify və send metodlarını saxlayır. Təsdiqli user notice/send çağıranda home route-na yönləndirilir. verified middleware web biznes route-larını qoruyur; EnsureApiEmailIsVerified isə API-də 403 JSON cavabı verir.

Frontend resources/views/auth/verify-email.blade.php faylındadır; user emailini göstərir, resend və logout formunu verir. SMTP config/mail.php və .env-də MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME ilə sazlanır. Real credential sənədləşdirilmir.
