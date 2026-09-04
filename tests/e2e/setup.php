<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Console\Kernel;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Enums\TaskType;
use Modules\Tasks\Models\Task;

$environment = [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => '/tmp/taskflow-e2e.sqlite',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'file',
    'SESSION_PATH' => '/tmp/taskflow-e2e-sessions',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'FILESYSTEM_DISK' => 'local',
    'LARAVEL_STORAGE_PATH' => '/tmp/taskflow-e2e-storage',
];

foreach ($environment as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

foreach (['/tmp/taskflow-e2e-sessions', '/tmp/taskflow-e2e-storage/logs'] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$kernel->call('migrate:fresh', ['--force' => true]);
$app->make(RolePermissionSeeder::class)->run();

$admin = User::factory()->asAdmin()->create(['name' => 'E2E Admin', 'email' => 'admin@e2e.test', 'password' => 'browser-password']);
$manager = User::factory()->asProjectManager()->create(['name' => 'E2E Manager', 'email' => 'manager@e2e.test', 'password' => 'browser-password']);
$member = User::factory()->asMember()->create(['name' => 'E2E Member', 'email' => 'member@e2e.test', 'password' => 'browser-password']);
User::factory()->asMember()->suspended()->create(['name' => 'E2E Suspended', 'email' => 'suspended@e2e.test', 'password' => 'browser-password']);

$project = Project::factory()->active()->create(['name' => 'E2E Project', 'key' => 'E2E', 'owner_id' => $manager->id]);
app(ProjectMemberService::class)->addMember($project, $member, ProjectMemberRole::Member, actor: $manager);
Task::factory()->for($project)->for($member, 'creator')->for($member, 'assignee')->create([
    'title' => 'E2E browser task', 'type' => TaskType::Bug, 'priority' => TaskPriority::High, 'status' => TaskStatus::Todo,
]);

echo "E2E fixture database prepared\n";
