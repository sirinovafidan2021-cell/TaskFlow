# Arxitektura

TaskFlow Laravel 13 modular monolith-dir. Host tətbiq authentication, User modeli, middleware, notification və əsas API route-larını saxlayır. Nwidart Laravel Modules dörd biznes modulunu yükləyir: Projects, Tasks, Activity və Dashboard.

## Qovluqlar

| Yer | Məsuliyyət |
| --- | --- |
| app/ | User, authentication controller/request/DTO/service, middleware və notification. |
| Modules/ | Dörd modulun biznes kodu, migration, route, view və testləri. |
| routes/ | Host web və api/v1 authentication route-ları. |
| database/ | Host migration, User factory, rol/permission seeder-ləri. |
| resources/ | Layout, auth Blade səhifələri, Tailwind və Vite giriş faylları. |
| tests/ | Host Pest feature testləri. |
| docs/ | Bu sənədləşdirmə və mövcud layihə qeydləri. |

## Qatlar

Model verilənlər bazası obyektini və əlaqələrini təsvir edir. Controller HTTP sorğunu qəbul edir, Policy çağırır və view/redirect/JSON qaytarır. API Controller Eloquent modeli birbaşa qaytarmır, API Resource istifadə edir. Form Request HTTP qaydalarını yoxlayır. Service biznes qaydası, transaction, modullar arası koordinasiya və activity qeydlərini daşıyır. Repository Eloquent query, filter, pagination və persistence üçündür. Policy resurs icazəsini, Middleware authentication, verification və rate limit-i təmin edir. Migration cədvəl sxemini, Factory test məlumatını, Seeder isə rol, permission və demo user-ləri yaradır.

Əsas axın belədir:

Request → Route → auth/verified və ya auth:sanctum middleware → Controller → Form Request validation → Policy → Service → Repository/Model → Database → Blade redirect və ya API Resource JSON.

Projects və Tasks bu qatları tam istifadə edir. Activity service əsasında Spatie jurnalını oxuyur/yazır; Dashboard service query-lərindən xülasə yaradır.
