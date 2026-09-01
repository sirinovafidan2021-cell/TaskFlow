<?php

declare(strict_types=1);

$database = getenv('TASKFLOW_MYSQL_TEST_DATABASE');

if (! is_string($database) || preg_match('/^taskflow_test(?:_[a-z0-9_]+)?$/', $database) !== 1) {
    throw new RuntimeException(
        'MySQL compatibility tests require TASKFLOW_MYSQL_TEST_DATABASE to be a dedicated taskflow_test database.',
    );
}

$environment = [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'mysql',
    'DB_DATABASE' => $database,
    'DB_FOREIGN_KEYS' => 'true',
    'FILESYSTEM_DISK' => 'local',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
];

foreach (['HOST', 'PORT', 'USERNAME', 'PASSWORD'] as $key) {
    $value = getenv("TASKFLOW_MYSQL_TEST_{$key}");

    if (is_string($value) && $value !== '') {
        $environment["DB_{$key}"] = $value;
    }
}

foreach ($environment as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
