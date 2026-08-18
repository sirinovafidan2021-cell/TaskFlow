# Architecture

TaskFlow is a **Laravel Modular Monolith** using Laravel 13, PHP 8.3+, and the planned `nwidart/laravel-modules` module system. Blade, Tailwind CSS, vanilla JavaScript, Vite, and limited Livewire form the frontend; Laravel Sanctum serves the API, while `spatie/laravel-permission` and `spatie/laravel-activitylog` are planned. Pest is planned but not installed yet.

```text
example-app-task/
├── app/
│   └── Models/
│       └── User.php
├── Modules/
│   ├── Projects/
│   ├── Tasks/
│   ├── Activity/
│   └── Dashboard/
├── AGENTS.md
└── docs/
```

Authentication and `User` stay in the host application. Each business module owns its needed routes, controllers, models, services, repositories, migrations, policies, and views. This is the eventual structure: do **not** create all folders in advance.

## Mature Tasks-module example

```text
Modules/Tasks/
├── app/
│   ├── Data/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Web/
│   │   │   └── Api/V1/
│   │   ├── Requests/
│   │   │   ├── Web/
│   │   │   └── Api/V1/
│   │   └── Resources/
│   ├── Livewire/
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   ├── Repositories/
│   │   ├── Contracts/
│   │   └── Eloquent/
│   ├── Services/
│   └── Support/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/views/
├── routes/
│   ├── web.php
│   └── api.php
└── tests/
    ├── Feature/
    └── Unit/
```

HTTP and approved Livewire components validate, authorize, create DTOs, and call services. Services own use cases and business rules; repositories own queries and persistence; models represent Eloquent data; views and API Resources present output. Web, API, and Livewire share services. Direct cross-module model dependencies are temporarily permitted in version 1, while cross-module writes should prefer the owning module service. All modules share one database and own their migrations.
