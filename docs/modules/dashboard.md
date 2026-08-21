# Dashboard modulu

## Məqsəd və fayllar

Dashboard modulu user üçün layihə və task xülasəsi verir. DashboardService məlumatı hazırlayır; web DashboardController index Blade-i, API DashboardController index JSON cavabını verir. routes/web.php, routes/api.php, resources/views/index.blade.php, provider və DashboardApiTest/DashboardWebTest mövcuddur.

## Məlumatın yaradılması

Axın Request → auth/verified və ya auth:sanctum/api.verified → DashboardController → viewDashboard authorization → DashboardService → Eloquent query-ləri → statistika → Blade və ya JSON-dur.

Service active_projects, archived_projects, total_tasks, todo, in_progress, review, overdue, completed_today, my_tasks və recent_activity məlumatını qaytarır. Admin bütün taskları, Project Manager üzv olduğu layihələrin tasklarını, Member öz assignee_id-si olan taskları əhatə edən scope alır. My tasks project və assignee ilə yüklənir, due date və gecikmə üzrə sıralanır.

API GET /api/v1/dashboard TaskResource və ActivityResource ilə data qaytarır. Web GET /dashboard index view qaytarır. Hər iki controller dashboard.view permission-u üçün authorization edir. Web auth+verified, API auth:sanctum+api.verified tələb edir.

DashboardApiTest Sanctum qoruması, authorization, saylar, task data və cavab strukturunu; DashboardWebTest guest redirect, icazəli render və denied user ssenarilərini yoxlayır.
