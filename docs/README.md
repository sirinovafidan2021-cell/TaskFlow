# TaskFlow sənədləşdirməsi

TaskFlow komanda işini layihə və tasklar vasitəsilə idarə edən Laravel tətbiqidir. Layihələr yaradılır, üzvlər əlavə edilir, aktiv layihələrdə tasklar idarə olunur, əməliyyatlar jurnallaşdırılır və dashboard xülasəsi göstərilir.

Texnologiyalar: PHP 8.3, Laravel 13, Nwidart Laravel Modules, Sanctum, Spatie Permission, Spatie Activitylog, Blade, Tailwind CSS, Vite və Pest. Arxitektura controller, Form Request, service, repository, model və API Resource qatlarından ibarətdir.

- [Arxitektura](architecture.md)
- [Authentication](authentication.md)
- [Sanctum](sanctum.md)
- [Email Verification](email-verification.md)
- [API](api.md)
- [Verilənlər bazası](database.md)
- [Rollar və icazələr](permissions.md)
- [Testlər](testing.md)
- [Frontend](frontend.md)
- [Projects modulu](modules/projects.md)
- [Tasks modulu](modules/tasks.md)
- [Activity modulu](modules/activity.md)
- [Dashboard modulu](modules/dashboard.md)

Web istifadəçisi qeydiyyatdan keçir, emailini təsdiqləyir və dashboard, layihə, task və activity səhifələrinə daxil olur. API client Sanctum Bearer token ilə eyni biznes servislərindən istifadə edir.
