# Activity modulu

## Məqsəd və fayllar

Activity modulu tətbiqdəki biznes əməliyyatlarının jurnalını göstərir. app/Services/ActivityRecorder.php qeydi yaradır, ActivityQueryService.php onu filterləyib qaytarır, Support/ActivityDisplay.php Blade görünüşünə təqdimat dəstəyi verir. Web və API ActivityController-ləri, ActivityIndexRequest, ActivityResource, ActivityPolicy, web/api route-ları, index Blade və iki feature test mövcuddur.

## Saxlama və yaradılma

Spatie Activitylog integration-u activity_log cədvəlindən istifadə edir. Cədvəldə description, event, polymorphic subject və causer, JSON properties və timestamps saxlanır. Project və Task service-ləri, üzvlük, təyinat, status, comment və attachment servisləri ActivityRecorder çağırır; buna görə jurnal avtomatik model observer-i deyil, service biznes əməliyyatının hissəsidir.

## Oxuma, API və authorization

Web ActivityController.index viewAny authorization etdikdən sonra ActivityQueryService nəticəsini index Blade-ə verir. API controller eyni yoxlamadan və ActivityIndexRequest validation-dan sonra ActivityResource collection qaytarır. GET /activity web, GET /api/v1/activities API route-udur. API filterləri event, project_id, task_id, actor_id, date_from və date_to-dur.

ActivityPolicy yalnız activity.view permission-u olan user-ə viewAny verir. API auth:sanctum və api.verified, web auth və verified ilə qorunur. ActivityResource causer yüklənibsə onu qaytarır; properties içindən password, token və secret çıxarılır.

ActivityApiTest real activity qeydi, actor/subject əlaqəsi, API strukturu, authentication və authorization-u yoxlayır. ActivityWebTest qorunan səhifə və permission denial ssenarilərini yoxlayır.
